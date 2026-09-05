<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Monta acabamento da ficha tecnica (Polida, Fosca, PVD) com slug unico; state cobre acabamento inativo.
*/

namespace Database\Factories;

use App\Models\Finish;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Finish>
 */
class FinishFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = (string) fake()->randomElement([
            'Polida',
            'Fosca',
            'Diamantada',
            'Cravejada',
            'Texturizada',
            'PVD',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('####'),
            'position' => fake()->numberBetween(1, 20),
            'is_active' => true,
        ];
    }

    /**
     * Acabamento nomeado da ficha técnica (Polida, Fosca, Diamantada).
     * Slug determinístico: uma linha por nome — chamar duas vezes com o mesmo nome viola o UNIQUE(slug).
     */
    public function comNome(string $name): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => $name,
            'slug' => Str::slug($name),
        ]);
    }

    public function inativo(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
