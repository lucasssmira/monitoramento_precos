<?php

namespace App\Services;

use App\Models\PriceHistory;
use App\Models\Product;

class PriceHistoryRecorderService
{
    /**
     * Record a new price history entry only if price, installment price, or stock status changed (RN-007).
     *
     * @return PriceHistory|null The newly created entry, or null if duplicate and discarded.
     */
    public function record(Product $product, float $price, ?float $installmentPrice, bool $inStock): ?PriceHistory
    {
        /** @var PriceHistory|null $latest */
        $latest = $product->priceHistories()
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->first();

        if ($latest !== null) {
            $samePrice = (abs((float) $latest->price - $price) < 0.001);
            $sameInstallment = ($latest->installment_price === null && $installmentPrice === null)
                || ($latest->installment_price !== null && $installmentPrice !== null && abs((float) $latest->installment_price - $installmentPrice) < 0.001);
            $sameStock = ($latest->in_stock === $inStock);

            if ($samePrice && $sameInstallment && $sameStock) {
                // RN-007: Duplicate reading without variation, discard.
                return null;
            }
        }

        return $product->priceHistories()->create([
            'price' => $price,
            'installment_price' => $installmentPrice,
            'in_stock' => $inStock,
            'recorded_at' => now(),
        ]);
    }
}
