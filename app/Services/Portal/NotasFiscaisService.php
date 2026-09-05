<?php

/*
[Modulo: app/Services/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Monta a tela de notas do Portal: filtro, lista das NF-e do lojista, resumo por competencia e dados fiscais.
*/

namespace App\Services\Portal;

use App\Http\Requests\Portal\NotasFiltroRequest;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\Reseller;
use App\Support\ResellerScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * As NF-e que a **Velaro emitiu contra o lojista** — a venda B2B fabrica → loja.
 * A nota que o consumidor final recebe e emitida pela propria loja e nao existe
 * neste ambiente; e a mesma fronteira do aviso dourado que a tela repete.
 *
 * O escopo vem de {@see FinanceiroService::notas()}: `invoices` nao tem
 * `reseller_id`, o documento pende do lote (decisao 1.3), e o lote e que tem
 * dono. Nenhuma consulta daqui parte de `Invoice::query()` sem esse filtro.
 */
class NotasFiscaisService
{
    /** Quantas competencias o cartao lateral resume. */
    private const COMPETENCIAS_NO_RESUMO = 3;

    /** Janela do KPI "Notas canceladas", em dias — o "últimos 90 dias" do prototipo. */
    private const JANELA_DE_CANCELAMENTOS = 90;

    public function __construct(
        private readonly FinanceiroService $financeiro,
        private readonly FinanceiroApresentacao $ui,
    ) {}

    /**
     * Dados de `/portal/financeiro/notas`.
     *
     * @param  array{q: string|null, aba: string, periodo: string, competencia: string|null, serie: string|null, por_pagina: int, pagina: int}  $filtros
     * @return array<string, mixed>
     */
    public function montar(ResellerScope $escopo, array $filtros): array
    {
        $notas = $this->listar($escopo, $filtros);

        return [
            'filtros' => $filtros,
            'parametros' => $this->parametrosDeRota($filtros),
            'algumFiltroAtivo' => $this->parametrosDeRota($filtros) !== [] || $filtros['aba'] !== NotasFiltroRequest::ABA_TODAS,
            'abas' => $this->abas($escopo, $filtros),
            'periodos' => NotasFiltroRequest::PERIODOS,
            'competencias' => $this->competenciasDisponiveis($escopo),
            'series' => $this->seriesDisponiveis($escopo),
            'tamanhos' => NotasFiltroRequest::POR_PAGINA,
            'kpis' => $this->kpis($escopo),
            'notas' => $notas,
            'linhas' => $this->linhas($notas),
            'resumoPorCompetencia' => $this->resumoPorCompetencia($escopo),
            'destinatario' => $this->destinatario($escopo->reseller),
        ];
    }

    /**
     * @param  array{q: string|null, aba: string, periodo: string, competencia: string|null, serie: string|null, por_pagina: int, pagina: int}  $filtros
     * @return LengthAwarePaginator<int, Invoice>
     */
    public function listar(ResellerScope $escopo, array $filtros): LengthAwarePaginator
    {
        return $this->filtrar($escopo, $filtros)
            ->with(['batch', 'items.order'])
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->paginate($filtros['por_pagina'], ['*'], 'page', $filtros['pagina'])
            ->withQueryString();
    }

    /**
     * Os quatro KPIs do topo. Como na 2.4, "este mes" e o mes do relogio.
     *
     * @return array<string, string>
     */
    public function kpis(ResellerScope $escopo): array
    {
        $inicioDoMes = Carbon::now()->startOfMonth();
        $fimDoMes = Carbon::now()->endOfMonth();

        $ultima = $this->financeiro->notas($escopo)
            ->whereNotNull('issued_at')
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->first();

        $canceladas = $this->financeiro->notas($escopo)
            ->where('status', Invoice::STATUS_CANCELED)
            ->where('issued_at', '>=', Carbon::now()->subDays(self::JANELA_DE_CANCELAMENTOS))
            ->count();

        return [
            'emitidas' => (string) $this->financeiro->notas($escopo)
                ->whereBetween('issued_at', [$inicioDoMes, $fimDoMes])
                ->count(),
            'faturado' => $this->ui->moeda($this->financeiro->notas($escopo)
                ->whereBetween('issued_at', [$inicioDoMes, $fimDoMes])
                ->sum('amount')),
            'ultima_emissao' => $ultima === null ? '—' : $this->ui->data($ultima->issued_at),
            'ultima_nota' => $ultima === null ? 'Nenhuma nota emitida' : 'NF-e '.$ultima->number,
            'canceladas' => (string) $canceladas,
        ];
    }

    /**
     * Linhas da tabela.
     *
     * "Pedido vinculado" sai do rateio: a nota e do lote e cobre varios pedidos,
     * entao a coluna mostra o primeiro e conta os demais em vez de fingir que a
     * NF-e pertence a um pedido so.
     *
     * @param  LengthAwarePaginator<int, Invoice>  $notas
     * @return list<array<string, mixed>>
     */
    public function linhas(LengthAwarePaginator $notas): array
    {
        /** @var list<Invoice> $itens */
        $itens = $notas->items();

        return array_map(function (Invoice $nota): array {
            $lote = $nota->batch;
            $pedidos = $nota->items
                ->map(static fn (InvoiceItem $rateio): ?Order => $rateio->order)
                ->filter(static fn (?Order $pedido): bool => $pedido instanceof Order)
                ->values();

            /** @var Order|null $primeiro */
            $primeiro = $pedidos->first();

            return [
                'numero' => 'NF-e '.$nota->number,
                'serie' => $nota->series ?? '—',
                'emissao' => $this->ui->data($nota->issued_at),
                'competencia' => $this->ui->competencia($nota->issued_at),
                'valor' => $this->ui->moeda($nota->amount),
                'lote' => $lote === null ? '—' : $this->ui->rotuloDoLote($lote),
                'lote_url' => $lote === null ? null : route('portal.financeiro.pagamento', $lote),
                'pedido' => $primeiro?->public_number,
                'pedido_url' => $primeiro === null ? null : route('portal.pedidos.show', $primeiro),
                'pedidos_restantes' => max(0, $pedidos->count() - 1),
                'status' => $this->ui->statusDaNota($nota->status),
                'cancelada' => $nota->status === Invoice::STATUS_CANCELED,
                'pdf' => $this->arquivo($nota->pdf_path),
                'xml' => $this->arquivo($nota->xml_path),
            ];
        }, $itens);
    }

    /**
     * Total faturado por mes, para o cartao "Resumo por competencia".
     *
     * @return list<array<string, string>>
     */
    public function resumoPorCompetencia(ResellerScope $escopo): array
    {
        $notas = $this->financeiro->notas($escopo)
            ->whereNotNull('issued_at')
            ->with('batch')
            ->orderByDesc('issued_at')
            ->get();

        $porMes = [];

        foreach ($notas as $nota) {
            $emissao = $nota->issued_at;

            if (! $emissao instanceof Carbon) {
                continue;
            }

            $chave = $emissao->format('Y-m');
            $porMes[$chave] ??= ['data' => $emissao, 'notas' => 0, 'total' => 0.0, 'lotes' => []];
            $porMes[$chave]['notas']++;
            $porMes[$chave]['total'] += (float) $nota->amount;

            if ($nota->batch !== null) {
                $porMes[$chave]['lotes'][] = $this->ui->rotuloDoLote($nota->batch);
            }
        }

        $resumo = [];

        foreach (array_slice($porMes, 0, self::COMPETENCIAS_NO_RESUMO, true) as $mes) {
            /** @var list<string> $lotes */
            $lotes = array_values(array_unique($mes['lotes']));
            sort($lotes);

            $resumo[] = [
                'competencia' => $this->ui->competencia($mes['data']),
                'detalhe' => $this->detalheDaCompetencia((int) $mes['notas'], $lotes),
                'total' => $this->ui->moeda($mes['total']),
            ];
        }

        return $resumo;
    }

    /**
     * Dados fiscais do lojista — o destinatario das notas. Sao os campos do
     * cadastro do revendedor: a tela nao guarda uma segunda copia deles.
     *
     * @return array<string, string>
     */
    public function destinatario(Reseller $revendedor): array
    {
        $endereco = array_filter([
            trim(($revendedor->street ?? '').' '.($revendedor->street_number ?? '')),
            $revendedor->district,
            trim(($revendedor->city ?? '').($revendedor->state !== null ? ' / '.$revendedor->state : '')),
        ], static fn (?string $parte): bool => $parte !== null && trim($parte) !== '');

        return [
            'razao_social' => $revendedor->legal_name,
            'cnpj' => (string) ($revendedor->cnpj ?? '—'),
            'inscricao_estadual' => $revendedor->state_registration ?? 'Isento',
            'endereco' => $endereco === [] ? '—' : implode(' - ', $endereco),
        ];
    }

    /**
     * Consulta filtrada — o mesmo alicerce da lista e das contagens das abas.
     *
     * @param  array{q: string|null, aba: string, periodo: string, competencia: string|null, serie: string|null, por_pagina: int, pagina: int}  $filtros
     * @return Builder<Invoice>
     */
    private function filtrar(ResellerScope $escopo, array $filtros, bool $comAba = true): Builder
    {
        $consulta = $this->financeiro->notas($escopo);

        if ($comAba && $filtros['aba'] !== NotasFiltroRequest::ABA_TODAS) {
            $consulta->where('status', $filtros['aba']);
        }

        if ($filtros['serie'] !== null) {
            $consulta->where('series', $filtros['serie']);
        }

        if ($filtros['competencia'] !== null) {
            $mes = Carbon::parse($filtros['competencia'].'-01');

            $consulta->whereBetween('issued_at', [$mes->copy()->startOfMonth(), $mes->copy()->endOfMonth()]);
        }

        $dias = (int) $filtros['periodo'];

        if ($dias > 0) {
            $consulta->where('issued_at', '>=', Carbon::now()->subDays($dias));
        }

        $busca = $filtros['q'];

        if ($busca !== null) {
            // Numero da NF-e, codigo do lote ou numero publico do pedido: os tres
            // identificadores com que o lojista chega nesta tela. O pedido entra
            // pelo rateio, que e o que liga documento fiscal e pedido.
            // O pedido entra pelo rateio, e o rateio ja esta preso a nota, que ja
            // esta presa aos lotes do lojista: nao ha caminho daqui para o pedido
            // de outro revendedor.
            $consulta->where(function (Builder $agrupada) use ($busca): void {
                $agrupada
                    ->where('number', 'like', '%'.$busca.'%')
                    ->orWhereHas('batch', fn (Builder $lote): Builder => $lote->where('code', 'like', '%'.$busca.'%'))
                    ->orWhereHas('items.order', fn (Builder $pedido): Builder => $pedido->where('public_number', 'like', '%'.$busca.'%'));
            });
        }

        return $consulta;
    }

    /**
     * Abas com a contagem de cada estado, respeitando os demais filtros.
     *
     * @param  array{q: string|null, aba: string, periodo: string, competencia: string|null, serie: string|null, por_pagina: int, pagina: int}  $filtros
     * @return list<array{chave: string, rotulo: string, ativa: bool, total: int, url: string}>
     */
    private function abas(ResellerScope $escopo, array $filtros): array
    {
        $parametros = $this->parametrosDeRota($filtros);
        $abas = [];

        foreach (NotasFiltroRequest::ABAS as $chave => $rotulo) {
            $consulta = $this->filtrar($escopo, $filtros, comAba: false);

            if ($chave !== NotasFiltroRequest::ABA_TODAS) {
                $consulta->where('status', $chave);
            }

            $abas[] = [
                'chave' => $chave,
                'rotulo' => $rotulo,
                'ativa' => $chave === $filtros['aba'],
                'total' => $consulta->count(),
                'url' => route(
                    'portal.financeiro.notas',
                    $chave === NotasFiltroRequest::ABA_TODAS ? $parametros : $parametros + ['aba' => $chave],
                ),
            ];
        }

        return $abas;
    }

    /**
     * Os filtros que sobrevivem a troca de aba e de pagina.
     *
     * @param  array{q: string|null, aba: string, periodo: string, competencia: string|null, serie: string|null, por_pagina: int, pagina: int}  $filtros
     * @return array<string, string>
     */
    private function parametrosDeRota(array $filtros): array
    {
        $parametros = [];

        foreach (['q', 'periodo', 'competencia', 'serie'] as $campo) {
            $valor = $filtros[$campo];

            if (is_string($valor) && $valor !== '' && ! ($campo === 'periodo' && $valor === NotasFiltroRequest::PERIODO_PADRAO)) {
                $parametros[$campo] = $valor;
            }
        }

        if ($filtros['por_pagina'] !== NotasFiltroRequest::POR_PAGINA_PADRAO) {
            $parametros['por_pagina'] = (string) $filtros['por_pagina'];
        }

        return $parametros;
    }

    /**
     * Competencias que realmente tem nota, para o `select` nao oferecer mes vazio.
     *
     * @return array<string, string>
     */
    private function competenciasDisponiveis(ResellerScope $escopo): array
    {
        $datas = $this->financeiro->notas($escopo)
            ->whereNotNull('issued_at')
            ->orderByDesc('issued_at')
            ->pluck('issued_at');

        $opcoes = [];

        foreach ($datas as $data) {
            if ($data instanceof Carbon) {
                $opcoes[$data->format('Y-m')] = $this->ui->competencia($data);
            }
        }

        return $opcoes;
    }

    /**
     * @return list<string>
     */
    private function seriesDisponiveis(ResellerScope $escopo): array
    {
        /** @var list<string> $series */
        $series = $this->financeiro->notas($escopo)
            ->whereNotNull('series')
            ->distinct()
            ->orderBy('series')
            ->pluck('series')
            ->all();

        return $series;
    }

    /**
     * "3 notas · lotes 21 a 23" — o subtitulo de cada competencia no resumo.
     *
     * @param  list<string>  $lotes
     */
    private function detalheDaCompetencia(int $notas, array $lotes): string
    {
        $detalhe = $notas.($notas === 1 ? ' nota' : ' notas');

        if ($lotes === []) {
            return $detalhe;
        }

        $primeiro = (string) reset($lotes);
        $ultimo = (string) end($lotes);

        return $detalhe.' · '.($primeiro === $ultimo ? 'lote '.$primeiro : 'lotes '.$primeiro.' a '.$ultimo);
    }

    /**
     * URL do PDF ou do XML da nota.
     *
     * O Portal nao tem rota de download — as 19 rotas do grupo sao fechadas —,
     * entao o unico link possivel e a URL publica do disco onde o documento foi
     * guardado. Em disco privado nao ha URL, e a acao aparece indisponivel: e
     * melhor que um botao que leva a um 404 do storage.
     *
     * @return array{url: string|null, disponivel: bool}
     */
    private function arquivo(?string $caminho): array
    {
        if ($caminho === null || trim($caminho) === '') {
            return ['url' => null, 'disponivel' => false];
        }

        $disco = (string) config('velaro-financeiro.notas.disco', 'public');
        $configuracao = config('filesystems.disks.'.$disco);

        // So disco com visibilidade publica gera URL direta.
        if (! is_array($configuracao) || ($configuracao['visibility'] ?? null) !== 'public') {
            return ['url' => null, 'disponivel' => false];
        }

        return ['url' => Storage::disk($disco)->url($caminho), 'disponivel' => true];
    }
}
