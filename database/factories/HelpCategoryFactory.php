<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Gera uma das seis secoes da central de ajuda com slug e ordem; state cobre secao fora do ar.
*/

namespace Database\Factories;

use App\Models\HelpCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HelpCategory>
 */
class HelpCategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // As seis categorias reais da Central de ajuda (mockup 43-portal-ajuda).
        // O slug deriva do nome, como nas demais taxonomias Velaro; o sufixo numerico
        // so existe para dar folga ao UNIQUE de `help_categories.slug`.
        $name = (string) fake()->randomElement([
            'Primeiros passos',
            'Catálogo e pedidos',
            'Financeiro e pagamentos',
            'Preços e margens',
            'Vitrine e personalização',
            'Notas fiscais',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('####'),
            'position' => fake()->numberBetween(0, 10),
            'is_active' => true,
        ];
    }

    /**
     * Categoria fora do ar na Central de ajuda.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
