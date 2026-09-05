<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Rateia um pedido dentro da nota do lote, uma linha por pedido, respeitando o par nota+pedido unico.
*/

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // UNIQUE(invoice_id, order_id): cada pedido entra uma unica vez na nota.
            'invoice_id' => Invoice::factory(),
            'order_id' => Order::factory(),
            'amount' => fake()->randomFloat(2, 380, 7600),
        ];
    }
}
