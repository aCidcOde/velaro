<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Abre lote semanal com codigo ISO, corte e vencimento; states cobrem lote quitado e lote em cobranca.
*/

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderBatch;
use App\Models\Reseller;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<OrderBatch>
 */
class OrderBatchFactory extends Factory
{
    /**
     * Quantas semanas o proximo lote fica atras da semana corrente.
     *
     * A identidade do lote e o par ano+semana ISO que aparece no `code` (UNIQUE), entao o
     * codigo so pode ser unico se a semana for. Um contador resolve isso sem depender do
     * pool do `fake()->unique()`, que e compartilhado por formatter entre todas as
     * factories e estouraria numa faixa de 52 valores.
     */
    private static int $weeksBack = 0;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Corte na segunda-feira da semana; vencimento 15 dias depois — sempre posterior.
        $cutDate = now()->startOfWeek()->subWeeks(self::$weeksBack++);
        $dueDate = $cutDate->copy()->addDays(15);

        return [
            // Formato do protocolo: LOTE-2025-W21 (ano ISO + semana ISO do corte).
            'code' => sprintf('LOTE-%s-W%s', $cutDate->format('o'), $cutDate->format('W')),
            'reseller_id' => Reseller::factory(),
            'cut_date' => $cutDate,
            'due_date' => $dueDate,
            'status' => OrderBatch::STATUS_OPEN,
            'total_amount' => fake()->randomFloat(2, 4800, 62000),
        ];
    }

    /**
     * Lote quitado no vencimento.
     *
     * `order_batches.status` so declara 'open' no model (o default da migration). O unico
     * slug 'paid' acordado no codigo e Order::PAYMENT_STATUS_PAID — e a decisao 1.2 de
     * docs/banco-de-dados.md diz que `orders.payment_status` E o ciclo financeiro do lote,
     * logo e o mesmo vocabulario. Troque por OrderBatch::STATUS_PAID quando o model declarar.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Order::PAYMENT_STATUS_PAID,
            // Baixa no vencimento, nunca no futuro: lote pago e fato consumado, e um
            // `paid_at` a frente de hoje sumiria de todo relatorio de recebimento.
            // Closure para ler o `due_date` ja resolvido, inclusive quando o chamador
            // sobrescreve a data no proprio create().
            'paid_at' => function (array $resolved): Carbon {
                $dueDateValue = Carbon::parse($resolved['due_date'] ?? Carbon::now());

                return $dueDateValue->isFuture() ? Carbon::now() : $dueDateValue;
            },
        ]);
    }

    /**
     * Lote ainda em cobranca: nada compensado.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderBatch::STATUS_OPEN,
            'paid_at' => null,
        ]);
    }
}
