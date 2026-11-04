<?php

namespace Database\Factories;

use App\Enums\ProductCategory;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $slug = fake()->unique()->slug();
        $url = "https://www.kabum.com.br/produto/{$slug}";
        $normalizedUrl = "https://kabum.com.br/produto/{$slug}";

        return [
            'user_id' => User::factory(),
            'url' => $url,
            'normalized_url' => $normalizedUrl,
            'domain' => 'kabum.com.br',
            'title' => fake()->sentence(4),
            'nickname' => null,
            'category' => ProductCategory::Hardware,
            'image_url' => null,
            'target_price' => 1500.00,
            'current_price' => null,
            'installment_price' => null,
            'in_stock' => true,
            'notify_on_stock' => false,
            'status' => ProductStatus::Pending,
            'last_checked_at' => null,
            'last_manual_check_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::Active,
            'current_price' => 1599.90,
            'installment_price' => 1799.90,
            'last_checked_at' => now(),
        ]);
    }

    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::Paused,
        ]);
    }

    public function unstable(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::Unstable,
        ]);
    }
}
