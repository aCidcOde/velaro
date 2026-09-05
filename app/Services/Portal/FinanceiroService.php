<?php

/*
[Modulo: app/Services/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Monta a tela 2.4: lotes, pedidos com o custo Velaro, notas do periodo e o drawer de pagamento do lote aberto.
*/

namespace App\Services\Portal;

use App\Http\Requests\Portal\FinanceiroFiltroRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\OrderBatch;
use App\Models\Payment;
use App\Models\Reseller;
use App\Support\ResellerScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * O financeiro do Portal e sempre a mesma pergunta: **quanto este lojista deve a
 * Velaro**. Nao existe saldo do consumidor, carteira de recebiveis B2C nem saque
 * (regra 3 da tela 2.4) — o dinheiro do cliente final e recebido no caixa da
 * propria loja e nunca passa por aqui.
 *
 * Toda consulta desta classe nasce de {@see ResellerScope}: `orders()` e
 * `batches()` sao relacoes do revendedor autenticado, entao o `WHERE reseller_id`
 * e estrutural. `invoices` e `payments` nao tem `reseller_id` proprio — eles
 * pendem do lote (decisao 1.3) — e por isso sao filtrados por subconsulta sobre
 * `batches()`, que ja e escopada. Nao ha `Invoice::query()` solto lendo a base
 * inteira em nenhum ponto do arquivo.
 */
class FinanceiroService
{
    /** Seis linhas por pagina, como a paginacao do prototipo declara. */
    private const PEDIDOS_POR_PAGINA = 6;

    /** Quantas notas o cartao "Notas fiscais emitidas" da 2.4 antecipa. */
    private const NOTAS_NO_RESUMO = 3;

    public function __construct(private readonly FinanceiroApresentacao $ui) {}

    /**
     * Dados da tela 2.4 (`/portal/financeiro`).
     *
     * @return array<string, mixed>
     */
    public function montarIndice(ResellerScope $escopo, string $aba, int $pagina = 1): array
    {
        $loteAtual = $this->loteAtual($escopo);
        $lotes = $aba === FinanceiroFiltroRequest::ABA_LOTES ? $this->lotes($escopo, $pagina) : null;
        $pedidos = $aba === FinanceiroFiltroRequest::ABA_LOTES
            ? null
            : $this->pedidos($escopo, $aba === FinanceiroFiltroRequest::ABA_TODOS ? null : $loteAtual, $pagina);

        return [
            'aba' => $aba,
            'abas' => $this->abas($aba),
            'loteAtual' => $loteAtual,
            'resumoDoLote' => $loteAtual === null ? null : $this->resumoDoLote($loteAtual),
            'meiosDePagamento' => $loteAtual === null ? [] : $this->meios($loteAtual, $this->meioDaCobranca($loteAtual)),
            'kpis' => $this->kpis($escopo, $loteAtual),
            'pedidos' => $pedidos,
            'linhasDePedido' => $pedidos === null ? [] : $this->linhasDePedido($pedidos),
            'lotes' => $lotes,
            'linhasDeLote' => $lotes === null ? [] : $this->linhasDeLote($lotes),
            'rotuloDaLista' => $this->rotuloDaLista($aba, $loteAtual),
            'notas' => $this->notasDoResumo($escopo),
        ];
    }

    /**
     * O lote que esta em cobranca: o mais antigo ainda em aberto. E dele que sai o
     * alerta de vencimento, o KPI "Pedidos no lote atual" e o drawer de pagamento.
     *
     * Quando o lojista esta em dia, nao ha lote atual — e a tela diz isso em vez
     * de escolher um lote quitado qualquer para preencher o espaco.
     */
    public function loteAtual(ResellerScope $escopo): ?OrderBatch
    {
        return $this->lotesEmAberto($escopo->batches())
            ->withCount('orders')
            ->orderBy('due_date')
            ->orderBy('id')
            ->first();
    }

    /**
     * Cabecalho do lote reaproveitado pelo drawer da 2.4 e pela tela de pagamento.
     *
     * @return array<string, mixed>
     */
    public function resumoDoLote(OrderBatch $lote): array
    {
        $quantidade = $this->pedidosNoLote($lote);
        $limite = $this->ui->dataLimite($lote);

        return [
            'lote' => $lote,
            'rotulo' => $this->ui->rotuloDoLote($lote),
            'periodo' => $this->ui->periodoDoLote($lote),
            'corte' => $this->ui->data($lote->cut_date),
            'vencimento' => $this->ui->data($limite),
            'hora_limite' => $this->ui->rotuloDaHoraLimite(),
            'prazo' => $this->ui->prazoCompleto($lote),
            'pedidos' => $quantidade,
            'pedidos_rotulo' => $this->ui->contagemDePedidos($quantidade),
            'total' => (float) $lote->total_amount,
            'total_formatado' => $this->ui->moeda($lote->total_amount),
            'quitado' => $this->ui->loteQuitado($lote),
            'vencido' => $this->ui->loteVencido($lote),
            'status' => $this->ui->statusDoLote($lote),
            'totais' => $this->totais($lote),
            'url' => route('portal.financeiro.pagamento', $lote),
        ];
    }

    /**
     * Conta do lote — o "Resumo do pagamento" do drawer da 2.4 e o bloco ① da tela
     * de pagamento leem daqui, para os dois nunca mostrarem numeros diferentes.
     *
     * O bruto e o que os pedidos custam antes do desconto; o total e o do proprio
     * lote. A diferenca entre os dois nao e escondida: sobra positiva vira
     * "acrescimos" e sobra negativa entra em "descontos", entao a conta fecha na
     * tela mesmo quando o faturamento ajustou o lote a mao.
     *
     * @return array<string, string>
     */
    public function totais(OrderBatch $lote): array
    {
        $bruto = (float) $lote->orders()->sum(DB::raw('subtotal_amount + engraving_amount + shipping_amount'));
        $descontos = (float) $lote->orders()->sum('discount_amount');
        $total = (float) $lote->total_amount;

        $diferenca = round($total - ($bruto - $descontos), 2);

        return [
            'subtotal' => $this->ui->moeda($bruto),
            'descontos' => $this->ui->moeda($descontos + ($diferenca < 0 ? abs($diferenca) : 0.0)),
            'acrescimos' => $this->ui->moeda(max(0.0, $diferenca)),
            'total' => $this->ui->moeda($total),
        ];
    }

    /**
     * Os tres meios B2B habilitados (regra 2 da tela 2.4), como radios.
     *
     * Cada um e um link para a tela de pagamento com `?metodo=`: escolher o meio e
     * trocar o que a pagina mostra, nao gravar uma decisao — o meio efetivo e o do
     * pagamento que o lojista de fato fizer.
     *
     * @return list<array{chave: string, rotulo: string, nota: string, icone: string, ativo: bool, url: string}>
     */
    public function meios(OrderBatch $lote, string $ativo): array
    {
        $definicoes = [
            Payment::METHOD_PIX => ['nota' => 'Aprovação imediata', 'icone' => 'coin'],
            Payment::METHOD_BOLETO => ['nota' => 'Compensação em até 1 dia útil', 'icone' => 'doc'],
            Payment::METHOD_BANK_TRANSFER => ['nota' => 'Compensação em até 1 dia útil', 'icone' => 'card'],
        ];

        $meios = [];

        foreach ($definicoes as $chave => $definicao) {
            $meios[] = [
                'chave' => $chave,
                'rotulo' => $this->ui->rotuloDoMeio($chave),
                'nota' => $definicao['nota'],
                'icone' => $definicao['icone'],
                'ativo' => $chave === $ativo,
                'url' => route('portal.financeiro.pagamento', [$lote, 'metodo' => $chave]),
            ];
        }

        return $meios;
    }

    /**
     * O meio da cobranca que ja existe para o lote — o mesmo que a tela de
     * pagamento abre.
     *
     * Sem isso o drawer marcaria Pix sempre, e clicar em "Realizar pagamento"
     * levaria a uma tela aberta no boleto: dois lugares dizendo coisas
     * diferentes sobre a mesma cobranca.
     */
    private function meioDaCobranca(OrderBatch $lote): string
    {
        $cobranca = $lote->payments()->latest('id')->first();

        return $cobranca instanceof Payment ? (string) $cobranca->method : Payment::METHOD_PIX;
    }

    /**
     * Os cinco KPIs da secao 5 da tela 2.4.
     *
     * "Este mes" e o mes do relogio, nao o mes do ultimo lancamento: o cartao diz
     * "Este mes" e precisa continuar verdadeiro na virada — um mes sem nota emitida
     * mostra zero, que e a informacao correta.
     *
     * @return array<string, mixed>
     */
    public function kpis(ResellerScope $escopo, ?OrderBatch $loteAtual): array
    {
        $inicioDoMes = Carbon::now()->startOfMonth();
        $fimDoMes = Carbon::now()->endOfMonth();

        $emAberto = (float) $this->lotesEmAberto($escopo->batches())->sum('total_amount');

        $pedidosDoLote = $loteAtual === null
            ? ['quantidade' => 0, 'total' => 0.0]
            : [
                'quantidade' => $this->pedidosNoLote($loteAtual),
                'total' => (float) $loteAtual->total_amount,
            ];

        $notasDoMes = $this->notas($escopo)
            ->whereBetween('issued_at', [$inicioDoMes, $fimDoMes])
            ->count();

        $pagamentosDoMes = (float) $this->pagamentos($escopo)
            ->whereBetween('paid_at', [$inicioDoMes, $fimDoMes])
            ->sum('amount');

        return [
            'em_aberto' => $this->ui->moeda($emAberto),
            'em_aberto_url' => $loteAtual === null ? null : route('portal.financeiro.pagamento', $loteAtual),
            'pedidos_do_lote' => $this->ui->contagemDePedidos($pedidosDoLote['quantidade']),
            'pedidos_do_lote_total' => $this->ui->moeda($pedidosDoLote['total']),
            'proximo_vencimento' => $loteAtual === null ? '—' : $this->ui->data($this->ui->dataLimite($loteAtual)),
            'proximo_vencimento_hora' => $loteAtual === null ? 'Nenhum lote em aberto' : $this->ui->rotuloDaHoraLimite(),
            'notas_emitidas' => (string) $notasDoMes,
            'pagamentos_confirmados' => $this->ui->moeda($pagamentosDoMes),
        ];
    }

    /**
     * Pedidos da aba corrente. `$lote` nulo e a aba "Todos os pedidos"; com lote,
     * so os que compoem o lote em cobranca.
     *
     * @return LengthAwarePaginator<int, Order>
     */
    public function pedidos(ResellerScope $escopo, ?OrderBatch $lote, int $pagina = 1): LengthAwarePaginator
    {
        $consulta = $escopo->orders()
            ->with(['customer', 'batch'])
            ->latest('created_at')
            ->orderByDesc('id');

        if ($lote !== null) {
            $consulta->where('batch_id', $lote->getKey());
        }

        return $consulta->paginate(self::PEDIDOS_POR_PAGINA, ['*'], 'page', $pagina)->withQueryString();
    }

    /**
     * Linhas da tabela de pedidos, ja formatadas.
     *
     * A coluna NF-e sai de `invoice_items` (decisao 1.3): a nota e do lote, e o
     * rateio e quem diz que **aquele** pedido entrou naquele documento. Um pedido
     * que caiu no lote depois da emissao nao ganha link de nota — e o rateio que
     * responde, nao a existencia de uma nota qualquer no lote.
     *
     * @param  LengthAwarePaginator<int, Order>  $pedidos
     * @return list<array<string, mixed>>
     */
    public function linhasDePedido(LengthAwarePaginator $pedidos): array
    {
        /** @var list<Order> $itens */
        $itens = $pedidos->items();
        $notas = $this->notasPorPedido($itens);

        return array_map(function (Order $pedido) use ($notas): array {
            $lote = $pedido->batch;
            $nota = $notas[(int) $pedido->getKey()] ?? null;
            $criado = $pedido->created_at;
            // `orders.customer_id` e anulavel no scaffold: pedido sem cliente na
            // carteira existe, e a coluna nao pode quebrar por causa disso.
            $cliente = $pedido->customer instanceof Customer ? $pedido->customer->name : null;

            return [
                'numero' => (string) $pedido->public_number,
                'referencia' => $pedido->reference,
                'cliente' => $cliente ?? 'Cliente removido',
                'iniciais' => $this->ui->iniciais($cliente),
                'data' => $this->ui->data($criado),
                'hora' => $this->ui->hora($criado),
                'valor' => $this->ui->moeda($pedido->total_amount),
                'lote' => $lote === null ? '—' : $this->ui->rotuloDoLote($lote),
                'prazo' => $lote === null ? '—' : $this->ui->data($this->ui->dataLimite($lote)),
                'prazo_hora' => $lote === null ? '' : $this->ui->rotuloDaHoraLimite(),
                'status' => $this->ui->statusDoPagamento($pedido->payment_status),
                'nota' => $nota,
                'url' => route('portal.pedidos.show', $pedido),
            ];
        }, $itens);
    }

    /**
     * Lotes do lojista, do mais recente para tras — a aba "Lotes anteriores".
     *
     * @return LengthAwarePaginator<int, OrderBatch>
     */
    public function lotes(ResellerScope $escopo, int $pagina = 1): LengthAwarePaginator
    {
        return $escopo->batches()
            ->withCount('orders')
            ->with('invoices')
            ->orderByDesc('cut_date')
            ->orderByDesc('id')
            ->paginate(self::PEDIDOS_POR_PAGINA, ['*'], 'page', $pagina)
            ->withQueryString();
    }

    /**
     * @param  LengthAwarePaginator<int, OrderBatch>  $lotes
     * @return list<array<string, mixed>>
     */
    public function linhasDeLote(LengthAwarePaginator $lotes): array
    {
        /** @var list<OrderBatch> $itens */
        $itens = $lotes->items();

        return array_map(function (OrderBatch $lote): array {
            /** @var Invoice|null $nota */
            $nota = $lote->invoices->sortByDesc('issued_at')->first();

            return [
                'rotulo' => $this->ui->rotuloDoLote($lote),
                'codigo' => (string) $lote->code,
                'periodo' => $this->ui->periodoDoLote($lote),
                'vencimento' => $this->ui->data($this->ui->dataLimite($lote)),
                'hora_limite' => $this->ui->rotuloDaHoraLimite(),
                'pedidos' => $this->ui->contagemDePedidos($this->pedidosNoLote($lote)),
                'total' => $this->ui->moeda($lote->total_amount),
                'status' => $this->ui->statusDoLote($lote),
                'nota' => $nota === null ? null : $this->referenciaDaNota($nota),
                'url' => route('portal.financeiro.pagamento', $lote),
            ];
        }, $itens);
    }

    /**
     * As tres ultimas notas, para o cartao "Notas fiscais emitidas" da 2.4.
     *
     * @return list<array<string, mixed>>
     */
    public function notasDoResumo(ResellerScope $escopo): array
    {
        $notas = $this->notas($escopo)
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->limit(self::NOTAS_NO_RESUMO)
            ->get();

        return $notas->map(fn (Invoice $nota): array => [
            'numero' => 'NF-e '.$nota->number,
            'emissao' => $this->ui->data($nota->issued_at),
            'competencia' => $this->ui->competencia($nota->issued_at),
            'valor' => $this->ui->moeda($nota->amount),
            'status' => $this->ui->statusDaNota($nota->status),
            'url' => route('portal.financeiro.notas', ['q' => $nota->number, 'periodo' => '0']),
        ])->all();
    }

    /**
     * Notas do lojista. `invoices` nao tem `reseller_id`: o documento pende do
     * lote, e o lote e que tem dono. A subconsulta sobre `batches()` (ja escopada
     * por {@see ResellerScope}) e o que impede a nota de outro lojista de entrar.
     *
     * @return Builder<Invoice>
     */
    public function notas(ResellerScope $escopo): Builder
    {
        return Invoice::query()->whereIn('batch_id', $this->idsDosLotes($escopo));
    }

    /**
     * Cobrancas do lojista, pela mesma subconsulta escopada das notas.
     *
     * @return Builder<Payment>
     */
    public function pagamentos(ResellerScope $escopo): Builder
    {
        return Payment::query()->whereIn('batch_id', $this->idsDosLotes($escopo));
    }

    /**
     * Subconsulta com os lotes do revendedor — a origem do `WHERE reseller_id`
     * para as duas tabelas que nao carregam a coluna. `ownedBy()` e o scope do
     * trait `BelongsToReseller`, e devolve conjunto vazio quando o dono e nulo:
     * um escopo perdido no caminho nunca abre a base inteira.
     *
     * @return Builder<OrderBatch>
     */
    private function idsDosLotes(ResellerScope $escopo): Builder
    {
        return OrderBatch::query()->ownedBy($escopo->reseller)->select('order_batches.id');
    }

    /**
     * Quantos pedidos o lote tem. Usa o `orders_count` do `withCount()` quando ele
     * veio junto e so consulta o banco quando nao veio — assim a mesma funcao
     * serve a lista (uma consulta para todos) e ao lote isolado.
     */
    private function pedidosNoLote(OrderBatch $lote): int
    {
        $contagem = $lote->getAttribute('orders_count');

        return is_numeric($contagem) ? (int) $contagem : $lote->orders()->count();
    }

    /**
     * Como a linha do pedido referencia a nota do lote dele.
     *
     * Nao ha rota de download no Portal — as 19 rotas do grupo sao fechadas —,
     * entao "Baixar NF" leva a tela de notas ja filtrada por aquele numero, que e
     * onde moram o PDF e o XML. E um salto a mais, mas honesto: nenhum link
     * promete um arquivo que o ambiente nao serve.
     *
     * @return array<string, string>
     */
    private function referenciaDaNota(Invoice $nota): array
    {
        return [
            'numero' => 'NF-e '.$nota->number,
            // `periodo=0` (todo o historico) junto com a busca: a tela de notas
            // abre nos ultimos 90 dias, e sem isso o link de uma nota mais antiga
            // cairia numa lista vazia.
            'url' => route('portal.financeiro.notas', ['q' => $nota->number, 'periodo' => '0']),
        ];
    }

    /**
     * Nota de cada pedido da pagina, em uma consulta so.
     *
     * @param  list<Order>  $pedidos
     * @return array<int, array<string, string>>
     */
    private function notasPorPedido(array $pedidos): array
    {
        $ids = array_values(array_filter(array_map(
            static fn (Order $pedido): mixed => $pedido->getKey(),
            $pedidos,
        )));

        if ($ids === []) {
            return [];
        }

        /** @var EloquentCollection<int, InvoiceItem> $rateios */
        $rateios = InvoiceItem::query()
            ->whereIn('order_id', $ids)
            ->with('invoice')
            ->get();

        $porPedido = [];

        foreach ($rateios as $rateio) {
            $nota = $rateio->invoice;

            if ($nota instanceof Invoice) {
                $porPedido[(int) $rateio->order_id] = $this->referenciaDaNota($nota);
            }
        }

        return $porPedido;
    }

    /**
     * Lotes que o lojista ainda deve.
     *
     * `paid_at` e a autoridade da baixa; o `status` textual entra junto para que a
     * consulta e o chip de {@see FinanceiroApresentacao::loteQuitado()} nunca
     * discordem — um lote marcado como pago sem `paid_at` sairia da lista de
     * cobranca na tela e continuaria somando no KPI.
     *
     * @param  HasMany<OrderBatch, Reseller>  $lotes
     * @return HasMany<OrderBatch, Reseller>
     */
    private function lotesEmAberto(HasMany $lotes): HasMany
    {
        return $lotes
            ->whereNull('paid_at')
            ->where('status', '!=', Order::PAYMENT_STATUS_PAID);
    }

    /**
     * @return list<array{chave: string, rotulo: string, ativa: bool, url: string}>
     */
    private function abas(string $atual): array
    {
        $rotulos = [
            FinanceiroFiltroRequest::ABA_LOTE_ATUAL => 'Pedidos do lote atual',
            FinanceiroFiltroRequest::ABA_TODOS => 'Todos os pedidos',
            FinanceiroFiltroRequest::ABA_LOTES => 'Lotes anteriores',
        ];

        $abas = [];

        foreach ($rotulos as $chave => $rotulo) {
            $abas[] = [
                'chave' => $chave,
                'rotulo' => $rotulo,
                'ativa' => $chave === $atual,
                'url' => route('portal.financeiro.index', $chave === FinanceiroFiltroRequest::ABA_LOTE_ATUAL ? [] : ['aba' => $chave]),
            ];
        }

        return $abas;
    }

    private function rotuloDaLista(string $aba, ?OrderBatch $loteAtual): string
    {
        if ($aba === FinanceiroFiltroRequest::ABA_LOTES) {
            return 'lotes';
        }

        if ($aba === FinanceiroFiltroRequest::ABA_TODOS || $loteAtual === null) {
            return 'pedidos';
        }

        return 'pedidos do lote '.$this->ui->rotuloDoLote($loteAtual);
    }
}
