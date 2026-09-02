<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Mobile\OrderStoreRequest;
use App\Http\Requests\Api\Mobile\OrderUpdateRequest;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use App\Services\OrderWorkflowStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderWorkflowStatusService $workflowService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $orders = $user->orders()
            ->with(['customer:id,name', 'items.product:id,name'])
            ->latest()
            ->get();

        return response()->json([
            'data' => $orders->map(fn (Order $order): array => $this->payload($order))->values(),
        ]);
    }

    public function store(OrderStoreRequest $request): JsonResponse
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

        return response()->json([
            'data' => $this->payload($order),
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()?->id, 404);
        $order->load(['customer', 'items.product']);

        return response()->json([
            'data' => $this->payload($order),
        ]);
    }

    public function update(OrderUpdateRequest $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()?->id, 404);

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

        $order->refresh()->load(['customer', 'items.product']);

        return response()->json([
            'data' => $this->payload($order),
        ]);
    }

    public function destroy(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()?->id, 404);

        $order->delete();

        return response()->json([
            'message' => 'Pedido removido com sucesso.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Order $order): array
    {
        return [
            'id' => $order->id,
            'number' => $order->public_number,
            'customer' => [
                'id' => $order->customer?->id,
                'name' => $order->customer?->name,
            ],
            'reference' => $order->reference,
            'status' => $order->status,
            'total_amount' => (float) $order->total_amount,
            'currency' => $order->currency,
            'notes' => $order->notes,
            'items' => $order->items->map(fn ($item): array => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total_price' => (float) $item->total_price,
                'status' => $item->status,
            ])->values(),
            'created_at' => $order->created_at?->toIso8601String(),
            'updated_at' => $order->updated_at?->toIso8601String(),
        ];
    }
}
