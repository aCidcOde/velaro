<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Lanca movimento de estoque com before e after coerentes; states cobrem os cinco tipos, o ator e o pedido.
*/

namespace Database\Factories;

use App\Models\Order;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $before = (int) fake()->numberBetween(0, 120);
        $qty = (int) fake()->numberBetween(1, 30);

        return [
            'stock_item_id' => StockItem::factory(),
            'type' => StockMovement::TYPE_ENTRADA,
            'qty' => $qty,
            'before' => $before,
            'after' => $before + $qty,
            'reason' => 'Entrada de produção — lote recebido da bancada',
            'actor_id' => null,
            'order_id' => null,
        ];
    }

    /**
     * Reabastecimento do cofre: soma ao saldo.
     */
    public function entrada(): static
    {
        return $this->state(function (array $attributes): array {
            $before = (int) fake()->numberBetween(0, 120);
            $qty = (int) fake()->numberBetween(1, 30);

            return [
                'type' => StockMovement::TYPE_ENTRADA,
                'qty' => $qty,
                'before' => $before,
                'after' => $before + $qty,
                'reason' => 'Entrada de produção — lote recebido da bancada',
            ];
        });
    }

    /**
     * Baixa por expedição: subtrai do saldo, nunca abaixo de zero.
     */
    public function saida(): static
    {
        return $this->state(function (array $attributes): array {
            $before = (int) fake()->numberBetween(5, 120);
            $qty = (int) fake()->numberBetween(1, $before);

            return [
                'type' => StockMovement::TYPE_SAIDA,
                'qty' => $qty,
                'before' => $before,
                'after' => $before - $qty,
                'reason' => 'Saída para expedição do lote do lojista',
            ];
        });
    }

    /**
     * Ajuste manual do master — qty é o delta assinado e exige registro em audit_logs.
     */
    public function ajuste(): static
    {
        return $this->state(function (array $attributes): array {
            $before = (int) fake()->numberBetween(5, 120);
            $qty = (int) fake()->randomElement([-4, -3, -2, -1, 1, 2, 3, 4]);

            return [
                'type' => StockMovement::TYPE_AJUSTE,
                'qty' => $qty,
                'before' => $before,
                'after' => $before + $qty,
                'reason' => 'Ajuste manual após conferência do cofre',
            ];
        });
    }

    /**
     * Reserva de peças para um pedido em aberto: derruba o disponível.
     */
    public function reserva(): static
    {
        return $this->state(function (array $attributes): array {
            $before = (int) fake()->numberBetween(4, 120);
            $qty = (int) fake()->numberBetween(1, min(12, $before));

            return [
                'type' => StockMovement::TYPE_RESERVA,
                'qty' => $qty,
                'before' => $before,
                'after' => $before - $qty,
                'reason' => 'Reserva de peças para pedido em aberto',
            ];
        });
    }

    /**
     * Devolução da produção ao cofre depois da solicitação atendida.
     */
    public function producao(): static
    {
        return $this->state(function (array $attributes): array {
            $before = (int) fake()->numberBetween(0, 60);
            $qty = (int) fake()->numberBetween(2, 40);

            return [
                'type' => StockMovement::TYPE_PRODUCAO,
                'qty' => $qty,
                'before' => $before,
                'after' => $before + $qty,
                'reason' => 'Solicitação de produção atendida pela bancada',
            ];
        });
    }

    public function porAtor(?User $actor = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'actor_id' => $actor?->getKey() ?? User::factory(),
        ]);
    }

    public function doPedido(Order $order): static
    {
        return $this->state(fn (array $attributes): array => [
            'order_id' => $order->getKey(),
        ]);
    }
}
