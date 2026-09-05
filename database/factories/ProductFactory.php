<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Finish;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'slug' => fake()->unique()->slug(),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####')),
            'description' => fake()->sentence(12),
            'price' => fake()->randomFloat(2, 25, 500),
            'is_active' => true,
            // Taxonomia Velaro fica nula no default: produto do scaffold nao arrasta
            // colecao, categoria, material e acabamento para os testes do template.
            'collection_id' => null,
            'category_id' => null,
            'material_id' => null,
            'finish_id' => null,
            'meta' => [
                'category' => fake()->randomElement(['service', 'subscription', 'package']),
            ],
        ];
    }

    /**
     * Produto do catalogo Velaro: taxonomia completa e ficha tecnica da alianca.
     */
    public function daVelaro(): static
    {
        return $this->state(fn (array $attributes): array => [
            'collection_id' => ProductCollection::factory(),
            'category_id' => Category::factory(),
            'material_id' => Material::factory(),
            'finish_id' => Finish::factory(),
            'largura_mm' => fake()->randomElement([4, 5, 6, 8]),
            'formato' => fake()->randomElement(['Reta', 'Anatômica']),
            'permite_gravacao' => true,
            'gravacao_max_chars' => fake()->randomElement([20, 25, 30]),
        ]);
    }
}
