<?php

use App\Enums\ProductStatus;
use App\Jobs\CollectProductPriceJob;
use App\Models\Product;
use App\Services\Scrapers\ScraperFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('CollectProductPriceJob scrapes product metadata and updates product state to active', function () {
    Http::fake([
        'https://pichau.com.br/*' => Http::response(
            <<<'HTML'
            <html>
            <body>
                <h1 data-cy="product-page-title">Fonte 750W 80 Plus Gold Modular</h1>
                <img data-cy="product-image" src="https://media.pichau.com.br/fonte-750w.jpg" />
                <div data-cy="price-avista">R$ 549,90</div>
                <div data-cy="price-parcelado">R$ 646,94</div>
            </body>
            </html>
            HTML,
            200
        ),
    ]);

    $product = Product::factory()->create([
        'url' => 'https://www.pichau.com.br/fonte-750w-gold',
        'normalized_url' => 'https://pichau.com.br/fonte-750w-gold',
        'domain' => 'pichau.com.br',
        'title' => null,
        'image_url' => null,
        'current_price' => null,
        'installment_price' => null,
        'status' => ProductStatus::Pending,
    ]);

    $job = new CollectProductPriceJob($product);
    $job->handle(new ScraperFactory);

    $product->refresh();

    expect($product->title)->toBe('Fonte 750W 80 Plus Gold Modular')
        ->and($product->image_url)->toBe('https://media.pichau.com.br/fonte-750w.jpg')
        ->and((float) $product->current_price)->toBe(549.90)
        ->and((float) $product->installment_price)->toBe(646.94)
        ->and($product->in_stock)->toBeTrue()
        ->and($product->status)->toBe(ProductStatus::Active)
        ->and($product->last_checked_at)->not->toBeNull();
});
