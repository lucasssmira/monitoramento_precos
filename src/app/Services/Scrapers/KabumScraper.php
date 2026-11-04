<?php

namespace App\Services\Scrapers;

class KabumScraper extends AbstractHttpScraper
{
    public function domain(): string
    {
        return 'kabum.com.br';
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
