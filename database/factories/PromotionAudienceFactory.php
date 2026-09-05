<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Define publico e canais da campanha; states cobrem rascunho sem canal e campanha so na loja online.
*/

namespace Database\Factories;

use App\Models\Promotion;
use App\Models\PromotionAudience;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromotionAudience>
 */
class PromotionAudienceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Valores transcritos do prototipo (tela 3.8 / mockup 63): publico-alvo
        // "Todos os revendedores ativos" e canais "Loja online, WhatsApp, E-mail".
        return [
            'promotion_id' => Promotion::factory(),
            'publico_alvo' => 'Todos os revendedores ativos',
            'canais' => ['Loja online', 'WhatsApp', 'E-mail'],
        ];
    }

    /**
     * Rascunho sem canal escolhido — o "Canais: Nenhum" do resumo da campanha.
     */
    public function semCanais(): static
    {
        return $this->state(fn (array $attributes): array => [
            'canais' => null,
        ]);
    }

    /**
     * Campanha restrita ao canal proprio da vitrine.
     */
    public function apenasLojaOnline(): static
    {
        return $this->state(fn (array $attributes): array => [
            'canais' => ['Loja online'],
        ]);
    }
}
