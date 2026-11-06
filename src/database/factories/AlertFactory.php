<?php

namespace Database\Factories;

use App\Enums\AlertType;
use App\Models\Alert;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alert>
 */
class AlertFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Alert>
     */
    protected $model = Alert::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
            'type' => AlertType::PriceDrop,
            'triggered_price' => fake()->randomFloat(2, 50, 1000),
            'installment_price' => fake()->optional()->randomFloat(2, 55, 1100),
            'previous_price' => fake()->optional()->randomFloat(2, 60, 1200),
            'target_price' => fake()->randomFloat(2, 60, 1200),
            'read_at' => null,
        ];
    }

    /**
     * Indicate that the alert is back in stock.
     */
    public function backInStock(): static
    {
        return $this->state(fn () => [
            'type' => AlertType::BackInStock,
        ]);
    }

    /**
     * Indicate that the alert has been read.
     */
    public function read(): static
    {
        return $this->state(fn () => [
            'read_at' => now(),
        ]);
    }
}
