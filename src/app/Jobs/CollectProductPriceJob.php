<?php

namespace App\Jobs;

use App\Models\Product;
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
    public function handle(): void
    {
        // O motor de extracao HTTP por adaptador de dominio sera integrado na Issue #10 / #12.
    }
}
