<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderUpdateRequest;
use App\Models\Order;
use App\Models\Product;
use App\Services\AdminAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private readonly AdminAuditLogger $auditLogger) {}

    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();

        $orders = Order::query()
            ->with(['user:id,name,email', 'customer:id,name'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('public_number', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('email', 'like', "%{$search}%"));
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('backend.orders.index', [
            'orders' => $orders,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['user:id,name,email', 'customer', 'items.product']);

        return view('backend.orders.show', [
            'order' => $order,
        ]);
    }

    public function update(OrderUpdateRequest $request, Order $order): RedirectResponse
    {
        $before = $order->toArray();
        $data = $request->validated();
        $customer = $order->user->customers()->find($data['customer_id']);

        if ($customer === null) {
            throw ValidationException::withMessages([
                'customer_id' => 'O cliente selecionado nao pertence ao dono do pedido.',
            ]);
        }

        $products = $order->user->products()
            ->whereIn('id', collect($data['items'])->pluck('product_id'))
            ->get()
            ->keyBy('id');

        if ($products->count() !== collect($data['items'])->pluck('product_id')->unique()->count()) {
            throw ValidationException::withMessages([
                'items' => 'Os produtos selecionados precisam pertencer ao mesmo usuario do pedido.',
            ]);
        }

        DB::transaction(function () use ($order, $customer, $products, $data): void {
            $order->update([
                'customer_id' => $customer->id,
                'reference' => $data['reference'] ?? null,
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
                'meta' => $data['meta'] ?? null,
            ]);

            $totalAmount = 0;
            $order->items()->delete();

            foreach ($data['items'] as $item) {
                /** @var Product $product */
                $product = $products->get((int) $item['product_id']);
                $quantity = max(1, (int) $item['quantity']);
                $unitPrice = array_key_exists('unit_price', $item) && $item['unit_price'] !== null
                    ? (float) $item['unit_price']
                    : (float) $product->price;
                $lineTotal = round($quantity * $unitPrice, 2);
                $totalAmount += $lineTotal;

                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $lineTotal,
                    'status' => $item['status'] ?? 'pending',
                    'meta' => $item['meta'] ?? null,
                ]);
            }

            $order->update([
                'total_amount' => round($totalAmount, 2),
            ]);
        });

        $this->auditLogger->log('backend.order.update', $order, $before, $order->fresh()?->toArray());

        return redirect()
            ->route('backend.orders.show', $order)
            ->with('status', 'Pedido atualizado com sucesso.');
    }

    public function destroy(Order $order): RedirectResponse
    {
        $this->auditLogger->log('backend.order.destroy', $order, $order->toArray(), null);

        $order->delete();

        return redirect()
            ->route('backend.orders.index')
            ->with('status', 'Pedido removido com sucesso.');
    }
}
