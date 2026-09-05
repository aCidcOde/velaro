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
        $atual = (int) fake()->numberBetween(12, 180);
        $reservado = (int) fake()->numberBetween(0, min(12, $atual));
        $minimo = (int) fake()->numberBetween(4, 20);

        return [
            'product_variant_id' => ProductVariant::factory(),
            'stock_location_id' => StockLocation::factory(),
            'atual' => $atual,
            'reservado' => $reservado,
            'disponivel' => $atual - $reservado,
            'minimo' => $minimo,
            'reposicao' => $minimo * 3,
        ];
    }

    public function paraVariante(ProductVariant $variante): static
    {
        return $this->state(fn (array $attributes): array => [
            'product_variant_id' => $variante->getKey(),
        ]);
    }

    /**
     * Fixa o cofre — o UNIQUE(product_variant_id, stock_location_id) é uma linha de saldo por cofre.
     */
    public function noLocal(StockLocation $local): static
    {
        return $this->state(fn (array $attributes): array => [
            'stock_location_id' => $local->getKey(),
        ]);
    }

    /**
     * Saldo zerado — o aro sai da vitrine e entra na fila de produção.
     */
    public function semEstoque(): static
    {
        return $this->state(fn (array $attributes): array => [
            'atual' => 0,
            'reservado' => 0,
            'disponivel' => 0,
        ]);
    }

    /**
     * Saldo abaixo do mínimo — alimenta o KPI "Baixo estoque" da tela de estoque.
     */
    public function baixoEstoque(): static
    {
        return $this->state(function (array $attributes): array {
            $minimo = (int) fake()->numberBetween(6, 20);
            $atual = (int) fake()->numberBetween(1, $minimo - 1);

            return [
                'atual' => $atual,
                'reservado' => 0,
                'disponivel' => $atual,
                'minimo' => $minimo,
                'reposicao' => $minimo * 3,
            ];
        });
    }
}
