<?php

namespace App\Services\Scrapers;

use Symfony\Component\DomCrawler\Crawler;

class KabumScraper extends AbstractHttpScraper
{
    public function domain(): string
    {
        return 'kabum.com.br';
    }

    /**
     * Extract cash and installment prices from KaBuM's Next.js hydration payload (__NEXT_DATA__).
     *
     * @return array{cashPrice: ?float, installmentPrice: ?float}
     */
    protected function extractStructuredFromDom(Crawler $crawler): array
    {
        $nextDataNode = $crawler->filter('script#__NEXT_DATA__');

        if ($nextDataNode->count() === 0) {
            return [
                'cashPrice' => null,
                'installmentPrice' => null,
            ];
        }

        $decoded = json_decode(trim($nextDataNode->first()->text('')), true);

        if (! is_array($decoded)) {
            return [
                'cashPrice' => null,
                'installmentPrice' => null,
            ];
        }

        $prices = $decoded['props']['pageProps']['product']['prices'] ?? null;

        if (! is_array($prices)) {
            return [
                'cashPrice' => null,
                'installmentPrice' => null,
            ];
        }

        $cashPrice = isset($prices['priceWithDiscount'])
            ? $this->jsonLdParser->parsePrice($prices['priceWithDiscount'])
            : null;
        $installmentPrice = isset($prices['price'])
            ? $this->jsonLdParser->parsePrice($prices['price'])
            : null;

        return [
            'cashPrice' => $cashPrice,
            'installmentPrice' => $installmentPrice,
        ];
    }

    protected function titleSelectors(): array
    {
        return [
            'h1[itemprop="name"]',
            '[data-testid="product-title"]',
            'h1',
        ];
    }

    protected function imageSelectors(): array
    {
        return [
            '#carouselDetails img',
            '[data-testid="product-image"]',
            'figure img',
        ];
    }

    protected function cashPriceSelectors(): array
    {
        return [
            'h4.finalPrice',
            '.finalPrice',
            '[data-testid="price-value"]',
            '[data-testid="cash-price"]',
        ];
    }

    protected function installmentPriceSelectors(): array
    {
        return [
            'b.regularPrice',
            '.regularPrice',
            '[data-testid="installment-price"]',
        ];
    }

    protected function outOfStockSelectors(): array
    {
        return [
            '#formularioAviseMe',
            '[data-testid="out-of-stock"]',
        ];
    }

    protected function outOfStockPhrases(): array
    {
        return [
            'produto indisponível',
            'avise-me quando chegar',
            'esgotado',
        ];
    }
}
