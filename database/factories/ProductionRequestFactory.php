<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Abre reposicao de um aro com prazo e prioridade; states cobrem o ciclo da bancada, o cofre e o solicitante.
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
            'status' => ProductionRequest::STATUS_PENDING,
            'priority' => ProductionRequest::PRIORITY_NORMAL,
            'due_date' => fake()->dateTimeBetween('+7 days', '+45 days'),
            'note' => 'Reposição do aro — saldo abaixo do mínimo no cofre da matriz.',
            'requested_by' => null,
        ];
    }

    public function forVariant(ProductVariant $variant): static
    {
        return $this->state(fn (array $attributes): array => [
            'product_variant_id' => $variant->getKey(),
        ]);
    }

    /**
     * Cofre de destino da produção — nulo cai no local padrão.
     */
    public function forLocation(?StockLocation $location = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'stock_location_id' => $location?->getKey() ?? StockLocation::factory(),
        ]);
    }

    public function requestedBy(?User $user = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'requested_by' => $user?->getKey() ?? User::factory(),
        ]);
    }

    /**
     * Solicitação já na bancada: aceita e em execução, sem nada entregue ainda.
     */
    public function inProduction(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProductionRequest::STATUS_IN_PRODUCTION,
            'qty_delivered' => 0,
            'completed_at' => null,
        ]);
    }

    /**
     * Solicitação atendida por inteiro: `qty_delivered` alcança `qty_requested` e a data
     * de conclusão é o que fecha o ciclo — sem ela a linha continua em aberto no relatório.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes): array {
            $requested = max(1, (int) ($attributes['qty_requested'] ?? 10));

            return [
                'status' => ProductionRequest::STATUS_COMPLETED,
                'qty_delivered' => $requested,
                'completed_at' => now(),
            ];
        });
    }

    /**
     * Solicitação cancelada antes de virar peça: nada entregue e nada concluído.
     */
    public function canceled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProductionRequest::STATUS_CANCELED,
            'qty_delivered' => 0,
            'completed_at' => null,
        ]);
    }

    /**
     * Fura a fila da bancada — prazo curto, prioridade máxima.
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'priority' => ProductionRequest::PRIORITY_URGENT,
            'due_date' => now()->addDays(fake()->numberBetween(2, 7))->startOfDay(),
        ]);
    }

    /**
     * Parte do lote já entregue pela bancada — qty_delivered nunca passa de qty_requested.
     */
    public function partiallyDelivered(): static
    {
        return $this->state(function (array $attributes): array {
            $requested = max(2, (int) ($attributes['qty_requested'] ?? 10));

            return [
                'qty_delivered' => fake()->numberBetween(1, $requested - 1),
            ];
        });
    }
}
