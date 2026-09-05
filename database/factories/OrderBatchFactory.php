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
    private static int $semanasAtras = 0;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Corte na segunda-feira da semana; vencimento 15 dias depois — sempre posterior.
        $cutDate = now()->startOfWeek()->subWeeks(self::$semanasAtras++);
        $dueDate = $cutDate->copy()->addDays(15);

        return [
            // Formato do protocolo: LOTE-2025-W21 (ano ISO + semana ISO do corte).
            'code' => sprintf('LOTE-%s-W%s', $cutDate->format('o'), $cutDate->format('W')),
            'reseller_id' => Reseller::factory(),
            'cut_date' => $cutDate,
            'due_date' => $dueDate,
            'status' => OrderBatch::STATUS_ABERTO,
            'total_amount' => fake()->randomFloat(2, 4800, 62000),
        ];
    }

    /**
     * Lote quitado no vencimento.
     *
     * `order_batches.status` so declara 'aberto' no model (o default da migration). O unico
     * slug 'pago' acordado no codigo e Order::PAYMENT_STATUS_PAGO — e a decisao 1.2 de
     * docs/banco-de-dados.md diz que `orders.payment_status` E o ciclo financeiro do lote,
     * logo e o mesmo vocabulario. Troque por OrderBatch::STATUS_PAGO quando o model declarar.
     */
    public function pago(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Order::PAYMENT_STATUS_PAGO,
            // Baixa no vencimento, nunca no futuro: lote pago e fato consumado, e um
            // `paid_at` a frente de hoje sumiria de todo relatorio de recebimento.
            // Closure para ler o `due_date` ja resolvido, inclusive quando o chamador
            // sobrescreve a data no proprio create().
            'paid_at' => function (array $resolvidos): Carbon {
                $vencimento = Carbon::parse($resolvidos['due_date'] ?? Carbon::now());

                return $vencimento->isFuture() ? Carbon::now() : $vencimento;
            },
        ]);
    }

    /**
     * Lote ainda em cobranca: nada compensado.
     */
    public function emAberto(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderBatch::STATUS_ABERTO,
            'paid_at' => null,
        ]);
    }
}
