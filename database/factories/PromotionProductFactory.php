<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Aponta a campanha para um produto avulso ou para a colecao inteira, nunca para os dois ao mesmo tempo.
*/

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\Promotion;
use App\Models\PromotionProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromotionProduct>
 */
class PromotionProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // O alvo e produto OU colecao, nunca os dois. O default aponta para produto
        // e zera `collection_id` de proposito, para o registro nascer valido tambem no make().
        return [
            'promotion_id' => Promotion::factory(),
            'product_id' => Product::factory(),
            'collection_id' => null,
        ];
    }

    /**
     * Alvo = produto avulso do catalogo.
     */
    public function paraProduto(?Product $product = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'product_id' => $product instanceof Product ? $product->getKey() : Product::factory(),
            'collection_id' => null,
        ]);
    }

    /**
     * Alvo = colecao inteira ("Selecionar colecao inteira" da tela 3.8).
     */
    public function paraColecao(?ProductCollection $collection = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'product_id' => null,
            'collection_id' => $collection instanceof ProductCollection
                ? $collection->getKey()
                : ProductCollection::factory(),
        ]);
    }
}
