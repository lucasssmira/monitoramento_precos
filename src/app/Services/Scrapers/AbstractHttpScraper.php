<?php

namespace App\Services\Scrapers;

use App\Contracts\ProductScraperInterface;
use App\DataTransferObjects\ScrapedProductData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractHttpScraper implements ProductScraperInterface
{
    /**
     * Pool of desktop and mobile User-Agent strings for rotation (RNF-005).
     *
     * @var list<string>
     */
    private const USER_AGENTS = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_7_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64; rv:133.0) Gecko/20100101 Firefox/133.0',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0',
    ];

    public function __construct(
        protected readonly JsonLdParser $jsonLdParser = new JsonLdParser,
    ) {}

    public function scrape(string $url, ?int $productId = null): ScrapedProductData
    {
        $startedAt = hrtime(true);

        $response = Http::withHeaders([
            'User-Agent' => $this->randomUserAgent(),
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control' => 'no-cache',
        ])
            ->timeout(15)
            ->get($url);

        $durationMs = (int) round((hrtime(true) - $startedAt) / 1_000_000);
        $httpStatus = $response->status();

        if ($response->failed()) {
            Log::warning('product.scrape.failed', [
                'product_id' => $productId,
                'url' => $url,
                'domain' => $this->domain(),
                'duration_ms' => $durationMs,
                'http_status' => $httpStatus,
                'extraction_status' => 'http_error',
            ]);

            throw new RuntimeException("Falha HTTP {$httpStatus} ao coletar dados de {$this->domain()}.");
        }

        $html = $response->body();
        $result = $this->extractFromHtml($html, $httpStatus, $durationMs);

        $crawler = new Crawler($html);
        $jsonLd = $this->jsonLdParser->extract($crawler);
        $hasValidData = $result->title !== ''
            && $result->title !== 'Produto sem título'
            && ($result->cashPrice !== null || $this->hasExplicitOutOfStockSignal($crawler, $jsonLd['inStock']));

        if (! $hasValidData) {
            Log::warning('product.scrape.failed', [
                'product_id' => $productId,
                'url' => $url,
                'domain' => $this->domain(),
                'duration_ms' => $durationMs,
                'http_status' => $httpStatus,
                'extraction_status' => 'selector_failure',
            ]);

            throw new RuntimeException("Não foi possível extrair os dados do produto em {$this->domain()} (bloqueio anti-bot ou estrutura alterada).");
        }

        Log::info('product.scrape.completed', [
            'product_id' => $productId,
            'url' => $url,
            'domain' => $this->domain(),
            'duration_ms' => $durationMs,
            'http_status' => $httpStatus,
            'extraction_status' => 'success',
            'in_stock' => $result->inStock,
            'cash_price' => $result->cashPrice,
            'installment_price' => $result->installmentPrice,
        ]);

        return $result;
    }

    public function extractFromHtml(string $html, int $httpStatus = 200, int $durationMs = 0): ScrapedProductData
    {
        $crawler = new Crawler($html);
        $jsonLd = $this->jsonLdParser->extract($crawler);
        $structured = $this->extractStructuredFromDom($crawler);

        $title = $this->extractFirstText($crawler, $this->titleSelectors())
            ?? $jsonLd['title']
            ?? $this->extractFirstMeta($crawler, ['meta[property="og:title"]', 'title'])
            ?? 'Produto sem título';

        $imageUrl = $this->extractFirstAttribute($crawler, $this->imageSelectors(), ['src', 'data-old-hires', 'content'])
            ?? $jsonLd['imageUrl']
            ?? $this->extractFirstMeta($crawler, ['meta[property="og:image"]']);

        $domCashPrice = $this->extractPriceFromSelectors($crawler, $this->cashPriceSelectors());
        $domInstallmentPrice = $this->extractPriceFromSelectors($crawler, $this->installmentPriceSelectors());

        $cashPrice = $domCashPrice ?? $structured['cashPrice'] ?? $jsonLd['cashPrice'];
        $installmentPrice = $domInstallmentPrice ?? $structured['installmentPrice'] ?? $jsonLd['installmentPrice'];

        if ($cashPrice !== null && $installmentPrice !== null && $installmentPrice < $cashPrice) {
            [$cashPrice, $installmentPrice] = [$installmentPrice, $cashPrice];
        }

        $inStock = $this->determineStockAvailability($crawler, $jsonLd['inStock'], $cashPrice);

        return new ScrapedProductData(
            title: $title,
            imageUrl: $imageUrl,
            cashPrice: $cashPrice,
            installmentPrice: $installmentPrice,
            inStock: $inStock,
            currency: $jsonLd['currency'],
            httpStatus: $httpStatus,
            durationMs: $durationMs,
        );
    }

    /**
     * Extract store-specific structured JSON embedded in the DOM (e.g., __NEXT_DATA__).
     *
     * @return array{cashPrice: ?float, installmentPrice: ?float}
     */
    protected function extractStructuredFromDom(Crawler $crawler): array
    {
        return [
            'cashPrice' => null,
            'installmentPrice' => null,
        ];
    }

    /**
     * Select the random User-Agent header.
     */
    public function randomUserAgent(): string
    {
        return self::USER_AGENTS[array_rand(self::USER_AGENTS)];
    }

    /**
     * CSS selectors for product title in priority order.
     *
     * @return list<string>
     */
    abstract protected function titleSelectors(): array;

    /**
     * CSS selectors for product main image in priority order.
     *
     * @return list<string>
     */
    abstract protected function imageSelectors(): array;

    /**
     * CSS selectors for cash / PIX price in priority order.
     *
     * @return list<string>
     */
    abstract protected function cashPriceSelectors(): array;

    /**
     * CSS selectors for installment / card total price in priority order.
     *
     * @return list<string>
     */
    abstract protected function installmentPriceSelectors(): array;

    /**
     * CSS selectors that explicitly indicate out-of-stock status when present.
     *
     * @return list<string>
     */
    abstract protected function outOfStockSelectors(): array;

    /**
     * Substrings (case-insensitive) inside availability containers that indicate out-of-stock.
     *
     * @return list<string>
     */
    abstract protected function outOfStockPhrases(): array;

    /**
     * @param  list<string>  $selectors
     */
    protected function extractFirstText(Crawler $crawler, array $selectors): ?string
    {
        foreach ($selectors as $selector) {
            $nodes = $crawler->filter($selector);

            if ($nodes->count() > 0) {
                $text = trim($nodes->first()->text(''));

                if ($text !== '') {
                    return $text;
                }
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $selectors
     * @param  list<string>  $attributes
     */
    protected function extractFirstAttribute(Crawler $crawler, array $selectors, array $attributes): ?string
    {
        foreach ($selectors as $selector) {
            $nodes = $crawler->filter($selector);

            if ($nodes->count() === 0) {
                continue;
            }

            $node = $nodes->first();

            foreach ($attributes as $attribute) {
                $val = $node->attr($attribute);
                if (is_string($val) && trim($val) !== '') {
                    return trim($val);
                }
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $selectors
     */
    protected function extractFirstMeta(Crawler $crawler, array $selectors): ?string
    {
        foreach ($selectors as $selector) {
            $nodes = $crawler->filter($selector);

            if ($nodes->count() === 0) {
                continue;
            }

            $node = $nodes->first();
            $content = $node->attr('content') ?? $node->text('');

            if (trim($content) !== '') {
                return trim($content);
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $selectors
     */
    protected function extractPriceFromSelectors(Crawler $crawler, array $selectors): ?float
    {
        foreach ($selectors as $selector) {
            $nodes = $crawler->filter($selector);

            if ($nodes->count() === 0) {
                continue;
            }

            foreach ($nodes as $nodeElement) {
                $nodeCrawler = new Crawler($nodeElement);
                $rawText = $nodeCrawler->attr('content')
                    ?? $nodeCrawler->attr('data-price')
                    ?? $nodeCrawler->text('');

                $parsed = $this->jsonLdParser->parsePrice($rawText);

                if ($parsed !== null) {
                    return $parsed;
                }
            }
        }

        return null;
    }

    protected function determineStockAvailability(Crawler $crawler, ?bool $jsonLdStock, ?float $cashPrice): bool
    {
        foreach ($this->outOfStockSelectors() as $selector) {
            if ($crawler->filter($selector)->count() > 0) {
                return false;
            }
        }

        if ($jsonLdStock === false) {
            return false;
        }

        if ($jsonLdStock === true) {
            return true;
        }

        $bodyText = mb_strtolower($crawler->filter('body')->count() > 0 ? $crawler->filter('body')->text('') : '');

        foreach ($this->outOfStockPhrases() as $phrase) {
            if ($bodyText !== '' && str_contains($bodyText, mb_strtolower($phrase))) {
                return false;
            }
        }

        return $cashPrice !== null;
    }

    protected function hasExplicitOutOfStockSignal(Crawler $crawler, ?bool $jsonLdStock): bool
    {
        if ($jsonLdStock === false) {
            return true;
        }

        foreach ($this->outOfStockSelectors() as $selector) {
            if ($crawler->filter($selector)->count() > 0) {
                return true;
            }
        }

        $bodyText = mb_strtolower($crawler->filter('body')->count() > 0 ? $crawler->filter('body')->text('') : '');

        foreach ($this->outOfStockPhrases() as $phrase) {
            if ($bodyText !== '' && str_contains($bodyText, mb_strtolower($phrase))) {
                return true;
            }
        }

        return false;
    }
}
