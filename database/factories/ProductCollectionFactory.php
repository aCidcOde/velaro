<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Monta colecao comercial (Classic, Diamond, Premium) com capa e slug; state cobre colecao aposentada.
*/

namespace Database\Factories;

use App\Models\ProductCollection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductCollection>
 */
class ProductCollectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = (string) fake()->randomElement([
            'Classic',
            'Diamond',
            'Premium',
            'Essenza',
            'Infinity',
            'Eterna',
            'Nobre',
            'Alliance',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('####'),
            'description' => 'Coleção '.$name.' — alianças e anéis de compromisso da Velaro.',
            'cover_path' => 'colecoes/'.Str::slug($name).'/capa.webp',
            'position' => fake()->numberBetween(1, 20),
            'is_active' => true,
        ];
    }

    /**
     * Coleção nomeada do catálogo (Classic, Diamond, Premium) com slug derivado do nome.
     * Slug determinístico: uma linha por nome — chamar duas vezes com o mesmo nome viola o UNIQUE(slug).
     */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => 'Coleção '.$name.' — alianças e anéis de compromisso da Velaro.',
            'cover_path' => 'colecoes/'.Str::slug($name).'/capa.webp',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
