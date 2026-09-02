<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_id' => User::factory()->admin(),
            'action' => $this->faker->randomElement([
                'user.updated',
                'product.updated',
                'customer.updated',
                'order.updated',
                'agent.message.processed',
            ]),
            'target_type' => User::class,
            'target_id' => User::factory(),
            'before' => [
                'name' => $this->faker->name(),
            ],
            'after' => [
                'name' => $this->faker->name(),
            ],
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }
}
