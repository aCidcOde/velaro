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
            // editada depois, o pedido nao muda. Use daPromocao() para casar os dois.
            'type' => fake()->randomElement([
                OrderPromotion::TYPE_DESCONTO_PROGRESSIVO,
                OrderPromotion::TYPE_PRECO_ESPECIAL,
                OrderPromotion::TYPE_FRETE_GRATIS,
                OrderPromotion::TYPE_DESCONTO_FIXO,
                OrderPromotion::TYPE_LANCAMENTO,
            ]),
            'discount_amount' => fake()->randomFloat(2, 45, 780),
            'applied_at' => now(),
        ];
    }

    /**
     * Aplica uma promocao existente e copia o tipo dela — o snapshot fiel.
     */
    public function daPromocao(Promotion $promocao): static
    {
        return $this->state(fn (array $attributes): array => [
            'promotion_id' => $promocao->getKey(),
            'type' => $promocao->type,
        ]);
    }
}
