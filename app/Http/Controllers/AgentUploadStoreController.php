<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgentUploadStoreRequest;
use App\Jobs\ProcessAgentUploadJob;
use App\Models\AgentUpload;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

class AgentUploadStoreController extends Controller
{
    public function __invoke(AgentUploadStoreRequest $request): JsonResponse
    {
        /** @var array<int, UploadedFile> $files */
        $files = $request->file('files', []);
        $user = $request->user();
        $uploads = [];

        foreach ($files as $file) {
            $path = $file->store('agent-uploads', 'local');

            $upload = AgentUpload::query()->create([
                'user_id' => $user?->id,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?: 'application/pdf',
                'size_bytes' => (int) $file->getSize(),
                'status' => AgentUpload::STATUS_PROCESSING,
                'local_path' => $path,
                'metadata' => [
                    'source' => 'velaro-agent',
                ],
            ]);

            ProcessAgentUploadJob::dispatch($upload->id);
            /** @var AgentUpload $freshUpload */
            $freshUpload = $upload->fresh('user') ?? $upload;
            $uploads[] = $this->mapUpload($freshUpload);
        }

        return response()->json([
            'ok' => true,
            'uploads' => $uploads,
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
        ];
    }
}
