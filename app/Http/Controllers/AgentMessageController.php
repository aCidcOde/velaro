<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgentMessageRequest;
use App\Jobs\ProcessAgentMessageJob;
use App\Models\AgentConversation;
use App\Models\AgentMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AgentMessageController extends Controller
{
    public function __invoke(AgentMessageRequest $request): JsonResponse
    {
        $conversation = $this->resolveConversation($request);
        $userMessage = $this->maskSensitiveData($request->string('message')->toString());

        if ($this->shouldGenerateTitle($conversation)) {
            $conversation->forceFill([
                'title' => $this->generateConversationTitle($userMessage),
            ])->save();
        }

        /** @var AgentMessage $message */
        $message = $conversation->messages()->create([
            'role' => 'user',
            'content' => $userMessage,
        ]);

        $conversation->forceFill([
            'last_message_at' => now(),
            'status' => 'waiting',
        ])->save();

        ProcessAgentMessageJob::dispatch($conversation->id, $message->id);

        return response()->json([
            'ok' => true,
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
            'message_created_at' => $message->created_at?->toISOString(),
            'status' => $conversation->status,
            'wait_timeout_seconds' => $this->resolveWaitTimeout(),
        ]);
    }

    private function resolveConversation(Request $request): AgentConversation
    {
        $conversationId = $request->input('conversation_id');
        $session = $request->session();
        $sessionId = $session->getId();
        $user = $request->user();

        if ($user === null && $sessionId === '') {
            $session->start();
            $sessionId = $session->getId();
        }

        if ($conversationId !== null) {
            $query = AgentConversation::query()->where('id', $conversationId);

            if ($user !== null) {
                $query->where('user_id', $user->id);
            } else {
                $query->whereNull('user_id')->where('session_id', $sessionId);
            }

            $conversation = $query->first();

            if ($conversation !== null) {
                return $conversation;
            }
        }

        return AgentConversation::query()->create([
            'user_id' => $user?->id,
            'session_id' => $sessionId,
            'status' => 'open',
        ]);
    }

    private function maskSensitiveData(string $content): string
    {
        $patterns = [
            '/\\b\\d{3}\\.?\\d{3}\\.?\\d{3}-?\\d{2}\\b/' => '***.***.***-**',
            '/\\b\\d{2}\\.?\\d{3}\\.?\\d{3}\\/?\\d{4}-?\\d{2}\\b/' => '**.***.***/****-**',
            '/\\bRG\\s*[:\\-]?\\s*\\d{7,9}\\b/i' => 'RG ***',
        ];

        return (string) preg_replace(array_keys($patterns), array_values($patterns), $content);
    }

    private function shouldGenerateTitle(AgentConversation $conversation): bool
    {
        return trim((string) $conversation->title) === ''
            && ! $conversation->messages()->exists();
    }

    private function generateConversationTitle(string $message): string
    {
        $title = Str::of($message)
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->limit(60, '')
            ->toString();

        if ($title !== '') {
            return $title;
        }

        return 'Conversa';
    }

    private function resolveWaitTimeout(): int
    {
        $timeout = (int) config('services.agent.wait_timeout', 30);

        if ($timeout < 1) {
            return 30;
        }

        return $timeout;
    }
}
