<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Abre reposicao de um aro com prazo e prioridade; states dao o cofre, o solicitante e a entrega parcial.
*/

namespace Database\Factories;

use App\Models\ProductionRequest;
use App\Models\ProductVariant;
use App\Models\StockLocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionRequest>
 */
class ProductionRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_variant_id' => ProductVariant::factory(),
            'stock_location_id' => null,
            'qty_requested' => fake()->numberBetween(4, 60),
            'qty_delivered' => 0,
            'status' => ProductionRequest::STATUS_PENDENTE,
            'priority' => ProductionRequest::PRIORITY_NORMAL,
            'due_date' => fake()->dateTimeBetween('+7 days', '+45 days'),
            'note' => 'Reposição do aro — saldo abaixo do mínimo no cofre da matriz.',
            'requested_by' => null,
        ];
    }

    public function paraVariante(ProductVariant $variante): static
    {
        return $this->state(fn (array $attributes): array => [
            'product_variant_id' => $variante->getKey(),
        ]);
    }

    /**
     * Cofre de destino da produção — nulo cai no local padrão.
     */
    public function paraLocal(?StockLocation $local = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'stock_location_id' => $local?->getKey() ?? StockLocation::factory(),
        ]);
    }

    public function solicitadaPor(?User $user = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'requested_by' => $user?->getKey() ?? User::factory(),
        ]);
    }

    /**
     * Parte do lote já entregue pela bancada — qty_delivered nunca passa de qty_requested.
     */
    public function parcialmenteEntregue(): static
    {
        return $this->state(function (array $attributes): array {
            $solicitado = max(2, (int) ($attributes['qty_requested'] ?? 10));

            return [
                'qty_delivered' => fake()->numberBetween(1, $solicitado - 1),
            ];
        });
    }
}
