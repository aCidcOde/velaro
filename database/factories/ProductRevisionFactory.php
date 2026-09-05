<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Grava revisao before/after de preco e prazo da peca; state atribui o operador que fez a alteracao.
*/

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductRevision;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductRevision>
 */
class ProductRevisionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $previousPrice = (float) fake()->randomFloat(2, 180, 4200);
        $newPrice = round($previousPrice * (float) fake()->randomFloat(2, 1.02, 1.18), 2);
        $previousDeliveryDays = (int) fake()->numberBetween(12, 30);

        return [
            'product_id' => Product::factory(),
            'actor_id' => null,
            'action' => ProductRevision::ACTION_UPDATED,
            'before' => [
                'price' => $previousPrice,
                'delivery_days' => $previousDeliveryDays,
                'is_active' => true,
            ],
            'after' => [
                'price' => $newPrice,
                'delivery_days' => max(5, $previousDeliveryDays - 3),
                'is_active' => true,
            ],
        ];
    }

    public function forProduct(Product $product): static
    {
        return $this->state(fn (array $attributes): array => [
            'product_id' => $product->getKey(),
        ]);
    }

    /**
     * Revisão feita por um usuário do painel — sem ator, a alteração é do sistema.
     */
    public function byActor(?User $actor = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'actor_id' => $actor?->getKey() ?? User::factory(),
        ]);
    }
}
