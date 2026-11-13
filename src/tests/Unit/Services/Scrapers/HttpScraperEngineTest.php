<?php

use App\Services\Scrapers\AmazonScraper;
use App\Services\Scrapers\KabumScraper;
use App\Services\Scrapers\MagaluScraper;
use App\Services\Scrapers\MercadoLivreScraper;
use App\Services\Scrapers\PichauScraper;
use App\Services\Scrapers\ScraperFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class);

describe('ScraperFactory', function () {
    it('resolves the correct adapter for each of the 5 homologated domains (RN-003)', function (string $input, string $expectedClass, string $expectedDomain) {
        $factory = new ScraperFactory;
        $scraper = $factory->make($input);

        expect($scraper)->toBeInstanceOf($expectedClass)
            ->and($scraper->domain())->toBe($expectedDomain);
    })->with([
        'Amazon domain' => ['amazon.com.br', AmazonScraper::class, 'amazon.com.br'],
        'Amazon URL' => ['https://www.amazon.com.br/dp/B08N5WRWNW', AmazonScraper::class, 'amazon.com.br'],
        'Kabum domain' => ['kabum.com.br', KabumScraper::class, 'kabum.com.br'],
        'Mercado Livre URL' => ['https://produto.mercadolivre.com.br/MLB-12345', MercadoLivreScraper::class, 'mercadolivre.com.br'],
        'Magalu domain' => ['magazineluiza.com.br', MagaluScraper::class, 'magazineluiza.com.br'],
        'Magazine Voce URL' => ['https://www.magazinevoce.com.br/loja/p/123', MagaluScraper::class, 'magazineluiza.com.br'],
        'Pichau domain' => ['pichau.com.br', PichauScraper::class, 'pichau.com.br'],
    ]);

    it('throws InvalidArgumentException with pt_BR message for unsupported domains', function () {
        $factory = new ScraperFactory;

        expect(fn () => $factory->make('americanas.com.br'))
            ->toThrow(InvalidArgumentException::class, 'O domínio informado ainda não é suportado pelo sistema.');
    });
});

describe('5 Homologated Store Adapters - In Stock Extraction (US-002 / RF-011)', function () {
    it('extracts cash price (PIX), installment price, title, image and stock from Amazon Brasil HTML/JSON-LD', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@type": "Product",
                "name": "Echo Dot 5ª Geração",
                "image": "https://m.media-amazon.com/images/I/echo-dot.jpg",
                "offers": {
                    "@type": "Offer",
                    "price": "349.00",
                    "priceCurrency": "BRL",
                    "availability": "https://schema.org/InStock"
                }
            }
            </script>
        </head>
        <body>
            <span id="productTitle">  Echo Dot 5ª Geração Amazon  </span>
            <img id="landingImage" src="https://m.media-amazon.com/images/I/echo-dot-hd.jpg" />
            <div id="corePriceDisplay_desktop_feature_div">
                <span class="priceToPay"><span class="a-offscreen">R$ 314,10</span></span>
                <span class="basisPrice"><span class="a-offscreen">R$ 349,00</span></span>
            </div>
        </body>
        </html>
        HTML;

        $data = (new AmazonScraper)->extractFromHtml($html, 200, 145);

        expect($data->title)->toBe('Echo Dot 5ª Geração Amazon')
            ->and($data->imageUrl)->toBe('https://m.media-amazon.com/images/I/echo-dot-hd.jpg')
            ->and($data->cashPrice)->toBe(314.10)
            ->and($data->installmentPrice)->toBe(349.00)
            ->and($data->inStock)->toBeTrue()
            ->and($data->currency)->toBe('BRL')
            ->and($data->httpStatus)->toBe(200)
            ->and($data->durationMs)->toBe(145);
    });

    it('extracts cash price (PIX), installment price, title, image and stock from Kabum HTML/JSON-LD', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@type": "Product",
                "name": "Placa de Vídeo RTX 4060 8GB",
                "image": ["https://images.kabum.com.br/produtos/rtx4060.jpg"],
                "offers": {
                    "@type": "Offer",
                    "price": 1899.90,
                    "priceCurrency": "BRL",
                    "availability": "https://schema.org/InStock"
                }
            }
            </script>
        </head>
        <body>
            <h1 itemprop="name">Placa de Vídeo RTX 4060 8GB GDDR6</h1>
            <h4 class="finalPrice">R$ 1.899,90</h4>
            <b class="regularPrice">R$ 2.199,90</b>
        </body>
        </html>
        HTML;

        $data = (new KabumScraper)->extractFromHtml($html);

        expect($data->title)->toBe('Placa de Vídeo RTX 4060 8GB GDDR6')
            ->and($data->imageUrl)->toBe('https://images.kabum.com.br/produtos/rtx4060.jpg')
            ->and($data->cashPrice)->toBe(1899.90)
            ->and($data->installmentPrice)->toBe(2199.90)
            ->and($data->inStock)->toBeTrue()
            ->and($data->currency)->toBe('BRL');
    });

    it('extracts cash price (PIX), installment price, title, image and stock from Mercado Livre HTML/JSON-LD', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@type": "Product",
                "name": "Monitor Gamer 27 IPS 180Hz",
                "image": "https://http2.mlstatic.com/D_NQ_NP_monitor.webp",
                "offers": {
                    "@type": "AggregateOffer",
                    "lowPrice": 999.00,
                    "highPrice": 1149.00,
                    "priceCurrency": "BRL",
                    "availability": "https://schema.org/InStock"
                }
            }
            </script>
        </head>
        <body>
            <h1 class="ui-pdp-title">Monitor Gamer 27 IPS 180Hz</h1>
            <div data-testid="cash-price">R$ 999,00</div>
            <div data-testid="installment-price">R$ 1.149,00</div>
        </body>
        </html>
        HTML;

        $data = (new MercadoLivreScraper)->extractFromHtml($html);

        expect($data->title)->toBe('Monitor Gamer 27 IPS 180Hz')
            ->and($data->imageUrl)->toBe('https://http2.mlstatic.com/D_NQ_NP_monitor.webp')
            ->and($data->cashPrice)->toBe(999.00)
            ->and($data->installmentPrice)->toBe(1149.00)
            ->and($data->inStock)->toBeTrue();
    });

    it('extracts cash price (PIX), installment price, title, image and stock from Magalu HTML/JSON-LD', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@type": "Product",
                "name": "Smartphone Samsung Galaxy S24 256GB",
                "image": "https://a-static.mlcdn.com.br/galaxy-s24.jpg",
                "offers": {
                    "@type": "Offer",
                    "price": "3599.10",
                    "priceCurrency": "BRL",
                    "availability": "https://schema.org/InStock"
                }
            }
            </script>
        </head>
        <body>
            <h1 data-testid="heading-product-title">Smartphone Samsung Galaxy S24 256GB</h1>
            <p data-testid="price-value">R$ 3.599,10</p>
            <p data-testid="price-original">R$ 3.999,00</p>
        </body>
        </html>
        HTML;

        $data = (new MagaluScraper)->extractFromHtml($html);

        expect($data->title)->toBe('Smartphone Samsung Galaxy S24 256GB')
            ->and($data->imageUrl)->toBe('https://a-static.mlcdn.com.br/galaxy-s24.jpg')
            ->and($data->cashPrice)->toBe(3599.10)
            ->and($data->installmentPrice)->toBe(3999.00)
            ->and($data->inStock)->toBeTrue();
    });

    it('extracts cash price (PIX), installment price, title, image and stock from Pichau HTML/JSON-LD', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html lang="pt-BR">
        <body>
            <h1 data-cy="product-page-title">Processador AMD Ryzen 7 5700X3D</h1>
            <img data-cy="product-image" src="https://media.pichau.com.br/ryzen-7-5700x3d.jpg" />
            <div data-cy="price-avista">R$ 1.349,99</div>
            <div data-cy="price-parcelado">R$ 1.588,22</div>
        </body>
        </html>
        HTML;

        $data = (new PichauScraper)->extractFromHtml($html);

        expect($data->title)->toBe('Processador AMD Ryzen 7 5700X3D')
            ->and($data->imageUrl)->toBe('https://media.pichau.com.br/ryzen-7-5700x3d.jpg')
            ->and($data->cashPrice)->toBe(1349.99)
            ->and($data->installmentPrice)->toBe(1588.22)
            ->and($data->inStock)->toBeTrue();
    });
});

describe('Out of Stock Identification (BDD Scenario 2)', function () {
    it('identifies out-of-stock products while preserving product identification metadata', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@type": "Product",
                "name": "SSD NVMe 2TB PCIe 4.0",
                "image": "https://images.kabum.com.br/produtos/ssd-2tb.jpg",
                "offers": {
                    "@type": "Offer",
                    "price": "799.90",
                    "priceCurrency": "BRL",
                    "availability": "https://schema.org/OutOfStock"
                }
            }
            </script>
        </head>
        <body>
            <h1 itemprop="name">SSD NVMe 2TB PCIe 4.0</h1>
            <div id="formularioAviseMe">Produto indisponível no momento</div>
        </body>
        </html>
        HTML;

        $data = (new KabumScraper)->extractFromHtml($html);

        expect($data->inStock)->toBeFalse()
            ->and($data->title)->toBe('SSD NVMe 2TB PCIe 4.0')
            ->and($data->imageUrl)->toBe('https://images.kabum.com.br/produtos/ssd-2tb.jpg');
    });
});

describe('HTTP Execution and Structured Logging (RNF-005 / RNF-008)', function () {
    it('executes HTTP GET with User-Agent header and records structured telemetry log', function () {
        Log::spy();

        Http::fake([
            'https://kabum.com.br/*' => Http::response(
                '<html><body><h1 itemprop="name">Mouse Gamer Sem Fio</h1><h4 class="finalPrice">R$ 299,90</h4></body></html>',
                200
            ),
        ]);

        $scraper = new KabumScraper;
        $result = $scraper->scrape('https://kabum.com.br/produto/999/mouse-gamer', 42);

        expect($result->title)->toBe('Mouse Gamer Sem Fio')
            ->and($result->cashPrice)->toBe(299.90)
            ->and($result->inStock)->toBeTrue()
            ->and($result->httpStatus)->toBe(200);

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(fn (string $event, array $context): bool => $event === 'product.scrape.completed'
                && $context['product_id'] === 42
                && $context['domain'] === 'kabum.com.br'
                && $context['http_status'] === 200
                && $context['extraction_status'] === 'success'
            );
    });

    it('extracts installment price from Kabum __NEXT_DATA__ payload when regularPrice selector is absent', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <script id="__NEXT_DATA__" type="application/json">
            {
                "props": {
                    "pageProps": {
                        "product": {
                            "prices": {
                                "oldPrice": 7999,
                                "priceWithDiscount": 4994.10,
                                "price": 5549.00,
                                "discountPercentage": 10
                            }
                        }
                    }
                }
            }
            </script>
        </head>
        <body>
            <h1 itemprop="name">Notebook Gamer Acer Nitro V15 RTX 4050</h1>
            <h4 class="finalPrice">R$ 4.994,10</h4>
        </body>
        </html>
        HTML;

        $data = (new KabumScraper)->extractFromHtml($html);

        expect($data->title)->toBe('Notebook Gamer Acer Nitro V15 RTX 4050')
            ->and($data->cashPrice)->toBe(4994.10)
            ->and($data->installmentPrice)->toBe(5549.00)
            ->and($data->inStock)->toBeTrue();
    });

    it('extracts Amazon total installment price from highlight-total-line-desktop even when equal to cash price', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html lang="pt-BR">
        <body>
            <span id="productTitle">Notebook Acer Nitro V 15</span>
            <div id="corePriceDisplay_desktop_feature_div">
                <span class="priceToPay"><span class="a-offscreen">R$ 5.299,00</span></span>
            </div>
            <span class="highlight-total-line-desktop">(total parcelado R$ 5.299,00)</span>
        </body>
        </html>
        HTML;

        $data = (new AmazonScraper)->extractFromHtml($html);

        expect($data->title)->toBe('Notebook Acer Nitro V 15')
            ->and($data->cashPrice)->toBe(5299.00)
            ->and($data->installmentPrice)->toBe(5299.00)
            ->and($data->inStock)->toBeTrue();
    });

    it('throws RuntimeException and logs selector_failure when HTTP 200 returns an anti-bot challenge page without price or stock signals', function () {
        Log::spy();

        Http::fake([
            'https://mercadolivre.com.br/*' => Http::response(
                '<!DOCTYPE html><html lang="pt-BR"><head><title>Mercado Libre</title></head><body><div id="root">Verifique sua conta</div></body></html>',
                200
            ),
        ]);

        $scraper = new MercadoLivreScraper;

        expect(fn () => $scraper->scrape('https://mercadolivre.com.br/p/MLB67293164', 99))
            ->toThrow(RuntimeException::class);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn (string $event, array $context): bool => $event === 'product.scrape.failed'
                && $context['product_id'] === 99
                && $context['domain'] === 'mercadolivre.com.br'
                && $context['extraction_status'] === 'selector_failure'
            );
    });

    it('falls back to magazinevoce mirror when magazineluiza returns HTTP 403', function () {
        Http::fake([
            'https://magazineluiza.com.br/*' => Http::response('Forbidden', 403),
            'https://www.magazinevoce.com.br/magazinevoce/*' => Http::response(
                '<!DOCTYPE html><html lang="pt-BR"><body><h1 data-testid="heading-product-title">Notebook Gamer Acer Nitro V 15</h1><p data-testid="price-value">R$ 5.183,80</p><div data-testid="mod-bestinstallment"><p>R$ 5.759,78</p></div></body></html>',
                200
            ),
        ]);

        $scraper = new MagaluScraper;
        $data = $scraper->scrape('https://magazineluiza.com.br/notebook-gamer/p/bhe08d34k4/in/nota?seller_id=aceroficial', 7);

        expect($data->title)->toBe('Notebook Gamer Acer Nitro V 15')
            ->and($data->cashPrice)->toBe(5183.80)
            ->and($data->installmentPrice)->toBe(5759.78)
            ->and($data->inStock)->toBeTrue();
    });
});
