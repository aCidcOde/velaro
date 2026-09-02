<?php

namespace Database\Factories;

use App\Models\AgentUpload;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgentUpload>
 */
class AgentUploadFactory extends Factory
{
    protected $model = AgentUpload::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->agent(),
            'original_name' => 'arquivo-'.$this->faker->unique()->numerify('###').'.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 120000,
            'status' => AgentUpload::STATUS_PROCESSING,
            'local_path' => 'agent-uploads/'.$this->faker->uuid().'.pdf',
            'stored_disk' => null,
            'stored_path' => null,
            'processed_at' => null,
            'last_checked_at' => null,
            'error_message' => null,
            'metadata' => null,
        ];
    }
}
