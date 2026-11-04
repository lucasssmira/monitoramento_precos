<?php

namespace App\Services\Scrapers;

class MercadoLivreScraper extends AbstractHttpScraper
{
    public function domain(): string
    {
        return 'mercadolivre.com.br';
    }

    protected function titleSelectors(): array
    {
        return [
            'h1.ui-pdp-title',
            '[data-testid="product-title"]',
            'h1',
        ];
    }

    protected function imageSelectors(): array
    {
        return [
            'img.ui-pdp-gallery__figure__image',
            '.ui-pdp-image',
        ];
    }

    protected function cashPriceSelectors(): array
    {
        return [
            '[data-testid="cash-price"]',
            '.ui-pdp-price__second-line .andes-money-amount',
            'meta[itemprop="price"]',
            '.andes-money-amount__fraction',
        ];
    }

    protected function installmentPriceSelectors(): array
    {
        return [
            '[data-testid="installment-price"]',
            '.ui-pdp-price__original-value .andes-money-amount',
            '.ui-pdp-price__subtitles .andes-money-amount',
        ];
    }

    protected function outOfStockSelectors(): array
    {
        return [
            '.ui-pdp-promotions-pill-label--paused',
            '[data-testid="out-of-stock"]',
        ];
    }

    protected function outOfStockPhrases(): array
    {
        return [
            'anúncio pausado',
            'estoque esgotado',
            'produto sem estoque',
        ];
    }
}
