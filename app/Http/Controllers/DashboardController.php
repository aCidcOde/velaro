<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $orders = $user
            ->orders()
            ->with(['customer:id,name', 'items.product:id,name'])
            ->latest()
            ->limit(10)
            ->get();

        $stats = [
            'customers' => $user->customers()->count(),
            'products' => $user->products()->count(),
            'orders' => $user->orders()->count(),
            'completed_orders' => $user->orders()->where('status', 'completed')->count(),
        ];

        $statusBreakdown = Order::query()
            ->where('user_id', $user->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $dailyOrders = $user
            ->orders()
            ->selectRaw('DATE(created_at) as order_date, COUNT(*) as total')
            ->whereDate('created_at', '>=', now()->subDays(6)->toDateString())
            ->groupBy('order_date')
            ->pluck('total', 'order_date');

        $statusChart = collect([
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ])->map(function (string $label, string $status) use ($statusBreakdown): array {
            return [
                'label' => $label,
                'short' => Str::upper(Str::substr($label, 0, 3)),
                'value' => (int) $statusBreakdown->get($status, 0),
            ];
        })->values();

        $activityChart = collect(range(6, 0))->map(function (int $offset) use ($dailyOrders): array {
            $date = now()->subDays($offset);

            return [
                'label' => Str::upper($date->translatedFormat('D')),
                'value' => (int) ($dailyOrders->get($date->toDateString()) ?? 0),
            ];
        })->values();

        $completionRate = $stats['orders'] > 0
            ? round(($stats['completed_orders'] / $stats['orders']) * 100, 2)
            : 0.0;

        return view('dashboard', [
            'orders' => $orders,
            'stats' => $stats,
            'statusBreakdown' => $statusBreakdown,
            'statusChart' => $statusChart,
            'activityChart' => $activityChart,
            'completionRate' => $completionRate,
        ]);
    }
}
