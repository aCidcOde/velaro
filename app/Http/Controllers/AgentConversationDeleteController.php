<?php

namespace App\Http\Controllers;

use App\Models\AgentConversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentConversationDeleteController extends Controller
{
    public function __invoke(Request $request, AgentConversation $conversation): JsonResponse
    {
        $user = $request->user();

        if ($user === null || (int) $conversation->user_id !== (int) $user->id) {
            return response()->json([
                'message' => 'Conversa nao encontrada.',
            ], 404);
        }

        $conversationId = $conversation->id;
        $conversation->delete();

        return response()->json([
            'ok' => true,
            'conversation_id' => $conversationId,
        ]);
    }
}
