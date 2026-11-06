<?php

namespace Database\Factories;

use App\Models\PriceHistory;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PriceHistory>
 */
class PriceHistoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<PriceHistory>
     */
    protected $model = PriceHistory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'price' => fake()->randomFloat(2, 50, 5000),
            'installment_price' => fake()->optional()->randomFloat(2, 55, 5500),
            'in_stock' => true,
            'recorded_at' => now(),
        ];
    }
}
