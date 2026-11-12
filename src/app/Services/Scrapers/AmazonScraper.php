<?php

namespace App\Services\Scrapers;

class AmazonScraper extends AbstractHttpScraper
{
    public function domain(): string
    {
        return 'amazon.com.br';
    }

    protected function titleSelectors(): array
    {
        return [
            '#productTitle',
            'h1#title span',
            '[data-testid="product-title"]',
        ];
    }

    protected function imageSelectors(): array
    {
        return [
            '#landingImage',
            '#imgBlkFront',
            '#main-image-container img',
        ];
    }

    protected function cashPriceSelectors(): array
    {
        return [
            '[data-testid="cash-price"]',
            '#corePriceDisplay_desktop_feature_div .priceToPay .a-offscreen',
            '#corePrice_feature_div .a-price .a-offscreen',
            '#priceblock_ourprice',
            '#priceblock_dealprice',
            '.a-price .a-offscreen',
        ];
    }

    protected function installmentPriceSelectors(): array
    {
        return [
            '[data-testid="installment-price"]',
            '#installmentCalculator_feature_div .a-offscreen',
            '#corePriceDisplay_desktop_feature_div .basisPrice .a-offscreen',
            '.highlight-total-line-desktop',
            '.highlight-total-line-mobile',
        ];
    }

    protected function outOfStockSelectors(): array
    {
        return [
            '#outOfStock',
            '[data-testid="out-of-stock"]',
        ];
    }

    protected function outOfStockPhrases(): array
    {
        return [
            'não disponível no momento',
            'atualmente indisponível',
            'currently unavailable',
            'sem estoque',
        ];
    }
}
