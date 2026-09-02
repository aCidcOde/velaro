<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $recentOrders = $user->orders()
            ->with(['customer:id,name', 'items.product:id,name'])
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'stats' => [
                'customers' => $user->customers()->count(),
                'products' => $user->products()->count(),
                'orders' => $user->orders()->count(),
                'completed_orders' => $user->orders()->where('status', 'completed')->count(),
            ],
            'recent_orders' => $recentOrders->map(fn (Order $order): array => [
                'id' => $order->id,
                'number' => $order->public_number,
                'status' => $order->status,
                'reference' => $order->reference,
                'total_amount' => (float) $order->total_amount,
                'currency' => $order->currency,
                'customer' => [
                    'id' => $order->customer?->id,
                    'name' => $order->customer?->name,
                ],
                'items_count' => $order->items->count(),
                'created_at' => $order->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }
}
