<?php

namespace App\Services\Scrapers;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class MagaluScraper extends AbstractHttpScraper
{
    private const SSR_USER_AGENT = 'WhatsApp/2.24.16.78 A';

    public function domain(): string
    {
        return 'magazineluiza.com.br';
    }

    protected function fetchHtml(string $url): Response
    {
        $response = $this->requestWithSsrHeaders($url);

        if ($response->status() === 403) {
            $fallbackUrl = $this->buildMagazineVoceFallbackUrl($url);

            if ($fallbackUrl !== null) {
                return $this->requestWithSsrHeaders($fallbackUrl);
            }
        }

        return $response;
    }

    private function requestWithSsrHeaders(string $url): Response
    {
        return Http::withHeaders([
            'User-Agent' => self::SSR_USER_AGENT,
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'pt-BR,pt;q=0.9',
            'Cache-Control' => 'no-cache',
        ])
            ->timeout(15)
            ->get($url);
    }

    private function buildMagazineVoceFallbackUrl(string $url): ?string
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['host'])) {
            return null;
        }

        $host = strtolower($parts['host']);

        if (str_contains($host, 'magazinevoce.com.br')) {
            return null;
        }

        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) && $parts['query'] !== '' ? '?'.$parts['query'] : '';

        return 'https://www.magazinevoce.com.br/magazinevoce'.$path.$query;
    }

    protected function titleSelectors(): array
    {
        return [
            '[data-testid="heading-product-title"]',
            'h1[data-testid="product-title"]',
            'h1',
        ];
    }

    protected function imageSelectors(): array
    {
        return [
            '[data-testid="image-selected-thumbnail"]',
            '[data-testid="product-image"]',
        ];
    }

    protected function cashPriceSelectors(): array
    {
        return [
            '[data-testid="price-value"]',
            '[data-testid="cash-price"]',
        ];
    }

    protected function installmentPriceSelectors(): array
    {
        return [
            '[data-testid="installment-price"]',
            '[data-testid="price-original"]',
            '[data-testid="mod-bestinstallment"] p',
        ];
    }

    protected function outOfStockSelectors(): array
    {
        return [
            '[data-testid="let-me-know-button"]',
            '[data-testid="out-of-stock"]',
        ];
    }

    protected function outOfStockPhrases(): array
    {
        return [
            'não disponível',
            'produto indisponível',
            'avise-me quando chegar',
        ];
    }
}
