<?php

namespace App\Services\Scrapers;

class MagaluScraper extends AbstractHttpScraper
{
    public function domain(): string
    {
        return 'magazineluiza.com.br';
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
