<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Declara CNAE do lojista; states dao o principal de joalheria, o secundario e um codigo fora do ramo.
*/

namespace Database\Factories;

use App\Models\Reseller;
use App\Models\ResellerCnae;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResellerCnae>
 */
class ResellerCnaeFactory extends Factory
{
    /**
     * UNIQUE(reseller_id, code): o código do padrão é gerado único no formato CNAE
     * (9999-9/99), então `ResellerCnae::factory()->count(3)->for($revendedor)` não colide.
     * Os CNAEs reais do ramo estão nos states `principal()`, `secundario()` e `incompativel()`,
     * que usam códigos distintos entre si e convivem no mesmo revendedor.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reseller_id' => Reseller::factory(),
            'code' => fake()->unique()->numerify('####-#/##'),
            'description' => 'Atividade econômica secundária declarada no CNPJ',
            // `is_primary` acompanha o default da migration: o CNAE principal é o state.
            'is_primary' => false,
            'compatible' => true,
        ];
    }

    /**
     * CNAE principal do ramo: 4783-1/01, comércio varejista de artigos de joalheria.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes): array => [
            'code' => '4783-1/01',
            'description' => 'Comércio varejista de artigos de joalheria',
            'is_primary' => true,
            'compatible' => true,
        ]);
    }

    public function secondary(): static
    {
        return $this->state(fn (array $attributes): array => [
            'code' => '4783-1/02',
            'description' => 'Comércio varejista de artigos de relojoaria',
            'is_primary' => false,
            'compatible' => true,
        ]);
    }

    /**
     * CNAE fora do ramo — derruba a compatibilidade na verificação do cadastro.
     */
    public function incompatible(): static
    {
        return $this->state(fn (array $attributes): array => [
            'code' => '5611-2/01',
            'description' => 'Restaurantes e similares',
            'is_primary' => false,
            'compatible' => false,
        ]);
    }
}
