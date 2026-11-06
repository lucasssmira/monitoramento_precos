<?php

use App\Enums\ProductCategory;
use App\Enums\ProductStatus;
use App\Jobs\CollectProductPriceJob;
use App\Models\Product;
use App\Models\User;
use App\Services\Scrapers\ScraperFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('scrape-active command dispatches active and unstable products interleaved by domain in round-robin order', function () {
    Queue::fake([CollectProductPriceJob::class]);

    $user = User::factory()->create();

    $kabum1 = Product::factory()->create([
        'user_id' => $user->id,
        'domain' => 'kabum.com.br',
        'status' => ProductStatus::Active,
    ]);

    $kabum2 = Product::factory()->create([
        'user_id' => $user->id,
        'domain' => 'kabum.com.br',
        'status' => ProductStatus::Active,
    ]);

    $amazon1 = Product::factory()->create([
        'user_id' => $user->id,
        'domain' => 'amazon.com.br',
        'status' => ProductStatus::Unstable,
    ]);

    $pichauPaused = Product::factory()->create([
        'user_id' => $user->id,
        'domain' => 'pichau.com.br',
        'status' => ProductStatus::Paused,
    ]);

    $exitCode = Artisan::call('products:scrape-active');

    expect($exitCode)->toBe(0);

    Queue::assertPushed(CollectProductPriceJob::class, 3);
    Queue::assertNotPushed(CollectProductPriceJob::class, function (CollectProductPriceJob $job) use ($pichauPaused) {
        return $job->product->id === $pichauPaused->id;
    });

    $pushedProducts = [];
    Queue::assertPushed(CollectProductPriceJob::class, function (CollectProductPriceJob $job) use (&$pushedProducts) {
        $pushedProducts[] = $job->product;

        return true;
    });

    // Verify round-robin interleaving: first element of each domain, then second
    $firstTwoDomains = array_slice(array_map(fn (Product $p) => $p->domain, $pushedProducts), 0, 2);
    expect(count(array_unique($firstTwoDomains)))->toBe(2);
});

test('job defines 3 tries, exponential backoff, rate limited middleware and marks product unstable on failure', function () {
    Log::shouldReceive('error')
        ->once()
        ->with('product.scrape.exhausted', Mockery::on(function ($context) {
            return isset($context['product_id']) && $context['domain'] === 'amazon.com.br';
        }));

    $product = Product::factory()->create([
        'domain' => 'amazon.com.br',
        'status' => ProductStatus::Active,
    ]);

    $job = new CollectProductPriceJob($product);

    expect($job->tries)->toBe(3)
        ->and($job->backoff())->toBe([120, 240, 480])
        ->and($job->middleware())->toHaveCount(1)
        ->and($job->middleware()[0])->toBeInstanceOf(RateLimited::class);

    $job->failed(new RuntimeException('Connection timed out'));

    $product->refresh();
    expect($product->status)->toBe(ProductStatus::Unstable);
});

test('job skips execution if product is paused', function () {
    $product = Product::factory()->create([
        'domain' => 'kabum.com.br',
        'status' => ProductStatus::Paused,
    ]);

    $scraperFactory = Mockery::mock(ScraperFactory::class);
    $scraperFactory->shouldNotReceive('make');

    $job = new CollectProductPriceJob($product);
    $job->handle($scraperFactory);

    $product->refresh();
    expect($product->status)->toBe(ProductStatus::Paused);
});

test('authenticated owner can trigger manual price check when cooldown has elapsed', function () {
    Queue::fake([CollectProductPriceJob::class]);

    $user = User::factory()->create();
    $product = Product::factory()->create([
        'user_id' => $user->id,
        'category' => ProductCategory::Hardware,
        'status' => ProductStatus::Active,
        'last_manual_check_at' => Carbon::now()->subMinutes(15),
    ]);

    $response = $this->actingAs($user)
        ->post(route('products.check-now', $product));

    $response->assertRedirect(route('products.index'));
    $response->assertSessionHas('status', 'Coleta sob demanda iniciada. Atualizando dados do produto...');

    Queue::assertPushed(CollectProductPriceJob::class, function (CollectProductPriceJob $job) use ($product) {
        return $job->product->id === $product->id;
    });

    $product->refresh();
    expect($product->last_manual_check_at)->not->toBeNull()
        ->and($product->last_manual_check_at->greaterThan(Carbon::now()->subMinute()))->toBeTrue();
});

test('manual price check is blocked during the 10-minute cooldown', function () {
    Queue::fake([CollectProductPriceJob::class]);

    $user = User::factory()->create();
    $product = Product::factory()->create([
        'user_id' => $user->id,
        'status' => ProductStatus::Active,
        'last_manual_check_at' => Carbon::now()->subMinutes(4),
    ]);

    $response = $this->actingAs($user)
        ->from(route('products.index'))
        ->post(route('products.check-now', $product));

    $response->assertRedirect(route('products.index'));
    $response->assertSessionHasErrors(['manual_check']);

    Queue::assertNotPushed(CollectProductPriceJob::class);
});

test('unauthorized users cannot trigger manual price check', function () {
    Queue::fake([CollectProductPriceJob::class]);

    $owner = User::factory()->create();
    $attacker = User::factory()->create();

    $product = Product::factory()->create([
        'user_id' => $owner->id,
        'status' => ProductStatus::Active,
    ]);

    // Guest is redirected to login
    $guestResponse = $this->post(route('products.check-now', $product));
    $guestResponse->assertRedirect(route('login'));

    // Another user gets 403 Forbidden
    $unauthorizedResponse = $this->actingAs($attacker)
        ->post(route('products.check-now', $product));
    $unauthorizedResponse->assertForbidden();

    Queue::assertNotPushed(CollectProductPriceJob::class);
});
