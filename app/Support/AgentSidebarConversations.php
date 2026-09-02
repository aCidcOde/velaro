<?php

namespace App\Support;

use App\Models\AgentConversation;
use App\Models\AgentMessage;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AgentSidebarConversations
{
    public function forUser(?User $user): Collection
    {
        if ($user === null) {
            return collect();
        }

        return AgentConversation::query()
            ->select([
                'agent_conversations.id',
                'agent_conversations.user_id',
                'agent_conversations.title',
                'agent_conversations.status',
                'agent_conversations.last_message_at',
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
            ])
            ->where('user_id', $user->id)
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(static function (AgentConversation $conversation): array {
                $title = trim((string) ($conversation->title ?? $conversation->fallback_title ?? ''));
                $preview = trim((string) ($conversation->preview ?? ''));
                $lastMessageAt = $conversation->last_message_at;

                if (is_string($lastMessageAt)) {
                    $lastMessageAt = Carbon::parse($lastMessageAt);
                }

                return [
                    'id' => $conversation->id,
                    'title' => $title !== '' ? Str::limit($title, 60, '') : 'Conversa',
                    'preview' => $preview !== '' ? Str::limit($preview, 80, '') : null,
                    'last_message_at' => $lastMessageAt?->toISOString(),
                    'status' => (string) $conversation->status,
                ];
            })
            ->toBase()
            ->values();
    }
}
