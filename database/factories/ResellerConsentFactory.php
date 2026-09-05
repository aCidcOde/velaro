<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Registra aceite do lojista com versao do texto, IP e user agent; states dao termos, LGPD e revogacao.
*/

namespace Database\Factories;

use App\Models\Reseller;
use App\Models\ResellerConsent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * Aceite do lojista no cadastro: guarda a versão do documento e a evidência técnica
 * (IP e user agent) do clique.
 *
 * @extends Factory<ResellerConsent>
 */
class ResellerConsentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reseller_id' => Reseller::factory(),
            'type' => fake()->randomElement([
                ResellerConsent::TYPE_TERMOS,
                ResellerConsent::TYPE_LGPD,
            ]),
            'granted' => true,
            'document_version' => fake()->randomElement(['v1.0', 'v1.1', 'v2.0', 'v2.1']),
            'granted_at' => now()->subDays(fake()->numberBetween(0, 365)),
            'revoked_at' => null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    public function termos(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ResellerConsent::TYPE_TERMOS,
        ]);
    }

    public function lgpd(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ResellerConsent::TYPE_LGPD,
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
