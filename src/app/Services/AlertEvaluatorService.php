<?php

namespace App\Services;

use App\Enums\AlertType;
use App\Models\Alert;
use App\Models\Product;
use App\Notifications\BackInStockNotification;
use App\Notifications\PriceDropNotification;
use Illuminate\Support\Carbon;

class AlertEvaluatorService
{
    /**
     * Cooldown duration in hours for repetitive price alerts (RN-009).
     */
    public const COOLDOWN_HOURS = 6;

    /**
     * Minimum additional percentage drop to bypass cooldown (RN-009).
     */
    public const MIN_ADDITIONAL_DROP_RATIO = 0.05;

    /**
     * Evaluate price drop and stock transitions after a scrape, dispatching alerts when eligible (RF-016, RF-017, RN-008, RN-009).
     */
    public function evaluate(
        Product $product,
        ?float $previousPrice,
        bool $previousInStock,
        ?float $newPrice,
        ?float $newInstallmentPrice,
        bool $newInStock,
    ): void {
        $this->evaluateBackInStock($product, $previousInStock, $newInStock, $newPrice, $newInstallmentPrice, $previousPrice);
        $this->evaluatePriceDrop($product, $newPrice, $newInstallmentPrice, $previousPrice, $newInStock);
    }

    /**
     * Evaluate and dispatch back in stock alert (RF-017).
     */
    protected function evaluateBackInStock(
        Product $product,
        bool $previousInStock,
        bool $newInStock,
        ?float $newPrice,
        ?float $newInstallmentPrice,
        ?float $previousPrice,
    ): void {
        if (! $product->notify_on_stock) {
            return;
        }

        // Product transitioned from out-of-stock (false) to available (true)
        if (! $previousInStock && $newInStock) {
            $alert = Alert::create([
                'user_id' => $product->user_id,
                'product_id' => $product->id,
                'type' => AlertType::BackInStock,
                'triggered_price' => $newPrice ?? ($product->current_price !== null ? (float) $product->current_price : (float) $product->target_price),
                'installment_price' => $newInstallmentPrice,
                'previous_price' => $previousPrice,
                'target_price' => (float) $product->target_price,
            ]);

            $product->user->notify(new BackInStockNotification($alert));
        }
    }

    /**
     * Evaluate and dispatch price drop alert under RN-008 and RN-009.
     */
    protected function evaluatePriceDrop(
        Product $product,
        ?float $newPrice,
        ?float $newInstallmentPrice,
        ?float $previousPrice,
        bool $newInStock,
    ): void {
        if ($newPrice === null) {
            return;
        }

        // RN-008: Item must be in stock to trigger price drop alerts
        if (! $newInStock) {
            return;
        }

        $targetPrice = (float) $product->target_price;

        // RN-008: Current cash price must be less than or equal to target price
        if ($newPrice > $targetPrice) {
            return;
        }

        // RN-009: Check cooldown of 6 hours unless there is a further price drop of >= 5%
        /** @var Alert|null $lastPriceAlert */
        $lastPriceAlert = Alert::query()
            ->where('product_id', $product->id)
            ->where('type', AlertType::PriceDrop)
            ->latest('created_at')
            ->first();

        if ($lastPriceAlert !== null && $lastPriceAlert->created_at !== null) {
            $isWithinCooldown = $lastPriceAlert->created_at->greaterThan(
                Carbon::now()->subHours(self::COOLDOWN_HOURS)
            );

            if ($isWithinCooldown) {
                $lastTriggeredPrice = (float) $lastPriceAlert->triggered_price;
                $additionalDropRatio = ($lastTriggeredPrice - $newPrice) / $lastTriggeredPrice;

                // If additional drop is less than 5%, suppress alert to avoid spam
                if ($additionalDropRatio < self::MIN_ADDITIONAL_DROP_RATIO) {
                    return;
                }
            }
        }

        $alert = Alert::create([
            'user_id' => $product->user_id,
            'product_id' => $product->id,
            'type' => AlertType::PriceDrop,
            'triggered_price' => $newPrice,
            'installment_price' => $newInstallmentPrice,
            'previous_price' => $previousPrice,
            'target_price' => $targetPrice,
        ]);

        $product->user->notify(new PriceDropNotification($alert));
    }
}
