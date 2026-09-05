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
                Payment::METHOD_TRANSFERENCIA,
            ]),
            'amount' => fake()->randomFloat(2, 1200, 48000),
            'due_date' => now()->addDays(fake()->numberBetween(3, 30))->startOfDay(),
            'status' => Payment::STATUS_PENDENTE,
        ];
    }

    /**
     * Pagamento compensado e conciliado pelo financeiro.
     *
     * `payments.status` so declara 'pendente' no model (o default da migration). O unico
     * slug 'pago' acordado no codigo e Order::PAYMENT_STATUS_PAGO — e a decisao 1.2 de
     * docs/banco-de-dados.md diz que `orders.payment_status` E o ciclo financeiro do lote,
     * logo e o mesmo vocabulario. Troque por Payment::STATUS_PAGO quando o model declarar.
     */
    public function pago(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Order::PAYMENT_STATUS_PAGO,
            // Compensacao no vencimento, nunca no futuro: o default vence daqui a alguns
            // dias, e um `paid_at` a frente de hoje seria uma baixa que ainda nao ocorreu.
            // Closure para ler o `due_date` ja resolvido, inclusive quando o chamador
            // sobrescreve a data no proprio create().
            'paid_at' => function (array $resolvidos): Carbon {
                $vencimento = Carbon::parse($resolvidos['due_date'] ?? Carbon::now());

                return $vencimento->isFuture() ? Carbon::now() : $vencimento;
            },
            'reconciled_by' => User::factory()->admin(),
            'receipt_path' => 'comprovantes/'.fake()->uuid().'.pdf',
        ]);
    }

    /**
     * Cobranca emitida e ainda sem baixa.
     */
    public function pendente(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Payment::STATUS_PENDENTE,
            'paid_at' => null,
            'reconciled_by' => null,
            'receipt_path' => null,
        ]);
    }
}
