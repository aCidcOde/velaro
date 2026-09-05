<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Sorteia um degrau real da cadeia operacional do pedido; states dao a abertura e o ator da transicao.
*/

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderStatusEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderStatusEvent>
 */
class OrderStatusEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Cadeia operacional canonica (decisao 1.2 de docs/banco-de-dados.md). O evento e
        // sempre um passo real dela: from_status e o degrau anterior de to_status.
        $flow = [
            Order::OPERATIONAL_STATUS_REGISTERED,
            Order::OPERATIONAL_STATUS_PAYMENT_CONFIRMED,
            Order::OPERATIONAL_STATUS_IN_PRODUCTION,
            Order::OPERATIONAL_STATUS_PRODUCTION_COMPLETED,
            Order::OPERATIONAL_STATUS_IN_TRANSIT,
            Order::OPERATIONAL_STATUS_READY_FOR_PICKUP,
            Order::OPERATIONAL_STATUS_PICKED_UP,
        ];

        $target = fake()->numberBetween(1, count($flow) - 1);

        return [
            'order_id' => Order::factory(),
            // Unico escopo com vocabulario acordado no model.
            'scope' => OrderStatusEvent::SCOPE_OPERATIONAL,
            'from_status' => $flow[$target - 1],
            'to_status' => $flow[$target],
            // actor_id e nullable: transicao automatica do sistema por padrao.
            'actor_id' => null,
        ];
    }

    /**
     * Primeiro evento da linha do tempo do pedido: nao existe status anterior.
     */
    public function opening(): static
    {
        return $this->state(fn (array $attributes): array => [
            'from_status' => null,
            'to_status' => Order::OPERATIONAL_STATUS_REGISTERED,
        ]);
    }

    /**
     * Transicao registrada por um operador identificado, e nao pelo sistema.
     */
    public function withActor(): static
    {
        return $this->state(fn (array $attributes): array => [
            'actor_id' => User::factory()->admin(),
        ]);
    }
}
