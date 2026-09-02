<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\View\View;

class AboutController extends Controller
{
    public function __invoke(): View
    {
        return view('about', [
            'stats' => [
                'customers' => Customer::query()->count(),
                'products' => Product::query()->count(),
                'orders' => Order::query()->count(),
            ],
        ]);
    }
}
