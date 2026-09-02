<?php

/*
app/Http/Controllers
@Author: André Gomes ( @acidcode )
@since 2026-02-03
Retorna a ultima mensagem do agente para polling curto.
*/

namespace App\Http\Controllers;

use App\Http\Requests\AgentLatestMessageRequest;
use App\Models\AgentConversation;
use Illuminate\Http\JsonResponse;

class AgentLatestMessageController extends Controller
{
    public function __invoke(AgentLatestMessageRequest $request): JsonResponse
    {
        $sessionId = $this->resolveSessionId($request);
        $conversation = $this->resolveConversation($request, $sessionId);

        if (! $conversation) {
            return response()->json(['found' => false]);
        }

        $after = $request->integer('after');

        $messageQuery = $conversation->messages()
            ->where('role', 'assistant');

        if ($after !== 0) {
            $messageQuery->where('id', '>', $after);
        }

        $message = $messageQuery->latest('id')->first();

        if (! $message) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'message' => $message->content,
            'message_id' => $message->id,
            'conversation_id' => $conversation->id,
        ]);
    }

    private function resolveSessionId(AgentLatestMessageRequest $request): string
    {
        $session = $request->session();
        $sessionId = $session->getId();

        if ($sessionId === '') {
            $session->start();
            $sessionId = $session->getId();
        }

        return $sessionId;
    }

    private function resolveConversation(AgentLatestMessageRequest $request, string $sessionId): ?AgentConversation
    {
        $conversationId = $request->integer('conversation_id');
        $user = $request->user();

        if ($conversationId !== 0) {
            $query = AgentConversation::query()->where('id', $conversationId);

            if ($user !== null) {
                $query->where('user_id', $user->id);
            } else {
                $query->whereNull('user_id')->where('session_id', $sessionId);
            }

            return $query->first();
        }

        return AgentConversation::query()
            ->where('session_id', $sessionId)
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->first();
    }
}
