<?php

namespace App\Services;

use App\Models\AgentUpload;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class AgentUploadStatusService
{
    public function synchronizePendingUploads(int $limit = 200): int
    {
        $uploads = AgentUpload::query()
            ->where('status', AgentUpload::STATUS_PROCESSING)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $staleCutoff = now()->subSeconds($this->staleProcessingSeconds());

        foreach ($uploads as $upload) {
            $storedDisk = trim((string) $upload->stored_disk);
            $storedPath = trim((string) $upload->stored_path);

            if ($storedDisk !== '' && $storedPath !== '' && Storage::disk($storedDisk)->exists($storedPath)) {
                $upload->update([
                    'status' => AgentUpload::STATUS_PROCESSED,
                    'processed_at' => $upload->processed_at ?? now(),
                    'last_checked_at' => now(),
                    'error_message' => null,
                ]);

                continue;
            }

            $this->markStaleQueueFailure($upload, $staleCutoff);
        }

        return $uploads->count();
    }

    private function staleProcessingSeconds(): int
    {
        $seconds = (int) config('services.agent_uploads.stale_processing_seconds', 900);

        return max($seconds, 60);
    }

    private function markStaleQueueFailure(AgentUpload $upload, Carbon $staleCutoff): void
    {
        $createdAt = $upload->created_at;

        if (! $createdAt instanceof Carbon || $createdAt->gt($staleCutoff)) {
            return;
        }

        $upload->update([
            'status' => AgentUpload::STATUS_FAILED,
            'last_checked_at' => now(),
            'error_message' => $upload->error_message ?: 'Upload nao foi processado dentro da janela esperada.',
        ]);
    }
}
