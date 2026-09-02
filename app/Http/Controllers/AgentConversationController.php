<?php

namespace App\Http\Controllers;

use App\Models\AgentConversation;
use App\Models\AgentMessage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentConversationController extends Controller
{
    public function __invoke(Request $request, AgentConversation $conversation): JsonResponse
    {
        if (! $this->canViewConversation($request, $conversation)) {
            return response()->json([
                'message' => 'Conversa nao encontrada.',
            ], 404);
        }

        $after = max(0, (int) $request->integer('after'));

        $messages = $conversation->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->when($after > 0, static function ($query) use ($after): void {
                $query->where('id', '>', $after);
            })
            ->oldest('id')
            ->get(['id', 'role', 'content', 'created_at']);

        /** @var Collection<int, AgentMessage> $messages */

        return response()->json([
            'conversation_id' => $conversation->id,
            'status' => $conversation->status,
            'title' => $conversation->title,
            'messages' => $messages->map(static function (AgentMessage $message): array {
                return [
                    'id' => $message->id,
                    'role' => $message->role,
                    'content' => $message->content,
                    'created_at' => $message->created_at?->toISOString(),
                ];
            })->values(),
        ]);
    }

    private function canViewConversation(Request $request, AgentConversation $conversation): bool
    {
        $user = $request->user();

        if ($user !== null) {
            return (int) $conversation->user_id === (int) $user->id;
        }

        return $conversation->user_id === null
            && $conversation->session_id === $request->session()->getId();
    }
}
