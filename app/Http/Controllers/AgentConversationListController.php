<?php

/*
[Modulo: app/Http/Controllers]
@Author: André Gomes ( @acidcode )
@since 2026-01-31
Lista conversas do agente para a sidebar.
*/

namespace App\Http\Controllers;

use App\Models\AgentConversation;
use App\Models\AgentMessage;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class AgentConversationListController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $cookieSessionId = $this->resolveSessionIdFromCookie($request);
        $sessionId = $this->resolveSessionId($request);

        if ($user === null && $cookieSessionId !== '') {
            $sessionId = $cookieSessionId;
        }

        if ($user === null && $sessionId === '') {
            $request->session()->start();
            $sessionId = $this->resolveSessionId($request);
        }

        if ($user === null && $sessionId === '') {
            return response()->json([
                'conversations' => [],
            ]);
        }

        $query = AgentConversation::query()
            ->select([
                'agent_conversations.id',
                'agent_conversations.user_id',
                'agent_conversations.title',
                'agent_conversations.session_id',
                'agent_conversations.status',
                'agent_conversations.last_message_at',
                'agent_conversations.created_at',
            ])
            ->addSelect([
                'fallback_title' => AgentMessage::query()
                    ->select('content')
                    ->whereColumn('conversation_id', 'agent_conversations.id')
                    ->where('role', 'user')
                    ->orderBy('id')
                    ->limit(1),
                'preview' => AgentMessage::query()
                    ->select('content')
                    ->whereColumn('conversation_id', 'agent_conversations.id')
                    ->whereIn('role', ['user', 'assistant'])
                    ->orderByDesc('id')
                    ->limit(1),
            ]);

        if ($user !== null) {
            $query->where('user_id', $user->id);
        } else {
            $query->whereNull('user_id')->where('session_id', $sessionId);
        }

        $conversations = $query
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return response()->json([
            'conversations' => $conversations->map(static function (AgentConversation $conversation): array {
                $title = trim((string) ($conversation->title ?? $conversation->fallback_title ?? ''));
                $preview = trim((string) ($conversation->preview ?? ''));

                return [
                    'id' => $conversation->id,
                    'title' => $title !== '' ? Str::limit($title, 60, '') : 'Conversa',
                    'preview' => $preview !== '' ? Str::limit($preview, 80, '') : null,
                    'last_message_at' => $conversation->last_message_at?->toISOString(),
                    'status' => $conversation->status,
                ];
            })->values(),
        ]);
    }

    private function resolveSessionId(Request $request): string
    {
        $sessionId = (string) $request->session()->getId();

        if ($sessionId !== '') {
            return $sessionId;
        }

        return $this->resolveSessionIdFromCookie($request);
    }

    private function resolveSessionIdFromCookie(Request $request): string
    {
        $cookie = $request->cookies->get(config('session.cookie'));

        if (! is_string($cookie) || $cookie === '') {
            return '';
        }

        try {
            $decrypted = app('encrypter')->decrypt($cookie, false);

            return CookieValuePrefix::remove($decrypted);
        } catch (Throwable) {
            return $cookie;
        }
    }
}
