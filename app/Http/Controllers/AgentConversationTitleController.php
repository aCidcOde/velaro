<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgentConversationTitleRequest;
use App\Models\AgentConversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class AgentConversationTitleController extends Controller
{
    public function __invoke(AgentConversationTitleRequest $request, AgentConversation $conversation): JsonResponse
    {
        $user = $request->user();

        if ($user === null || (int) $conversation->user_id !== (int) $user->id) {
            return response()->json([
                'message' => 'Conversa nao encontrada.',
            ], 404);
        }

        $title = Str::of($request->string('title')->toString())
            ->squish()
            ->limit(120, '')
            ->toString();

        $conversation->forceFill(['title' => $title])->save();

        return response()->json([
            'ok' => true,
            'conversation_id' => $conversation->id,
            'title' => $conversation->title,
        ]);
    }
}
