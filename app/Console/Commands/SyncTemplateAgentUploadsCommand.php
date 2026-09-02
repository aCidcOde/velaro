<?php

namespace App\Console\Commands;

use App\Services\AgentUploadStatusService;
use Illuminate\Console\Command;

class SyncTemplateAgentUploadsCommand extends Command
{
    protected $signature = 'agent:sync-uploads {--limit=200 : Maximum pending uploads to inspect}';

    protected $description = 'Sincroniza o status dos uploads do módulo CodaFácil IA.';

    public function handle(AgentUploadStatusService $agentUploadStatusService): int
    {
        $processed = $agentUploadStatusService->synchronizePendingUploads(
            max(1, (int) $this->option('limit'))
        );

        $this->info("Uploads verificados: {$processed}");

        return self::SUCCESS;
    }
}
