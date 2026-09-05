<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Gera saldo de um aro em um cofre com minimo e reposicao; states cobrem cofre fixo, zerado e baixo saldo.
*/

namespace Database\Factories;

use App\Models\ProductVariant;
use App\Models\StockItem;
use App\Models\StockLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockItem>
 */
class StockItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $onHand = (int) fake()->numberBetween(12, 180);
        $reservedQty = (int) fake()->numberBetween(0, min(12, $onHand));
        $minimum = (int) fake()->numberBetween(4, 20);

        return [
            'product_variant_id' => ProductVariant::factory(),
            'stock_location_id' => StockLocation::factory(),
            'on_hand' => $onHand,
            'reserved' => $reservedQty,
            'available' => $onHand - $reservedQty,
            'minimum' => $minimum,
            'restock_point' => $minimum * 3,
        ];
    }

    public function forVariant(ProductVariant $variant): static
    {
        return $this->state(fn (array $attributes): array => [
            'product_variant_id' => $variant->getKey(),
        ]);
    }

    /**
     * Fixa o cofre — o UNIQUE(product_variant_id, stock_location_id) é uma linha de saldo por cofre.
     */
    public function atLocation(StockLocation $location): static
    {
        return $this->state(fn (array $attributes): array => [
            'stock_location_id' => $location->getKey(),
        ]);
    }

    /**
     * Saldo zerado — o aro sai da vitrine e entra na fila de produção.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes): array => [
            'on_hand' => 0,
            'reserved' => 0,
            'available' => 0,
        ]);
    }

    /**
     * Saldo abaixo do mínimo — alimenta o KPI "Baixo estoque" da tela de estoque.
     */
    public function lowStock(): static
    {
        return $this->state(function (array $attributes): array {
            $minimum = (int) fake()->numberBetween(6, 20);
            $onHand = (int) fake()->numberBetween(1, $minimum - 1);

            return [
                'on_hand' => $onHand,
                'reserved' => 0,
                'available' => $onHand,
                'minimum' => $minimum,
                'restock_point' => $minimum * 3,
            ];
        });
    }
}
