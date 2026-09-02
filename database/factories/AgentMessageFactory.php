<?php

namespace Database\Factories;

use App\Models\AgentConversation;
use App\Models\AgentMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgentMessage>
 */
class AgentMessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => AgentConversation::factory(),
            'role' => fake()->randomElement(['user', 'assistant']),
            'content' => fake()->sentence(12),
            'metadata' => [
                'source' => fake()->randomElement(['queue', 'seed']),
            ],
        ];
    }
}
