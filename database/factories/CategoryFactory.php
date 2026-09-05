<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Monta categoria do catalogo com slug unico e posicao; states dao subcategoria filha e ramo desativado.
*/

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = (string) fake()->randomElement([
            'Alianças Tradicionais',
            'Alianças Anatômicas',
            'Solitários',
            'Meia Aliança',
            'Anéis Aparadores',
            'Acessórios',
        ]);

        return [
            'parent_id' => null,
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('####'),
            'position' => fake()->numberBetween(1, 20),
            'is_active' => true,
        ];
    }

    /**
     * Categoria nomeada do catálogo (Alianças Tradicionais, Solitários, Acessórios).
     * Slug determinístico: uma linha por nome — chamar duas vezes com o mesmo nome viola o UNIQUE(slug).
     */
    public function comNome(string $name): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => $name,
            'slug' => Str::slug($name),
        ]);
    }

    public function filhaDe(Category $parent): static
    {
        return $this->state(fn (array $attributes): array => [
            'parent_id' => $parent->getKey(),
        ]);
    }

    public function inativa(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
