<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Monta remessa aguardando liberacao com transportadora e rastreio; state amarra ao lote e ao dono dele.
*/

namespace Database\Factories;

use App\Models\OrderBatch;
use App\Models\Reseller;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shipment>
 */
class ShipmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Servico e prefixo de rastreio andam juntos: SEDEX e PAC nao compartilham faixa.
        [$carrier, $prefix] = fake()->randomElement([
            ['Correios SEDEX', 'SW'],
            ['Correios PAC', 'PB'],
        ]);

        $trackingCode = $prefix.fake()->numerify('#########').'BR';

        return [
            // Formato do protocolo de remessa: REM-2026-0421.
            'code' => 'REM-'.now()->format('Y').'-'.fake()->unique()->numerify('####'),
            // FK nullable: a remessa nasce sem lote e ganha um em forBatch().
            'order_batch_id' => null,
            'reseller_id' => Reseller::factory(),
            'status' => Shipment::STATUS_AWAITING_RELEASE,
            'carrier' => $carrier,
            'tracking_code' => $trackingCode,
            'tracking_url' => 'https://rastreamento.correios.com.br/app/index.php?objeto='.$trackingCode,
            'estimated_at' => now()->addDays(fake()->numberBetween(3, 12))->startOfDay(),
        ];
    }

    /**
     * Remessa de um lote: o revendedor da remessa e sempre o dono do lote.
     */
    public function forBatch(OrderBatch $batch): static
    {
        return $this->state(fn (array $attributes): array => [
            'order_batch_id' => $batch->getKey(),
            'reseller_id' => $batch->reseller_id,
        ]);
    }
}
