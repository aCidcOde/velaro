<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Monta campanha B2B em rascunho com janela e verba; states cobrem os status, frete gratis e lancamento.
*/

namespace Database\Factories;

use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Promotion>
 */
class PromotionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->startOfDay();

        return [
            // Formato do protocolo do prototipo: PROMO-2025-05 (ano-mes da campanha).
            // O ano-mes vem da janela que abre no `starts_at`; o sufixo numerico so
            // existe para dar folga ao UNIQUE de `promotions.code`.
            'code' => 'PROMO-'.$startsAt->format('Y-m').'-'.fake()->unique()->numerify('####'),
            // Nomes compativeis com o tipo padrao (desconto progressivo). Campanha de
            // frete e de lancamento tem nome proprio nos states, junto com o tipo.
            'name' => fake()->randomElement([
                'Desconto Progressivo - Alianças',
                'Dia dos Namorados',
                'Brilho que Encanta',
            ]),
            'description' => 'Descontos aplicados conforme o valor total do pedido.',
            // O tipo padrao casa com os tiers do PromotionRuleFactory.
            'type' => Promotion::TYPE_DESCONTO_PROGRESSIVO,
            'status' => Promotion::STATUS_RASCUNHO,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addDays(30)->endOfDay(),
            'priority' => fake()->numberBetween(0, 10),
            'show_badge' => true,
            'budget' => fake()->randomFloat(2, 1000, 20000),
        ];
    }

    /**
     * Campanha no ar: a janela envolve o agora.
     */
    public function ativa(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Promotion::STATUS_ATIVA,
            'starts_at' => now()->subDays(5)->startOfDay(),
            'ends_at' => now()->addDays(25)->endOfDay(),
        ]);
    }

    /**
     * Campanha publicada com inicio no futuro.
     */
    public function agendada(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Promotion::STATUS_AGENDADA,
            'starts_at' => now()->addDays(7)->startOfDay(),
            'ends_at' => now()->addDays(37)->endOfDay(),
        ]);
    }

    /**
     * Campanha cuja janela ja se fechou.
     */
    public function encerrada(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Promotion::STATUS_ENCERRADA,
            'starts_at' => now()->subDays(60)->startOfDay(),
            'ends_at' => now()->subDays(30)->endOfDay(),
        ]);
    }

    /**
     * Campanha suspensa sem perder a janela original.
     */
    public function pausada(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Promotion::STATUS_PAUSADA,
            'starts_at' => now()->subDays(5)->startOfDay(),
            'ends_at' => now()->addDays(25)->endOfDay(),
        ]);
    }

    /**
     * Frete gratis acima de um piso de pedido — nao usa tier percentual.
     */
    public function freteGratis(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => Promotion::TYPE_FRETE_GRATIS,
            'name' => 'Frete Grátis acima de R$ 1.000,00',
            'description' => 'Frete por conta da Velaro nos pedidos acima do piso.',
        ]);
    }

    /**
     * Campanha de lancamento de colecao.
     */
    public function lancamento(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => Promotion::TYPE_LANCAMENTO,
            'name' => 'Coleção Eternidade',
            'description' => 'Destaque de lançamento na vitrine dos revendedores.',
        ]);
    }
}
