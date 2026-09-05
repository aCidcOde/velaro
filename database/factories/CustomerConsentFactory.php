<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Monta aceite LGPD do consumidor final com evidencia; states dao marketing, transacional e revogacao.
*/

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerConsent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * Aceite do consumidor final: registra o tipo do consentimento e a evidência textual do aceite.
 *
 * `channel` fica de fora: a coluna é nullable, o model não declara constante para ela e o
 * vocabulário do canal ainda não está acordado. Quando virar constante, entra aqui.
 *
 * @extends Factory<CustomerConsent>
 */
class CustomerConsentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'type' => fake()->randomElement([
                CustomerConsent::TYPE_MARKETING,
                CustomerConsent::TYPE_TRANSACIONAL,
            ]),
            'granted' => true,
            'granted_at' => now()->subDays(fake()->numberBetween(0, 365)),
            'revoked_at' => null,
            'evidence' => 'Aceite colhido no atendimento e registrado pelo revendedor.',
        ];
    }

    public function marketing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => CustomerConsent::TYPE_MARKETING,
            'evidence' => 'Cliente autorizou o envio de novidades e promoções.',
        ]);
    }

    public function transacional(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => CustomerConsent::TYPE_TRANSACIONAL,
            'evidence' => 'Cliente autorizou avisos sobre o andamento do pedido.',
        ]);
    }

    /**
     * Aceite revogado depois de concedido: `revoked_at` nasce ancorado em `granted_at`,
     * nunca antes dele.
     */
    public function revogado(): static
    {
        return $this->state(function (array $attributes): array {
            $concedidoEm = $attributes['granted_at'] ?? null;
            $base = $concedidoEm instanceof \DateTimeInterface
                ? Carbon::instance($concedidoEm)
                : now()->subDays(30);

            return [
                'granted' => false,
                'revoked_at' => Carbon::instance(fake()->dateTimeBetween($base->toDateTimeString(), 'now')),
            ];
        });
    }
}
