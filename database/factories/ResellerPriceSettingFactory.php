<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Fixa o padrao de precificacao do lojista: multiplicador, faixas de margem, arredondamento e permissoes.
*/

namespace Database\Factories;

use App\Models\Reseller;
use App\Models\ResellerPriceSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResellerPriceSetting>
 */
class ResellerPriceSettingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reseller_id' => Reseller::factory(),
            'pricing_model' => ResellerPriceSetting::PRICING_MODEL_MULTIPLIER,
            'multiplier' => 3.60,
            'margin_global' => 50.00,
            'margin_min' => 40.00,
            'margin_ideal' => 50.00,
            'margin_max' => 60.00,
            'rounding' => ResellerPriceSetting::ROUNDING_UP_099,
            'rule_scope' => ResellerPriceSetting::RULE_SCOPE_GLOBAL,
            'apply_to_all' => true,
            'allow_manual_override' => true,
            'allow_promotional_prices' => true,
        ];
    }

    public function porMargem(): static
    {
        return $this->state(fn (array $attributes): array => [
            'pricing_model' => ResellerPriceSetting::PRICING_MODEL_PERCENT,
        ]);
    }

    public function recalculada(): static
    {
        return $this->state(fn (array $attributes): array => [
            'recalculated_at' => now(),
        ]);
    }
}
