<?php

namespace App\Contracts;

use App\DataTransferObjects\ScrapedProductData;

interface ProductScraperInterface
{
    /**
     * Return the canonical domain handled by this scraper adapter.
     */
    public function domain(): string;

    /**
     * Scrape a live product URL via HTTP and extract its pricing and stock metadata.
     */
    public function scrape(string $url, ?int $productId = null): ScrapedProductData;

    /**
     * Extract product pricing, stock and metadata from raw HTML content.
     */
    public function extractFromHtml(string $html, int $httpStatus = 200, int $durationMs = 0): ScrapedProductData;
}
