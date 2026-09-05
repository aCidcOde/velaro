<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Monta a excecao de preco B2C do lojista; states levam o escopo a colecao, a produto ou a margem percentual.
*/

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\Reseller;
use App\Models\ResellerPriceRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResellerPriceRule>
 */
class ResellerPriceRuleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reseller_id' => Reseller::factory(),
            'scope' => ResellerPriceRule::SCOPE_GLOBAL,
            'collection_id' => null,
            'product_id' => null,
            'mode' => ResellerPriceRule::MODE_MULTIPLIER,
            'value' => 3.6000,
            'priority' => 0,
            'is_active' => true,
        ];
    }

    public function forCollection(?ProductCollection $collection = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'scope' => ResellerPriceRule::SCOPE_COLLECTION,
            'collection_id' => $collection?->getKey() ?? ProductCollection::factory(),
            'product_id' => null,
            'priority' => 10,
        ]);
    }

    public function forProduct(?Product $product = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'scope' => ResellerPriceRule::SCOPE_PRODUCT,
            'collection_id' => null,
            'product_id' => $product?->getKey() ?? Product::factory(),
            'priority' => 20,
        ]);
    }

    public function byMargin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'mode' => ResellerPriceRule::MODE_PERCENT,
            'value' => 50.0000,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
