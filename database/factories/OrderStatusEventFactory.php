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
        $fluxo = [
            Order::OPERATIONAL_STATUS_REGISTRADO,
            Order::OPERATIONAL_STATUS_PAGAMENTO_CONFIRMADO,
            Order::OPERATIONAL_STATUS_PRODUCAO_ANDAMENTO,
            Order::OPERATIONAL_STATUS_PRODUCAO_FINALIZADA,
            Order::OPERATIONAL_STATUS_EM_TRANSPORTE,
            Order::OPERATIONAL_STATUS_PRONTO_RETIRADA,
            Order::OPERATIONAL_STATUS_RETIRADO,
        ];

        $destino = fake()->numberBetween(1, count($fluxo) - 1);

        return [
            'order_id' => Order::factory(),
            // Unico escopo com vocabulario acordado no model.
            'scope' => OrderStatusEvent::SCOPE_OPERATIONAL,
            'from_status' => $fluxo[$destino - 1],
            'to_status' => $fluxo[$destino],
            // actor_id e nullable: transicao automatica do sistema por padrao.
            'actor_id' => null,
        ];
    }

    /**
     * Primeiro evento da linha do tempo do pedido: nao existe status anterior.
     */
    public function abertura(): static
    {
        return $this->state(fn (array $attributes): array => [
            'from_status' => null,
            'to_status' => Order::OPERATIONAL_STATUS_REGISTRADO,
        ]);
    }

    /**
     * Transicao registrada por um operador identificado, e nao pelo sistema.
     */
    public function comAtor(): static
    {
        return $this->state(fn (array $attributes): array => [
            'actor_id' => User::factory()->admin(),
        ]);
    }
}
