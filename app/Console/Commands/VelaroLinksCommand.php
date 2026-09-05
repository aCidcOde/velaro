<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderBatch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Models\Reseller;
use App\Models\ResellerStore;
use App\Models\SupportTicket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

/**
 * Imprime a URL de cada tela da plataforma, com parâmetros resolvidos a partir
 * do banco (a loja, um pedido, um chamado... do cenário semeado). É a lista que
 * se entrega para revisão — e o que o smoke test percorre.
 */
class VelaroLinksCommand extends Command
{
    protected $signature = 'velaro:links {--json : Saída em JSON} {--secao=* : Seções de configuração a listar}';

    protected $description = 'Lista todos os links das telas Velaro com parâmetros de exemplo';

    public function handle(): int
    {
        $store = ResellerStore::query()->where('is_active', true)->first() ?? ResellerStore::query()->first();
        $pre = Reseller::query()->where('status', 'pre_cadastro')->whereNotNull('protocolo')->first()
            ?? Reseller::query()->whereNotNull('protocolo')->first();
        $aprovado = Reseller::query()->where('status', 'aprovado')->first() ?? Reseller::query()->first();

        $exemplo = [
            'store' => $store?->slug,
            'product' => Product::query()->whereNotNull('slug')->value('slug'),
            'order' => Order::query()->whereNotNull('public_number')->value('public_number'),
            'ticket' => SupportTicket::query()->whereNotNull('code')->value('code'),
            'customer' => Customer::query()->value('id'),
            'batch' => OrderBatch::query()->value('id'),
            'variant' => ProductVariant::query()->value('id'),
            'promotion' => Promotion::query()->value('id'),
        ];

        $telas = config('velaro-telas');
        $secoes = $this->option('secao') ?: ['empresa', 'usuarios', 'notificacoes', 'integracoes', 'seguranca', 'financeiro', 'personalizacao', 'backup'];
        $linhas = [];

        foreach (Route::getRoutes()->getRoutesByName() as $nome => $rota) {
            if (! isset($telas[$nome]) && ! in_array($nome, ['login', 'password.request'], true)) {
                continue;
            }
            if (! in_array('GET', $rota->methods(), true)) {
                continue;
            }

            $params = [];
            foreach ($rota->parameterNames() as $p) {
                $params[$p] = match ($p) {
                    'reseller' => str_starts_with($nome, 'site.') ? $pre?->protocolo : $aprovado?->id,
                    'colecao' => null,
                    'secao' => null,
                    default => $exemplo[$p] ?? null,
                };
            }

            $variantes = $nome === 'backend.configuracoes.secao' ? $secoes : [null];
            foreach ($variantes as $secao) {
                if ($secao !== null) {
                    $params['secao'] = $secao;
                }
                $faltando = array_keys(array_filter($params, fn ($v, $k) => $v === null && $k !== 'colecao', ARRAY_FILTER_USE_BOTH));
                $url = $faltando === [] ? route($nome, array_filter($params, fn ($v) => $v !== null)) : null;
                [$n, $titulo] = $telas[$nome] ?? ['0', $nome === 'login' ? 'Login' : 'Recuperar senha'];
                $linhas[] = [
                    'tela' => $n,
                    'titulo' => $secao ? "{$titulo} — {$secao}" : $titulo,
                    'rota' => $nome,
                    'acesso' => str_starts_with($nome, 'portal.') ? 'lojista' : (str_starts_with($nome, 'backend.') ? 'master' : 'público'),
                    'url' => $url ?? '(sem dado para '.implode(', ', $faltando).')',
                ];
            }
        }

        usort($linhas, fn ($a, $b) => [$a['acesso'], $a['tela'], $a['rota']] <=> [$b['acesso'], $b['tela'], $b['rota']]);

        if ($this->option('json')) {
            $this->line(json_encode($linhas, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->table(['Tela', 'Título', 'Acesso', 'URL'], array_map(fn ($l) => [$l['tela'], $l['titulo'], $l['acesso'], $l['url']], $linhas));
        $this->info(count($linhas).' links.');

        return self::SUCCESS;
    }
}
