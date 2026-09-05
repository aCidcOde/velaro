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
        [$transportadora, $prefixo] = fake()->randomElement([
            ['Correios SEDEX', 'SW'],
            ['Correios PAC', 'PB'],
        ]);

        $rastreio = $prefixo.fake()->numerify('#########').'BR';

        return [
            // Formato do protocolo de remessa: REM-2026-0421.
            'code' => 'REM-'.now()->format('Y').'-'.fake()->unique()->numerify('####'),
            // FK nullable: a remessa nasce sem lote e ganha um em paraLote().
            'order_batch_id' => null,
            'reseller_id' => Reseller::factory(),
            'status' => Shipment::STATUS_AGUARDANDO_LIBERACAO,
            'carrier' => $transportadora,
            'tracking_code' => $rastreio,
            'tracking_url' => 'https://rastreamento.correios.com.br/app/index.php?objeto='.$rastreio,
            'estimated_at' => now()->addDays(fake()->numberBetween(3, 12))->startOfDay(),
        ];
    }

    /**
     * Remessa de um lote: o revendedor da remessa e sempre o dono do lote.
     */
    public function paraLote(OrderBatch $lote): static
    {
        return $this->state(fn (array $attributes): array => [
            'order_batch_id' => $lote->getKey(),
            'reseller_id' => $lote->reseller_id,
        ]);
    }
}
