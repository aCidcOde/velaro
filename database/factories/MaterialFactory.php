<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Monta material da ficha tecnica (Prata 950, Ouro 18k, Aco) com slug unico; state cobre material inativo.
*/

namespace Database\Factories;

use App\Models\Material;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Material>
 */
class MaterialFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = (string) fake()->randomElement([
            'Prata 950',
            'Ouro Amarelo 18k',
            'Ouro Rosé 18k',
            'Ouro Branco 18k',
            'Aço',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('####'),
            'position' => fake()->numberBetween(1, 20),
            'is_active' => true,
        ];
    }

    /**
     * Material nomeado da ficha técnica (Prata 950, Ouro Amarelo 18k, Aço).
     * Slug determinístico: uma linha por nome — chamar duas vezes com o mesmo nome viola o UNIQUE(slug).
     */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => $name,
            'slug' => Str::slug($name),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
