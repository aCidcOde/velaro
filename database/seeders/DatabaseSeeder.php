<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminPassword = env('ADMIN_SEED_PASSWORD', Str::random(16));

        User::updateOrCreate(
            ['email' => env('ADMIN_SEED_EMAIL', 'admin@example.com')],
            [
                'name' => 'Admin',
                'phone' => '(11) 99999-0000',
                'document' => 'ADMIN001',
                'password' => Hash::make($adminPassword),
                'is_admin' => true,
                'is_agent' => true,
                'email_verified_at' => now(),
            ],
        );

        if (! env('ADMIN_SEED_PASSWORD')) {
            $this->command?->info("Admin seed password: {$adminPassword}");
        }

        Artisan::call('acl:sync-backend', ['--no-interaction' => true]);

        $owner = User::factory()
            ->withoutTwoFactor()
            ->create([
                'name' => 'Conta Exemplo',
                'email' => 'owner@example.com',
                'phone' => '(11) 98888-7777',
                'document' => 'OWNER001',
                'is_agent' => true,
            ]);

        $customers = Customer::factory()->count(2)->for($owner)->create();
        $products = Product::factory()->count(3)->for($owner)->create();

        $order = Order::factory()->for($owner)->for($customers->first())->create([
            'status' => 'in_progress',
            'notes' => 'Pedido seed para demonstrar o template-base.',
        ]);

        OrderItem::factory()->for($order)->for($products->first())->create([
            'quantity' => 2,
            'unit_price' => 120,
            'total_price' => 240,
            'status' => 'processing',
        ]);

        $order->update([
            'total_amount' => $order->items()->sum('total_price'),
        ]);
    }
}
