<?php

namespace App\Jobs;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\Scrapers\ScraperFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CollectProductPriceJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Product $product,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ScraperFactory $scraperFactory): void
    {
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
}
