<?php

namespace App\Http\Controllers;

use App\Models\AgentUpload;
use App\Models\User;
use App\Services\AgentUploadStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AgentUploadListController extends Controller
{
    public function __invoke(Request $request, AgentUploadStatusService $agentUploadStatusService): JsonResponse
    {
        $agentUploadStatusService->synchronizePendingUploads(100);

        $perPage = (int) $request->integer('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        $uploads = AgentUpload::query()
            ->where('user_id', $request->user()?->id)
            ->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'uploads' => $uploads->getCollection()->map(fn (AgentUpload $upload): array => $this->mapUpload($upload))->values(),
            'meta' => [
                'current_page' => $uploads->currentPage(),
                'last_page' => $uploads->lastPage(),
                'per_page' => $uploads->perPage(),
                'total' => $uploads->total(),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapUpload(AgentUpload $upload): array
    {
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
            'can_delete' => $upload->status === AgentUpload::STATUS_FAILED,
        ];
    }
}
