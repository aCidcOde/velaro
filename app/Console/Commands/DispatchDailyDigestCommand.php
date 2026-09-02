<?php

namespace App\Console\Commands;

use App\Models\AgentConversation;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DispatchDailyDigestCommand extends Command
{
    protected $signature = 'template:dispatch-daily-digest';

    protected $description = 'Gera um resumo operacional diário da base genérica.';

    public function handle(): int
    {
        $summary = sprintf(
            'Resumo diario: %d clientes, %d produtos e %d pedidos criados hoje.',
            Customer::query()->whereDate('created_at', today())->count(),
            Product::query()->whereDate('created_at', today())->count(),
            Order::query()->whereDate('created_at', today())->count(),
        );

        Log::info('template.daily_digest', [
            'summary' => $summary,
        ]);

        $agentUser = User::query()
            ->where('is_agent', true)
            ->orderByDesc('is_admin')
            ->orderBy('id')
            ->first();

        if ($agentUser instanceof User) {
            $conversation = AgentConversation::query()->create([
                'user_id' => $agentUser->id,
                'title' => 'Resumo operacional diário',
                'status' => 'open',
                'last_message_at' => now(),
            ]);

            $conversation->messages()->createMany([
                [
                    'role' => 'user',
                    'content' => 'Gerar resumo operacional diário.',
                    'metadata' => ['type' => 'scheduled-digest-request'],
                ],
                [
                    'role' => 'assistant',
                    'content' => $summary,
                    'metadata' => ['type' => 'scheduled-digest-response'],
                ],
            ]);
        }

        $this->info($summary);

        return self::SUCCESS;
    }
}
