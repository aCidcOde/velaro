<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $greeting = match (true) {
            now()->hour < 12 => 'Bom dia',
            now()->hour < 18 => 'Boa tarde',
            default => 'Boa noite',
        };

        $ordersByStatus = Order::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $dailyOrders = Order::query()
            ->selectRaw('DATE(created_at) as order_date, COUNT(*) as total')
            ->whereDate('created_at', '>=', now()->subDays(6)->toDateString())
            ->groupBy('order_date')
            ->pluck('total', 'order_date');

        $recentOrders = Order::query()
            ->with(['user:id,name,email', 'customer:id,name'])
            ->latest()
            ->limit(5)
            ->get();

        $stats = [
            'users' => User::query()->where('is_admin', false)->count(),
            'customers' => Customer::query()->count(),
            'products' => Product::query()->count(),
            'orders' => Order::query()->count(),
            'month_orders' => Order::query()->whereBetween('created_at', [$monthStart, $monthEnd])->count(),
            'month_volume' => Order::query()->whereBetween('created_at', [$monthStart, $monthEnd])->sum('total_amount'),
        ];

        $statusChart = collect([
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ])->map(function (string $label, string $status) use ($ordersByStatus): array {
            return [
                'label' => $label,
                'short' => Str::upper(Str::substr($label, 0, 3)),
                'value' => (int) $ordersByStatus->get($status, 0),
            ];
        })->values();

        $activityChart = collect(range(6, 0))->map(function (int $offset) use ($dailyOrders): array {
            $date = now()->subDays($offset);

            return [
                'label' => Str::upper($date->translatedFormat('D')),
                'value' => (int) ($dailyOrders->get($date->toDateString()) ?? 0),
            ];
        })->values();

        $monthProgress = $stats['orders'] > 0
            ? round(($stats['month_orders'] / $stats['orders']) * 100, 2)
            : 0.0;

        return view('backend.dashboard', [
            'greeting' => $greeting,
            'stats' => $stats,
            'ordersByStatus' => $ordersByStatus,
            'recentOrders' => $recentOrders,
            'statusChart' => $statusChart,
            'activityChart' => $activityChart,
            'monthProgress' => $monthProgress,
        ]);
    }
}
