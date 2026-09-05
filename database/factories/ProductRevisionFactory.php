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
        $precoAnterior = (float) fake()->randomFloat(2, 180, 4200);
        $precoNovo = round($precoAnterior * (float) fake()->randomFloat(2, 1.02, 1.18), 2);
        $prazoAnterior = (int) fake()->numberBetween(12, 30);

        return [
            'product_id' => Product::factory(),
            'actor_id' => null,
            'action' => ProductRevision::ACTION_UPDATED,
            'before' => [
                'price' => $precoAnterior,
                'prazo_entrega_dias' => $prazoAnterior,
                'is_active' => true,
            ],
            'after' => [
                'price' => $precoNovo,
                'prazo_entrega_dias' => max(5, $prazoAnterior - 3),
                'is_active' => true,
            ],
        ];
    }

    public function paraProduto(Product $product): static
    {
        return $this->state(fn (array $attributes): array => [
            'product_id' => $product->getKey(),
        ]);
    }

    /**
     * Revisão feita por um usuário do painel — sem ator, a alteração é do sistema.
     */
    public function porAtor(?User $actor = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'actor_id' => $actor?->getKey() ?? User::factory(),
        ]);
    }
}
