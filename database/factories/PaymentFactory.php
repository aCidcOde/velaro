<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Emite cobranca do lote por pix, boleto ou transferencia; states cobrem baixa conciliada e pendencia.
*/

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderBatch;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // O pagamento e do lote inteiro, nunca do pedido avulso.
            'batch_id' => OrderBatch::factory(),
            'method' => fake()->randomElement([
                Payment::METHOD_PIX,
                Payment::METHOD_BOLETO,
                Payment::METHOD_BANK_TRANSFER,
            ]),
            'amount' => fake()->randomFloat(2, 1200, 48000),
            'due_date' => now()->addDays(fake()->numberBetween(3, 30))->startOfDay(),
            'status' => Payment::STATUS_PENDING,
        ];
    }

    /**
     * Pagamento compensado e conciliado pelo financeiro.
     *
     * `payments.status` so declara 'pending' no model (o default da migration). O unico
     * slug 'paid' acordado no codigo e Order::PAYMENT_STATUS_PAID — e a decisao 1.2 de
     * docs/banco-de-dados.md diz que `orders.payment_status` E o ciclo financeiro do lote,
     * logo e o mesmo vocabulario. Troque por Payment::STATUS_PAID quando o model declarar.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Order::PAYMENT_STATUS_PAID,
            // Compensacao no vencimento, nunca no futuro: o default vence daqui a alguns
            // dias, e um `paid_at` a frente de hoje seria uma baixa que ainda nao ocorreu.
            // Closure para ler o `due_date` ja resolvido, inclusive quando o chamador
            // sobrescreve a data no proprio create().
            'paid_at' => function (array $resolved): Carbon {
                $dueDateValue = Carbon::parse($resolved['due_date'] ?? Carbon::now());

                return $dueDateValue->isFuture() ? Carbon::now() : $dueDateValue;
            },
            'reconciled_by' => User::factory()->admin(),
            'receipt_path' => 'comprovantes/'.fake()->uuid().'.pdf',
        ]);
    }

    /**
     * Cobranca emitida e ainda sem baixa.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Payment::STATUS_PENDING,
            'paid_at' => null,
            'reconciled_by' => null,
            'receipt_path' => null,
        ]);
    }
}
