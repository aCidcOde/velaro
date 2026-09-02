<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'company_name' => fake()->boolean(30) ? fake()->company() : null,
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'document' => fake()->bothify('DOC-####'),
            'notes' => fake()->boolean(50) ? fake()->sentence() : null,
            'meta' => [
                'segment' => fake()->randomElement(['standard', 'priority', 'partner']),
            ],
        ];
    }
}
