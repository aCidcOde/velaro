<?php

namespace App\Http\Controllers;

use App\Models\AgentUpload;
use App\Models\User;
use App\Services\AgentUploadStatusService;
use App\Support\AgentSidebarConversations;
use App\Support\AgentUploadLimits;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AgentPageController extends Controller
{
    public function __construct(
        private AgentSidebarConversations $sidebarConversations,
        private AgentUploadStatusService $agentUploadStatusService,
        private AgentUploadLimits $agentUploadLimits,
    ) {}

    public function chat(): View
    {
        return view('agente', [
            'initialConversations' => $this->sidebarConversations->forUser(auth()->user()),
        ]);
    }

    public function files(): View
    {
        $this->agentUploadStatusService->synchronizePendingUploads(100);

        return view('agente-arquivos', [
            'initialConversations' => $this->sidebarConversations->forUser(auth()->user()),
            'initialUploads' => $this->latestUploads(),
            'uploadLimits' => $this->agentUploadLimits->payload(),
        ]);
    }

    private function latestUploads(): Collection
    {
        /** @var EloquentCollection<int, AgentUpload> $uploads */
        $uploads = AgentUpload::query()
            ->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return $uploads->map(static function (AgentUpload $upload): array {
            $user = $upload->user;
            $uploadedBy = $user instanceof User
                ? trim((string) ($user->name ?: $user->email ?: 'Sistema'))
                : 'Sistema';
            $createdAt = $upload->created_at;
            $processedAt = $upload->processed_at;

            return [
                'id' => $upload->id,
                'original_name' => $upload->original_name,
                'status' => $upload->status,
                'size_bytes' => $upload->size_bytes,
                'uploaded_by' => $uploadedBy,
                'created_at' => $createdAt instanceof Carbon ? $createdAt->toISOString() : null,
                'stored_path' => $upload->stored_path,
                'processed_at' => $processedAt instanceof Carbon ? $processedAt->toISOString() : null,
                'error_message' => $upload->error_message,
            ];
        })
            ->values();
    }
}
