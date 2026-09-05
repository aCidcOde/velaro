<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Emite a NF-e do lote na serie 1 com numero unico, ainda pendente e sem PDF nem XML transmitidos.
*/

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\OrderBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // A nota e do lote (decisao 1.3): `invoice_items` e que amarra pedido a pedido.
            'batch_id' => OrderBatch::factory(),
            // UNIQUE(series, number): a serie e fixa, o numero e que anda.
            'series' => '1',
            'number' => fake()->unique()->numerify('######'),
            'amount' => fake()->randomFloat(2, 1200, 48000),
            // Nota ainda nao transmitida: por isso issued_at, pdf_path e xml_path ficam nulos.
            'status' => Invoice::STATUS_PENDING,
        ];
    }
}
