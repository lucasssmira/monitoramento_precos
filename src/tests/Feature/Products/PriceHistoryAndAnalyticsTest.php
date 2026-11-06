<?php

use App\Contracts\ProductScraperInterface;
use App\DataTransferObjects\ScrapedProductData;
use App\Enums\ProductCategory;
use App\Enums\ProductStatus;
use App\Jobs\CollectProductPriceJob;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\User;
use App\Services\PriceHistoryRecorderService;
use App\Services\ProductAnalyticsService;
use App\Services\Scrapers\ScraperFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('PriceHistoryRecorderService records first reading and discards duplicate readings without variation (RN-007, RF-013)', function () {
    $service = new PriceHistoryRecorderService;
    $product = Product::factory()->create([
        'current_price' => 1200.00,
        'installment_price' => 1350.00,
        'in_stock' => true,
    ]);

    // Initial reading
    $first = $service->record($product, 1200.00, 1350.00, true);

    expect($first)->not->toBeNull()
        ->and((float) $first->price)->toBe(1200.00)
        ->and((float) $first->installment_price)->toBe(1350.00)
        ->and($first->in_stock)->toBeTrue()
        ->and($product->priceHistories()->count())->toBe(1);

    // Duplicate reading: identical cash price, installment price, and stock
    $duplicate = $service->record($product, 1200.00, 1350.00, true);

    expect($duplicate)->toBeNull()
        ->and($product->priceHistories()->count())->toBe(1);

    // Variation in cash price
    $newCashPrice = $service->record($product, 1150.00, 1350.00, true);

    expect($newCashPrice)->not->toBeNull()
        ->and((float) $newCashPrice->price)->toBe(1150.00)
        ->and($product->priceHistories()->count())->toBe(2);

    // Variation in installment price
    $newInstallment = $service->record($product, 1150.00, 1290.00, true);

    expect($newInstallment)->not->toBeNull()
        ->and((float) $newInstallment->installment_price)->toBe(1290.00)
        ->and($product->priceHistories()->count())->toBe(3);

    // Variation in stock availability
    $outOfStock = $service->record($product, 1150.00, 1290.00, false);

    expect($outOfStock)->not->toBeNull()
        ->and($outOfStock->in_stock)->toBeFalse()
        ->and($product->priceHistories()->count())->toBe(4);
});

test('ProductAnalyticsService calculates lowest price, average price and savings percentage (RF-014, RF-015)', function () {
    $service = new ProductAnalyticsService;
    $product = Product::factory()->create([
        'target_price' => 1000.00,
        'current_price' => 800.00,
    ]);

    // Create price history points: 1200, 1000, 800 (all within last 10 days)
    PriceHistory::factory()->create([
        'product_id' => $product->id,
        'price' => 1200.00,
        'installment_price' => 1300.00,
        'in_stock' => true,
        'recorded_at' => Carbon::now()->subDays(10),
    ]);

    PriceHistory::factory()->create([
        'product_id' => $product->id,
        'price' => 1000.00,
        'installment_price' => 1100.00,
        'in_stock' => true,
        'recorded_at' => Carbon::now()->subDays(5),
    ]);

    PriceHistory::factory()->create([
        'product_id' => $product->id,
        'price' => 800.00,
        'installment_price' => 900.00,
        'in_stock' => true,
        'recorded_at' => Carbon::now()->subDays(1),
    ]);

    // Older record outside 30 days (45 days ago) with lowest all-time price
    PriceHistory::factory()->create([
        'product_id' => $product->id,
        'price' => 750.00,
        'installment_price' => 850.00,
        'in_stock' => true,
        'recorded_at' => Carbon::now()->subDays(45),
    ]);

    // 30 days analytics
    $analytics30 = $service->getAnalytics($product, 30);

    expect($analytics30['period_days'])->toBe(30)
        ->and($analytics30['all_time_lowest_price'])->toBe(750.00) // All time lowest (45 days ago)
        ->and($analytics30['period_lowest_price'])->toBe(800.00)   // Period lowest in 30 days
        ->and($analytics30['period_highest_price'])->toBe(1200.00)
        ->and($analytics30['period_average_price'])->toBe(1000.00) // (1200 + 1000 + 800) / 3 = 1000.00
        ->and($analytics30['savings_percentage'])->toBe(20.0)      // (1000 - 800) / 1000 * 100 = 20.0%
        ->and(count($analytics30['history']))->toBe(3);

    // 90 days analytics (includes the 45-day record)
    $analytics90 = $service->getAnalytics($product, 90);

    expect($analytics90['period_days'])->toBe(90)
        ->and(count($analytics90['history']))->toBe(4)
        ->and($analytics90['period_lowest_price'])->toBe(750.00);

    // 7 days analytics (only 5-day and 1-day records)
    $analytics7 = $service->getAnalytics($product, 7);

    expect($analytics7['period_days'])->toBe(7)
        ->and(count($analytics7['history']))->toBe(2);
});

test('CollectProductPriceJob records price history when price changes (RN-007, RF-013)', function () {
    $product = Product::factory()->create([
        'domain' => 'kabum.com.br',
        'normalized_url' => 'https://kabum.com.br/produto/123',
        'current_price' => 500.00,
        'installment_price' => 550.00,
        'in_stock' => true,
        'status' => ProductStatus::Active,
    ]);

    PriceHistory::factory()->create([
        'product_id' => $product->id,
        'price' => 500.00,
        'installment_price' => 550.00,
        'in_stock' => true,
        'recorded_at' => Carbon::now()->subHours(2),
    ]);

    $scraper = Mockery::mock(ProductScraperInterface::class);
    $scraper->shouldReceive('scrape')
        ->once()
        ->andReturn(new ScrapedProductData(
            title: 'Headset Gamer',
            imageUrl: 'https://images.kabum.com.br/headset.jpg',
            cashPrice: 450.00, // Price dropped!
            installmentPrice: 500.00,
            inStock: true,
        ));

    $scraperFactory = Mockery::mock(ScraperFactory::class);
    $scraperFactory->shouldReceive('make')
        ->with('kabum.com.br')
        ->once()
        ->andReturn($scraper);

    $job = new CollectProductPriceJob($product);
    $job->handle($scraperFactory);

    $product->refresh();
    expect((float) $product->current_price)->toBe(450.00)
        ->and($product->priceHistories()->count())->toBe(2)
        ->and((float) $product->priceHistories()->latest('id')->first()->price)->toBe(450.00);
});

test('authenticated user can view product details, price history and analytics (US-004, RF-014, RF-015)', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'user_id' => $user->id,
        'category' => ProductCategory::Hardware,
        'target_price' => 1500.00,
        'current_price' => 1200.00,
    ]);

    PriceHistory::factory()->create([
        'product_id' => $product->id,
        'price' => 1350.00,
        'recorded_at' => Carbon::now()->subDays(15),
    ]);

    PriceHistory::factory()->create([
        'product_id' => $product->id,
        'price' => 1200.00,
        'recorded_at' => Carbon::now()->subDays(2),
    ]);

    $response = $this->actingAs($user)
        ->get(route('products.show', ['product' => $product->id, 'period' => 30]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('products/Show')
        ->has('product', fn (Assert $prop) => $prop
            ->where('id', $product->id)
            ->where('target_price', fn ($val) => (float) $val === 1500.0)
            ->where('current_price', fn ($val) => (float) $val === 1200.0)
            ->etc()
        )
        ->has('analytics', fn (Assert $prop) => $prop
            ->where('period_days', 30)
            ->where('all_time_lowest_price', fn ($val) => (float) $val === 1200.0)
            ->has('history', 2)
            ->etc()
        )
    );
});

test('unauthorized users cannot view product details', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();

    $product = Product::factory()->create([
        'user_id' => $owner->id,
    ]);

    // Guest redirected to login
    $guestResponse = $this->get(route('products.show', $product));
    $guestResponse->assertRedirect(route('login'));

    // Other user forbidden (403)
    $attackerResponse = $this->actingAs($attacker)
        ->get(route('products.show', $product));
    $attackerResponse->assertForbidden();
});
