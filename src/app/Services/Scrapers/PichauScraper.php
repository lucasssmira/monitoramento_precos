<?php

namespace App\Services\Scrapers;

use Illuminate\Http\Client\Response;

class PichauScraper extends AbstractHttpScraper
{
    private const SSR_USER_AGENTS = [
        'WhatsApp/2.24.16.78 A',
        'Twitterbot/1.0',
        'TelegramBot (like TwitterBot)',
    ];

    public function domain(): string
    {
        return 'pichau.com.br';
    }

    protected function fetchHtml(string $url): Response
    {
        $response = null;

        foreach (self::SSR_USER_AGENTS as $userAgent) {
            $headers = $this->requestHeaders();
            $headers['User-Agent'] = $userAgent;

            $response = $this->sendGetRequest($url, $headers);

            if ($response->successful()) {
                return $response;
            }
        }

        return $response;
    }

    protected function titleSelectors(): array
    {
        return [
            '[data-cy="product-page-title"]',
            'h1[data-testid="product-title"]',
            'h1',
        ];
    }

    protected function imageSelectors(): array
    {
        return [
            '[data-cy="product-image"]',
            '[data-testid="product-image"]',
        ];
    }

    protected function cashPriceSelectors(): array
    {
        return [
            '[data-cy="price-avista"]',
            '.price-avista',
            '[data-testid="cash-price"]',
            '[class*="price_vista"]',
            'meta[name="product:price:amount"]',
        ];
    }

    protected function installmentPriceSelectors(): array
    {
        return [
            '[data-cy="price-parcelado"]',
            '.price-parcelado',
            '[data-testid="installment-price"]',
            '[class*="price_total"]',
            '[class*="price_parcelado"]',
        ];
    }

    protected function outOfStockSelectors(): array
    {
        return [
            '[data-cy="out-of-stock"]',
            '[data-testid="out-of-stock"]',
        ];
    }

    protected function outOfStockPhrases(): array
    {
        return [
            'produto indisponível',
            'produto esgotado',
            'sem estoque',
        ];
    }
}
