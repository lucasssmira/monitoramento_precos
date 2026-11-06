<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Carbon;

class ProductAnalyticsService
{
    /**
     * Supported history windows in days (RF-014).
     *
     * @var list<int>
     */
    public const ALLOWED_PERIODS = [7, 30, 90];

    /**
     * Compute analytical summary and price history series for the requested period (RF-014, RF-015).
     *
     * @return array{
     *     period_days: int,
     *     allowed_periods: list<int>,
     *     all_time_lowest_price: float|null,
     *     period_lowest_price: float|null,
     *     period_highest_price: float|null,
     *     period_average_price: float|null,
     *     savings_percentage: float|null,
     *     history: list<array{
     *         id: int,
     *         price: float,
     *         installment_price: float|null,
     *         in_stock: bool,
     *         recorded_at: string
     *     }>
     * }
     */
    public function getAnalytics(Product $product, int $days = 30): array
    {
        if (! in_array($days, self::ALLOWED_PERIODS, true)) {
            $days = 30;
        }

        $allTimeLowestRaw = $product->priceHistories()->min('price');
        $allTimeLowest = $allTimeLowestRaw !== null
            ? (float) $allTimeLowestRaw
            : ($product->current_price !== null ? (float) $product->current_price : null);

        $since = Carbon::now()->subDays($days)->startOfDay();

        $records = $product->priceHistories()
            ->where('recorded_at', '>=', $since)
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->get();

        $historyPoints = array_values($records->map(fn ($history) => [
            'id' => $history->id,
            'price' => (float) $history->price,
            'installment_price' => $history->installment_price !== null ? (float) $history->installment_price : null,
            'in_stock' => (bool) $history->in_stock,
            'recorded_at' => $history->recorded_at->toIso8601String(),
        ])->all());

        if ($records->isNotEmpty()) {
            $periodLowest = (float) $records->min('price');
            $periodHighest = (float) $records->max('price');
            $periodAverage = round((float) $records->avg('price'), 2);
        } else {
            $periodLowest = $product->current_price !== null ? (float) $product->current_price : null;
            $periodHighest = $product->current_price !== null ? (float) $product->current_price : null;
            $periodAverage = $product->current_price !== null ? (float) $product->current_price : null;
        }

        $savingsPercentage = null;
        if ($periodAverage !== null && $periodAverage > 0 && $product->current_price !== null) {
            $diff = $periodAverage - (float) $product->current_price;
            $savingsPercentage = round(($diff / $periodAverage) * 100, 1);
        }

        return [
            'period_days' => $days,
            'allowed_periods' => self::ALLOWED_PERIODS,
            'all_time_lowest_price' => $allTimeLowest,
            'period_lowest_price' => $periodLowest,
            'period_highest_price' => $periodHighest,
            'period_average_price' => $periodAverage,
            'savings_percentage' => $savingsPercentage,
            'history' => $historyPoints,
        ];
    }
}
