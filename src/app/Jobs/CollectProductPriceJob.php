<?php

namespace App\Jobs;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\Scrapers\ScraperFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Log;
use Throwable;

class CollectProductPriceJob implements ShouldQueue
{
    use Queueable;

    /**
     * Maximum number of attempts before failing (RN-006).
     */
    public int $tries = 3;

    public function __construct(
        public readonly Product $product,
    ) {}

    /**
     * Exponential backoff in seconds between failed attempts: 2m, 4m, 8m (RN-006).
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return [120, 240, 480];
    }

    /**
     * Get the middleware the job should pass through (RN-005).
     *
     * @return list<object>
     */
    public function middleware(): array
    {
        return [
            new RateLimited('domain-scraping'),
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(ScraperFactory $scraperFactory): void
    {
        // Don't collect prices for paused products (RF-008)
        if ($this->product->status === ProductStatus::Paused) {
            return;
        }

        $scraper = $scraperFactory->make($this->product->domain);
        $scraped = $scraper->scrape($this->product->normalized_url, $this->product->id);

        $this->product->update([
            'title' => $scraped->title !== '' ? $scraped->title : $this->product->title,
            'image_url' => $scraped->imageUrl ?? $this->product->image_url,
            'current_price' => $scraped->cashPrice ?? $this->product->current_price,
            'installment_price' => $scraped->installmentPrice,
            'in_stock' => $scraped->inStock,
            'status' => ProductStatus::Active,
            'last_checked_at' => now(),
        ]);
    }

    /**
     * Handle job failure after all retries have been exhausted (RN-006, RNF-008).
     */
    public function failed(?Throwable $exception): void
    {
        $this->product->update([
            'status' => ProductStatus::Unstable,
        ]);

        Log::error('product.scrape.exhausted', [
            'product_id' => $this->product->id,
            'domain' => $this->product->domain,
            'url' => $this->product->normalized_url,
            'error' => $exception?->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
