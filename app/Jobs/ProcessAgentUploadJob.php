<?php

namespace App\Jobs;

use App\Models\AgentUpload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ProcessAgentUploadJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $uploadId) {}

    public function handle(): void
    {
        $upload = AgentUpload::query()->find($this->uploadId);

        if (! $upload instanceof AgentUpload || $upload->status !== AgentUpload::STATUS_PROCESSING) {
            return;
        }

        $diskName = (string) config('services.agent_uploads.disk', 'local');
        $disk = Storage::disk($diskName);
        $localPath = (string) ($upload->local_path ?? '');

        if ($localPath === '' || ! Storage::disk('local')->exists($localPath)) {
            $upload->update([
                'status' => AgentUpload::STATUS_FAILED,
                'last_checked_at' => now(),
                'error_message' => 'Arquivo local nao encontrado para processamento.',
            ]);

            return;
        }

        $processedDirectory = trim((string) config('services.agent_uploads.processed_directory', 'agent-uploads/processed'), '/');
        $processedPath = $processedDirectory.'/'.basename($localPath);

        $stream = Storage::disk('local')->readStream($localPath);

        if ($stream === false) {
            $upload->update([
                'status' => AgentUpload::STATUS_FAILED,
                'last_checked_at' => now(),
                'error_message' => 'Nao foi possivel ler o arquivo enviado.',
            ]);

            return;
        }

        $disk->writeStream($processedPath, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        $upload->update([
            'status' => AgentUpload::STATUS_PROCESSED,
            'stored_disk' => $diskName,
            'stored_path' => $processedPath,
            'processed_at' => now(),
            'last_checked_at' => now(),
            'error_message' => null,
        ]);
    }
}
