<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Monta a faixa de desconto da campanha; states reproduzem os tres tiers do prototipo e o abatimento fixo.
*/

namespace Database\Factories;

use App\Models\Promotion;
use App\Models\PromotionRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromotionRule>
 */
class PromotionRuleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Primeiro tier do prototipo (tela 3.8): acima de R$ 1.000,00 -> 5%.
        // `discount_amount` fica de fora: e a alternativa excludente ao percentual
        // e ganha valor pelo state `fixedAmount()`.
        return [
            'promotion_id' => Promotion::factory(),
            'min_amount' => 1000.00,
            'discount_percent' => 5.00,
            'position' => 1,
        ];
    }

    /**
     * Tier 1 do prototipo: acima de R$ 1.000,00 -> 5%.
     */
    public function aboveOneThousand(): static
    {
        return $this->state(fn (array $attributes): array => [
            'min_amount' => 1000.00,
            'discount_percent' => 5.00,
            'discount_amount' => null,
            'position' => 1,
        ]);
    }

    /**
     * Tier 2 do prototipo: acima de R$ 2.000,00 -> 10%.
     */
    public function aboveTwoThousand(): static
    {
        return $this->state(fn (array $attributes): array => [
            'min_amount' => 2000.00,
            'discount_percent' => 10.00,
            'discount_amount' => null,
            'position' => 2,
        ]);
    }

    /**
     * Tier 3 do prototipo: acima de R$ 3.000,00 -> 15%.
     */
    public function aboveThreeThousand(): static
    {
        return $this->state(fn (array $attributes): array => [
            'min_amount' => 3000.00,
            'discount_percent' => 15.00,
            'discount_amount' => null,
            'position' => 3,
        ]);
    }

    /**
     * Faixa de abatimento fixo em reais — exclui o percentual.
     */
    public function fixedAmount(float $amount = 150.00): static
    {
        return $this->state(fn (array $attributes): array => [
            'discount_percent' => null,
            'discount_amount' => $amount,
        ]);
    }
}
