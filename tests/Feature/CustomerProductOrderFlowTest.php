<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerProductOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_user_can_manage_customers_products_and_orders(): void
    {
        $user = User::factory()->withoutTwoFactor()->create();

        $this->actingAs($user);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pedidos recentes');

        $this->post(route('customers.store'), [
            'name' => 'Cliente Base',
            'company_name' => 'Base LTDA',
            'email' => 'cliente@example.com',
            'phone' => '(11) 94444-1111',
            'document' => 'DOC-CL-001',
            'notes' => 'Conta criada para o fluxo web.',
        ])->assertRedirect(route('customers.index'));

        $customer = Customer::query()->firstOrFail();

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'user_id' => $user->id,
            'document' => 'DOCCL001',
        ]);

        $this->post(route('products.store'), [
            'name' => 'Plano Operacional',
            'sku' => 'SKU-BASE-01',
            'description' => 'Produto genérico do template.',
            'price' => 149.9,
            'is_active' => '1',
        ])->assertRedirect(route('products.index'));

        $product = Product::query()->firstOrFail();

        $this->post(route('orders.store'), [
            'customer_id' => $customer->id,
            'reference' => 'REF-BASE-001',
            'status' => 'draft',
            'notes' => 'Pedido criado no fluxo autenticado.',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 149.9,
                    'status' => 'pending',
                ],
            ],
        ])->assertRedirect();

        $order = Order::query()->with('items')->firstOrFail();

        $this->assertSame('draft', $order->status);
        $this->assertSame('299.80', $order->fresh()->total_amount);
        $this->assertCount(1, $order->items);

        $this->put(route('orders.update', $order), [
            'customer_id' => $customer->id,
            'reference' => 'REF-BASE-002',
            'status' => 'awaiting_payment',
            'notes' => 'Pedido enviado para pagamento.',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 199.5,
                    'status' => 'completed',
                ],
            ],
        ])->assertRedirect(route('orders.show', $order));

        $order->refresh()->load('items.product', 'customer');

        $this->assertSame('awaiting_payment', $order->status);
        $this->assertSame('199.50', $order->total_amount);
        $this->assertSame('completed', $order->items->firstOrFail()->status);

        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee($order->public_number)
            ->assertSee('REF-BASE-002')
            ->assertSee('Plano Operacional');
    }

    public function test_user_cannot_access_records_owned_by_another_account(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $foreignCustomer = Customer::factory()->for($anotherUser)->create();
        $foreignProduct = Product::factory()->for($anotherUser)->create();
        $foreignOrder = Order::factory()->for($anotherUser)->for($foreignCustomer)->create();

        $this->actingAs($user);

        $this->get(route('customers.edit', $foreignCustomer))->assertNotFound();
        $this->get(route('products.edit', $foreignProduct))->assertNotFound();
        $this->get(route('orders.show', $foreignOrder))->assertNotFound();
    }
}
