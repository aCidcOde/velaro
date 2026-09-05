<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackendOrderHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_read_order_and_items_after_owner_is_deleted(): void
    {
        $admin = $this->createBackendAdmin();
        $order = $this->orderWithItem();
        $order->user->delete();

        $this->assertNull($order->fresh()->user_id);
        $this->actingAs($admin)->get(route('backend.orders.show', $order))
            ->assertOk()->assertSee('Histórico preservado')->assertSee('Aliança histórica')
            ->assertSee('120,00')->assertDontSee('Salvar alterações');
    }

    public function test_admin_cannot_modify_order_without_owner(): void
    {
        $admin = $this->createBackendAdmin();
        $order = $this->orderWithItem();
        $item = $order->items()->firstOrFail();
        $order->user->delete();

        $this->actingAs($admin)->putJson(route('backend.orders.update', $order), [
            'customer_id' => $order->customer_id, 'status' => 'paid', 'notes' => 'Alteração indevida',
            'items' => [['product_id' => $item->product_id, 'quantity' => 1, 'unit_price' => 10]],
        ])->assertConflict()->assertJsonPath('message', 'Este pedido não pode ser editado porque a conta responsável foi excluída. O histórico permanece disponível para consulta.');

        $this->assertSame('draft', $order->fresh()->status);
        $this->assertSame('240.00', $order->fresh()->total_amount);
        $this->assertSame($item->id, $order->items()->firstOrFail()->id);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'backend.order.update']);
    }

    public function test_owned_orders_remain_editable(): void
    {
        $admin = $this->createBackendAdmin();
        $order = $this->orderWithItem();
        $item = $order->items()->firstOrFail();

        $this->actingAs($admin)->get(route('backend.orders.show', $order))
            ->assertOk()->assertSee('Salvar alterações')->assertDontSee('Histórico preservado');

        $this->putJson(route('backend.orders.update', $order), [
            'customer_id' => $order->customer_id, 'status' => 'awaiting_payment',
            'items' => [['product_id' => $item->product_id, 'quantity' => 2, 'unit_price' => 120]],
        ])->assertRedirect();
        $this->assertSame('awaiting_payment', $order->fresh()->status);
    }

    private function orderWithItem(): Order
    {
        $owner = User::factory()->create();
        $customer = Customer::factory()->for($owner)->create();
        $product = Product::factory()->for($owner)->create(['name' => 'Aliança histórica', 'price' => 120]);
        $order = Order::factory()->for($owner)->for($customer)->create(['status' => 'draft', 'total_amount' => 240]);
        OrderItem::factory()->for($order)->for($product)->create([
            'quantity' => 2, 'unit_price' => 120, 'total_price' => 240,
        ]);

        return $order;
    }
}
