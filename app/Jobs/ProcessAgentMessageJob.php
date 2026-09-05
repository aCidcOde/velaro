<?php

namespace App\Jobs;

use App\Models\AgentConversation;
use App\Models\AgentMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class ProcessAgentMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public int $conversationId,
        public int $messageId,
    ) {}

    public function handle(): void
    {
        $conversation = AgentConversation::query()
            ->with(['messages' => fn ($query) => $query->oldest('id')])
            ->find($this->conversationId);

        if (! $conversation instanceof AgentConversation) {
            return;
        }

        $message = $conversation->messages->firstWhere('id', $this->messageId);

        if (! $message instanceof AgentMessage || $message->role !== 'user') {
            return;
        }

        $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $this->buildReply($message->content),
            'metadata' => [
                'type' => 'template-example-response',
            ],
        ]);

        $conversation->forceFill([
            'last_message_at' => now(),
            'status' => 'open',
        ])->save();
    }

    private function buildReply(string $content): string
    {
        $normalized = Str::of($content)->lower()->ascii()->toString();

        $topics = [];

        if (str_contains($normalized, 'cliente')) {
            $topics[] = 'clientes';
        }

        if (str_contains($normalized, 'produto')) {
            $topics[] = 'produtos';
        }

        if (str_contains($normalized, 'pedido')) {
            $topics[] = 'pedidos';
        }

        if (str_contains($normalized, 'job') || str_contains($normalized, 'fila') || str_contains($normalized, 'rotina')) {
            $topics[] = 'jobs e rotinas';
        }

        if ($topics === []) {
            $topics[] = 'dashboard';
        }

        $topicList = implode(', ', array_unique($topics));

        return "Este Velaro IA e um exemplo local assíncrono da base SaaS. Posso orientar a operação sobre {$topicList}. "
            .'Use esta implementação como referência para substituir a lógica por integrações reais no próximo projeto.';
    }
}
