<?php

/*
[Modulo: app/Console/Commands]
@Author: André Gomes ( @acidcode )
@since 2026-02-20
Preenche numero publico aleatorio de 8 digitos em pedidos legados sem identificador externo.
*/

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;

class BackfillOrderPublicNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:backfill-public-numbers {--chunk=500 : Quantidade de registros por lote}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Preenche public_number em pedidos sem numero publico.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $chunkSize = max((int) $this->option('chunk'), 1);
        $processed = 0;
        $updated = 0;

        Order::query()
            ->whereNull('public_number')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($orders) use (&$processed, &$updated): void {
                foreach ($orders as $order) {
                    $processed++;

                    if ($this->assignPublicNumberWithRetry($order)) {
                        $updated++;
                    }
                }
            });

        $this->info("Processados: {$processed}");
        $this->info("Atualizados: {$updated}");

        return self::SUCCESS;
    }

    private function assignPublicNumberWithRetry(Order $order): bool
    {
        for ($attempt = 1; $attempt <= 20; $attempt++) {
            $candidate = Order::generatePublicNumberCandidate();

            try {
                $affectedRows = Order::query()
                    ->whereKey($order->id)
                    ->whereNull('public_number')
                    ->update(['public_number' => $candidate]);
            } catch (QueryException $exception) {
                if (str_contains(strtolower($exception->getMessage()), 'unique')) {
                    continue;
                }

                throw $exception;
            }

            if ($affectedRows === 1) {
                return true;
            }

            $alreadyAssigned = Order::query()
                ->whereKey($order->id)
                ->whereNotNull('public_number')
                ->exists();

            if ($alreadyAssigned) {
                return false;
            }
        }

        $this->warn("Não foi possível gerar chave única para o pedido #{$order->id} após 20 tentativas.");

        return false;
    }
}
