<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderStoreRequest;
use App\Http\Requests\OrderUpdateRequest;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use App\Services\OrderWorkflowStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrdersController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderWorkflowStatusService $workflowService,
    ) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();

        $orders = $user->orders()
            ->with(['customer:id,name', 'items.product:id,name'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('public_number', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('orders.index', [
            'orders' => $orders,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function create(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('orders.create', [
            'order' => new Order(['status' => 'draft']),
            'customers' => $user->customers()->orderBy('name')->get(),
            'products' => $user->products()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(OrderStoreRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();
        $customer = $this->orderService->resolveOwnedCustomer($user, (int) $data['customer_id']);
        $products = $this->orderService->resolveOwnedProducts($user, $data['items']);

        $order = DB::transaction(function () use ($user, $customer, $products, $data): Order {
            $order = $user->orders()->create([
                'customer_id' => $customer->id,
                'reference' => $data['reference'] ?? null,
                'status' => 'draft',
                'currency' => 'BRL',
                'notes' => $data['notes'] ?? null,
                'meta' => $data['meta'] ?? null,
                'total_amount' => 0,
            ]);

            $this->orderService->syncItems($order, $data['items'], $products);

            return $order->fresh(['customer', 'items.product']) ?? $order;
        });

        return redirect()
            ->route('orders.show', $order)
            ->with('status', 'Pedido criado com sucesso.');
    }

    public function show(Order $order): View
    {
        abort_unless($order->user_id === auth()->id(), 404);

        $order->load(['customer', 'items.product']);

        return view('orders.show', [
            'order' => $order,
        ]);
    }

    public function edit(Order $order, Request $request): View
    {
        abort_unless($order->user_id === auth()->id(), 404);

        /** @var User $user */
        $user = $request->user();
        $order->load(['customer', 'items.product']);

        return view('orders.edit', [
            'order' => $order,
            'customers' => $user->customers()->orderBy('name')->get(),
            'products' => $user->products()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(OrderUpdateRequest $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === auth()->id(), 404);

        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();
        $customer = $this->orderService->resolveOwnedCustomer($user, (int) $data['customer_id']);
        $products = $this->orderService->resolveOwnedProducts($user, $data['items']);

        if ($data['status'] !== $order->status) {
            $this->workflowService->assertTransition($order->status, $data['status']);
        }

        DB::transaction(function () use ($order, $customer, $products, $data): void {
            $order->update([
                'customer_id' => $customer->id,
                'reference' => $data['reference'] ?? null,
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
                'meta' => $data['meta'] ?? null,
            ]);

            $this->orderService->syncItems($order, $data['items'], $products);
        });

        return redirect()
            ->route('orders.show', $order)
            ->with('status', 'Pedido atualizado com sucesso.');
    }

    public function destroy(Order $order): RedirectResponse
    {
        abort_unless($order->user_id === auth()->id(), 404);

        $order->delete();

        return redirect()
            ->route('orders.index')
            ->with('status', 'Pedido removido com sucesso.');
    }
}
