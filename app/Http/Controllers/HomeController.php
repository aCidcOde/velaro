<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('welcome', [
            'stats' => [
                'customers' => Customer::query()->count(),
                'products' => Product::query()->count(),
                'orders' => Order::query()->count(),
            ],
            'recentOrders' => Order::query()
                ->with(['customer:id,name', 'user:id,name'])
                ->latest()
                ->limit(6)
                ->get(),
        ]);
    }
}
