<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'customer_id' => Customer::factory(),
            'public_number' => 'ORD'.fake()->unique()->numerify('######'),
            'reference' => strtoupper(fake()->bothify('REF-####')),
            'status' => fake()->randomElement(['draft', 'awaiting_payment', 'paid', 'in_progress', 'completed', 'canceled', 'error']),
            'total_amount' => fake()->randomFloat(2, 0, 500),
            'currency' => 'BRL',
            'notes' => fake()->sentence(),
            'meta' => [
                'channel' => fake()->randomElement(['web', 'mobile', 'admin']),
            ],
        ];
    }
}
