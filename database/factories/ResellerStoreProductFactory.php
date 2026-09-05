<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Seleciona um produto na vitrine do lojista como destaque; state deixa so a curadoria, sem o destaque.
*/

namespace Database\Factories;

use App\Models\Product;
use App\Models\ResellerStore;
use App\Models\ResellerStoreProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResellerStoreProduct>
 */
class ResellerStoreProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reseller_store_id' => ResellerStore::factory(),
            'product_id' => Product::factory(),
            'position' => fake()->numberBetween(0, 20),
            'is_featured' => true,
        ];
    }

    public function semDestaque(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_featured' => false,
        ]);
    }
}
