<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Congela a campanha aplicada ao pedido com o tipo e o desconto em reais do instante da aplicacao.
*/

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderPromotion;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderPromotion>
 */
class OrderPromotionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // UNIQUE(order_id, promotion_id): cada promocao entra uma vez por pedido.
            'order_id' => Order::factory(),
            'promotion_id' => Promotion::factory(),
            // Snapshot do tipo da promocao no instante da aplicacao: a promocao pode ser
            // editada depois, o pedido nao muda. Use fromPromotion() para casar os dois.
            'type' => fake()->randomElement([
                OrderPromotion::TYPE_TIERED_DISCOUNT,
                OrderPromotion::TYPE_SPECIAL_PRICE,
                OrderPromotion::TYPE_FREE_SHIPPING,
                OrderPromotion::TYPE_FIXED_DISCOUNT,
                OrderPromotion::TYPE_LAUNCH,
            ]),
            'discount_amount' => fake()->randomFloat(2, 45, 780),
            'applied_at' => now(),
        ];
    }

    /**
     * Aplica uma promocao existente e copia o tipo dela — o snapshot fiel.
     */
    public function fromPromotion(Promotion $promotion): static
    {
        return $this->state(fn (array $attributes): array => [
            'promotion_id' => $promotion->getKey(),
            'type' => $promotion->type,
        ]);
    }
}
