<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####')),
            'description' => fake()->sentence(12),
            'price' => fake()->randomFloat(2, 25, 500),
            'is_active' => true,
            'meta' => [
                'category' => fake()->randomElement(['service', 'subscription', 'package']),
            ],
        ];
    }
}
