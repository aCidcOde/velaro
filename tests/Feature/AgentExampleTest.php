<?php

namespace Tests\Feature;

use App\Jobs\ProcessAgentMessageJob;
use App\Jobs\ProcessAgentUploadJob;
use App\Models\AgentConversation;
use App\Models\AgentMessage;
use App\Models\AgentUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AgentExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_routes_require_the_agent_flag(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('agent.dashboard'))
            ->assertForbidden();
    }

    public function test_agent_message_endpoint_creates_a_conversation_and_dispatches_a_job(): void
    {
        Bus::fake();

        $agent = User::factory()->agent()->create();

        $response = $this->actingAs($agent)->postJson(route('agent.message'), [
            'message' => 'Preciso de ajuda com clientes e pedidos.',
        ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('status', 'waiting');

        $conversationId = (int) $response->json('conversation_id');
        $conversation = AgentConversation::query()->findOrFail($conversationId);

        $this->assertSame($agent->id, $conversation->user_id);
        $this->assertSame('waiting', $conversation->status);
        $this->assertSame('Preciso de ajuda com clientes e pedidos.', $conversation->title);

        Bus::assertDispatched(ProcessAgentMessageJob::class, function (ProcessAgentMessageJob $job) use ($conversationId): bool {
            return $job->conversationId === $conversationId;
        });
    }

    public function test_process_agent_message_job_generates_a_local_reply(): void
    {
        $conversation = AgentConversation::factory()->create([
            'status' => 'waiting',
        ]);
        $message = AgentMessage::factory()->for($conversation, 'conversation')->create([
            'role' => 'user',
            'content' => 'Quero entender clientes, produtos e pedidos.',
        ]);

        (new ProcessAgentMessageJob($conversation->id, $message->id))->handle();

        $conversation->refresh();
        $assistantMessage = $conversation->messages()
            ->where('role', 'assistant')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('open', $conversation->status);
        $this->assertStringContainsString('clientes, produtos, pedidos', $assistantMessage->content);
    }

    public function test_agent_upload_endpoint_dispatches_processing_and_job_can_store_processed_file(): void
    {
        Storage::fake('local');
        Bus::fake();

        $agent = User::factory()->agent()->create();

        $response = $this->actingAs($agent)->post(route('agent.uploads.store'), [
            'files' => [
                UploadedFile::fake()->create('manual.pdf', 64, 'application/pdf'),
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('ok', true);

        $upload = AgentUpload::query()->firstOrFail();

        $this->assertSame(AgentUpload::STATUS_PROCESSING, $upload->status);
        Storage::disk('local')->assertExists($upload->local_path);

        Bus::assertDispatched(ProcessAgentUploadJob::class, function (ProcessAgentUploadJob $job) use ($upload): bool {
            return $job->uploadId === $upload->id;
        });

        (new ProcessAgentUploadJob($upload->id))->handle();

        $upload->refresh();

        $this->assertSame(AgentUpload::STATUS_PROCESSED, $upload->status);
        $this->assertNotNull($upload->stored_path);
        Storage::disk('local')->assertExists($upload->stored_path);
    }

    public function test_agent_upload_sync_command_marks_stale_processing_uploads_as_failed(): void
    {
        config(['services.agent_uploads.stale_processing_seconds' => 60]);

        $upload = AgentUpload::factory()->create([
            'status' => AgentUpload::STATUS_PROCESSING,
            'stored_disk' => null,
            'stored_path' => null,
            'created_at' => now()->subMinutes(5),
        ]);

        $this->artisan('agent:sync-uploads', ['--limit' => 10])
            ->assertExitCode(0);

        $upload->refresh();

        $this->assertSame(AgentUpload::STATUS_FAILED, $upload->status);
        $this->assertNotNull($upload->error_message);
    }

    public function test_daily_digest_command_creates_an_agent_conversation_example(): void
    {
        $agent = User::factory()->agent()->create();

        $this->artisan('template:dispatch-daily-digest')
            ->assertExitCode(0);

        $conversation = AgentConversation::query()
            ->where('user_id', $agent->id)
            ->where('title', 'Resumo operacional diário')
            ->firstOrFail();

        $this->assertCount(2, $conversation->messages);
        $this->assertSame('assistant', $conversation->messages()->latest('id')->firstOrFail()->role);
    }
}
