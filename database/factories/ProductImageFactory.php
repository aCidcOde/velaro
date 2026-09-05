<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Gera foto da galeria com caminho, texto alternativo e ordem; state marca a imagem de capa da peca.
*/

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductImage>
 */
class ProductImageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $peca = (string) fake()->randomElement([
            'Aliança Clássica',
            'Aliança Anatômica',
            'Aliança Diamantada',
            'Solitário Clássico',
            'Anel Aparador',
        ]);
        $largura = (int) fake()->randomElement([3, 4, 5, 6, 8]);
        $angulo = (string) fake()->randomElement([
            'vista frontal',
            'vista lateral',
            'par sobreposto',
            'detalhe do acabamento',
        ]);

        return [
            'product_id' => Product::factory(),
            'path' => 'produtos/'.Str::slug($peca.' '.$largura.'mm').'/'.fake()->unique()->numerify('foto-####').'.webp',
            'alt' => $peca.' '.$largura.'mm — '.$angulo,
            'position' => fake()->numberBetween(1, 6),
            'is_primary' => false,
        ];
    }

    public function paraProduto(Product $product): static
    {
        return $this->state(fn (array $attributes): array => [
            'product_id' => $product->getKey(),
        ]);
    }

    /**
     * Foto de capa do produto — primeira posição da galeria.
     */
    public function principal(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_primary' => true,
            'position' => 0,
        ]);
    }
}
