<?php

namespace App\Services\Scrapers;

class PichauScraper extends AbstractHttpScraper
{
    public function domain(): string
    {
        return 'pichau.com.br';
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
        ];
    }

    protected function installmentPriceSelectors(): array
    {
        return [
            '[data-cy="price-parcelado"]',
            '.price-parcelado',
            '[data-testid="installment-price"]',
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
