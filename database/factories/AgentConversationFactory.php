<?php

namespace Database\Factories;

use App\Models\AgentConversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgentConversation>
 */
class AgentConversationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->agent(),
            'title' => fake()->sentence(3),
            'session_id' => fake()->uuid(),
            'status' => fake()->randomElement(['open', 'waiting', 'error']),
            'last_message_at' => now(),
        ];
    }
}
