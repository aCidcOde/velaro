<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Cria o SKU por aro da peca; states fixam um aro ou distribuem aros consecutivos sem repetir nenhum.
*/

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $largura = (string) fake()->randomElement(['3', '4', '5', '6', '8']);

        return [
            'product_id' => Product::factory(),
            'sku' => 'ALC-'.$largura.'MM-'.strtoupper(fake()->unique()->bothify('??##')),
            'aro' => (string) fake()->numberBetween(8, 34),
            'is_active' => true,
        ];
    }

    public function paraProduto(Product $product): static
    {
        return $this->state(fn (array $attributes): array => [
            'product_id' => $product->getKey(),
        ]);
    }

    /**
     * Fixa o aro (tamanho do anel, 8 a 34) — respeita o UNIQUE(product_id, aro).
     */
    public function comAro(int|string $aro): static
    {
        return $this->state(fn (array $attributes): array => [
            'aro' => (string) $aro,
        ]);
    }

    /**
     * Distribui aros consecutivos a partir de $inicial, dando a volta na faixa 8–34.
     * Percorre os 27 aros antes de repetir qualquer um — que é o teto do
     * UNIQUE(product_id, aro), então count() acima de 27 no mesmo produto não existe.
     * Use ao criar várias variantes de uma vez:
     * ProductVariant::factory()->count(6)->paraProduto($produto)->arosSequenciais().
     */
    public function arosSequenciais(int $inicial = 10): static
    {
        $primeiro = max(8, min(34, $inicial));
        $faixa = 34 - 8 + 1;

        return $this->sequence(fn (Sequence $sequence): array => [
            'aro' => (string) (8 + (($primeiro - 8 + $sequence->index) % $faixa)),
        ]);
    }

    public function inativa(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
