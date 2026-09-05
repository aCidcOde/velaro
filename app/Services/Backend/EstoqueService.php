<?php

/*
[Modulo: app/Services/Backend]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Saldo por SKU, aro e local no Painel Master: indicadores, tabela, gaveta, extrato e as cinco movimentacoes.
*/

namespace App\Services\Backend;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductionRequest;
use App\Models\ProductVariant;
use App\Models\StockItem;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\AdminAuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Tela 3.4 — o estoque físico da Velaro.
 *
 * Três regras moldam este arquivo, e as três vêm do doc da tela:
 *
 * 1. **O saldo é por SKU/aro e por local.** `stock_items` tem
 *    `UNIQUE(product_variant_id, stock_location_id)`: uma linha de saldo por aro
 *    em cada cofre. A tabela da tela soma os aros de um produto porque é assim
 *    que o protótipo a mostra, mas a escrita é sempre na linha do aro.
 * 2. **Nenhum saldo muda sem movimento correspondente** (regra 3). Não existe
 *    `update` de `stock_items` fora de {@see registrarMovimentacao()}, e todo
 *    movimento grava `before`, `after`, o motivo e o ator.
 * 3. **Ajuste manual é ação sensível (§7).** Motivo é obrigatório em toda
 *    movimentação, e o ajuste ainda gera uma linha própria em `audit_logs`, com
 *    o saldo anterior e o posterior.
 *
 * ## O que `before` e `after` guardam
 *
 * O **disponível** (`stock_items.available`) — o número que o Portal do Lojista
 * e a vitrine leem, e o único que as cinco movimentações mexem. É também a
 * convenção que a base já tem: em `StockMovementFactory`, toda linha semeada
 * satisfaz `after = before ± qty`, inclusive a reserva, que não toca o saldo
 * físico e ainda assim consome disponibilidade.
 *
 * ## Sinal de `qty`
 *
 * Magnitude positiva em entrada, saída, reserva e produção — a direção está no
 * `type`. No **ajuste** o número é o delta com sinal, porque o campo da tela é o
 * novo saldo e o que interessa auditar é o quanto ele andou.
 */
class EstoqueService
{
    /**
     * As faixas de aro do bloco "Estoque por tamanho" da gaveta. O aro é uma
     * coluna de texto (`product_variants.ring_size`) e a faixa é agrupamento de
     * apresentação — nada aqui vira coluna nem enum.
     *
     * @var list<array{rotulo: string, de: int, ate: int}>
     */
    public const FAIXAS_DE_ARO = [
        ['rotulo' => '10 - 14', 'de' => 10, 'ate' => 14],
        ['rotulo' => '15 - 19', 'de' => 15, 'ate' => 19],
        ['rotulo' => '20 - 24', 'de' => 20, 'ate' => 24],
        ['rotulo' => '25 - 29', 'de' => 25, 'ate' => 29],
        ['rotulo' => '30 - 33', 'de' => 30, 'ate' => 33],
    ];

    /** Situação do saldo — as chaves de `lang/pt_BR/stock.php`, nunca texto solto na view. */
    public const SITUACAO_EM_ESTOQUE = 'in_stock';

    public const SITUACAO_BAIXO_ESTOQUE = 'low_stock';

    public const SITUACAO_RESERVADO = 'reserved';

    public const SITUACAO_SEM_ESTOQUE = 'out_of_stock';

    /** Necessidade de reposição — idem. */
    public const REPOSICAO_SUGERIDA = 'suggested';

    public const REPOSICAO_PRIORITARIA = 'priority';

    /**
     * A cor de cada situação e de cada tipo de movimentação no design system.
     * Fica aqui, e não na view, para a tabela, a gaveta e o extrato pintarem o
     * mesmo estado da mesma cor.
     *
     * @var array<string, string>
     */
    public const CHIP_SITUACAO = [
        self::SITUACAO_EM_ESTOQUE => 'chip--ok',
        self::SITUACAO_BAIXO_ESTOQUE => 'chip--warn',
        self::SITUACAO_RESERVADO => 'chip--info',
        self::SITUACAO_SEM_ESTOQUE => 'chip--danger',
    ];

    /** @var array<string, string> */
    public const CHIP_REPOSICAO = [
        self::REPOSICAO_SUGERIDA => 'chip--neutral',
        self::REPOSICAO_PRIORITARIA => 'chip--danger',
    ];

    /** @var array<string, string> */
    public const CHIP_MOVIMENTACAO = [
        StockMovement::TYPE_INBOUND => 'chip--ok',
        StockMovement::TYPE_OUTBOUND => 'chip--danger',
        StockMovement::TYPE_ADJUSTMENT => 'chip--warn',
        StockMovement::TYPE_RESERVATION => 'chip--info',
        StockMovement::TYPE_PRODUCTION => 'chip--violet',
    ];

    /** Linhas por página da tabela (protótipo: 1 a 8 de 58) e do extrato (1 a 10 de 148). */
    private const POR_PAGINA = 10;

    /** Quantas movimentações a gaveta mostra antes do "Ver todas →". */
    private const ULTIMAS_MOVIMENTACOES = 4;

    public function __construct(private readonly AdminAuditLogger $auditoria) {}

    // ─────────────────────────────── INDICADORES ───────────────────────────────

    /**
     * Os cinco cartões do topo.
     *
     * A variação mês a mês só aparece onde o banco consegue sustentá-la: o
     * ledger de `stock_movements` reconstrói o saldo do mês anterior, mas não há
     * fotografia diária de `stock_items`, então "baixo estoque" e "reservados"
     * não têm com o que se comparar. Cartão sem base devolve `variacao => null`
     * e a tela não desenha a linha — melhor do que um percentual inventado.
     *
     * @return list<array{rotulo: string, valor: int, icone: string, tom: string, variacao: float|null, situacao: string|null}>
     */
    public function kpis(): array
    {
        $emEstoque = (int) StockItem::query()->sum('on_hand');

        return [
            [
                'rotulo' => 'Itens em estoque',
                'valor' => $emEstoque,
                'icone' => 'box',
                'tom' => 'gold',
                'variacao' => $this->variacao($emEstoque, $emEstoque - $this->saldoFisicoMovimentadoNoMes()),
                'situacao' => null,
            ],
            [
                'rotulo' => 'Baixo estoque',
                'valor' => StockItem::query()->whereColumn('on_hand', '<=', 'minimum')->count(),
                'icone' => 'clock',
                'tom' => 'warn',
                'variacao' => null,
                'situacao' => self::SITUACAO_BAIXO_ESTOQUE,
            ],
            [
                'rotulo' => 'Reservados',
                'valor' => (int) StockItem::query()->sum('reserved'),
                'icone' => 'lock',
                'tom' => 'info',
                'variacao' => null,
                'situacao' => self::SITUACAO_RESERVADO,
            ],
            [
                'rotulo' => 'Sob encomenda',
                'valor' => (int) ProductionRequest::query()
                    ->whereIn('status', [ProductionRequest::STATUS_PENDING, ProductionRequest::STATUS_IN_PRODUCTION])
                    ->sum(DB::raw('qty_requested - qty_delivered')),
                'icone' => 'factory',
                'tom' => 'violet',
                'variacao' => null,
                'situacao' => null,
            ],
            [
                'rotulo' => 'Reposições pendentes',
                'valor' => ProductionRequest::query()->where('status', ProductionRequest::STATUS_PENDING)->count(),
                'icone' => 'refresh',
                'tom' => 'danger',
                'variacao' => null,
                'situacao' => null,
            ],
        ];
    }

    /**
     * O quanto o **saldo físico** (`stock_items.on_hand`) andou no mês corrente,
     * reconstruído pelo ledger.
     *
     * Só entram os três tipos que mexem no saldo físico. A **reserva** fica de
     * fora de propósito: ela consome disponibilidade sem tirar peça do cofre, e
     * `before`/`after` guardam o disponível — somar o delta dela ao `on_hand`
     * faria o cartão anunciar uma queda de estoque que nunca aconteceu.
     * Produção também não entra: não gera movimento (ver
     * {@see solicitarProducao()}).
     *
     * O sinal segue a convenção documentada de `qty`: magnitude em entrada e
     * saída, delta já com sinal no ajuste.
     */
    private function saldoFisicoMovimentadoNoMes(): int
    {
        $desde = Carbon::now()->startOfMonth();

        $noMes = fn (string $tipo): int => (int) StockMovement::query()
            ->where('type', $tipo)
            ->where('created_at', '>=', $desde)
            ->sum('qty');

        return $noMes(StockMovement::TYPE_INBOUND)
            - $noMes(StockMovement::TYPE_OUTBOUND)
            + $noMes(StockMovement::TYPE_ADJUSTMENT);
    }

    private function variacao(int $atual, int $anterior): ?float
    {
        if ($anterior === 0) {
            return null;
        }

        return round((($atual - $anterior) / abs($anterior)) * 100, 1);
    }

    // ─────────────────────────────── TABELA ───────────────────────────────

    /**
     * A tabela da tela: uma linha por produto, com os aros somados.
     *
     * O saldo por aro vira soma do produto num `leftJoinSub` — e não em
     * `addSelect` — porque o filtro de situação compara duas dessas somas entre
     * si, e comparação de coluna agregada precisa da soma disponível como
     * coluna. `left` de propósito: produto do catálogo que ainda não recebeu
     * nenhuma peça aparece com saldo zero, em vez de sumir da tela de estoque.
     *
     * @param  array{busca?: string|null, categoria?: int|string|null, situacao?: string|null, local?: int|string|null}  $filtros
     * @return LengthAwarePaginator<int, Product>
     */
    public function listar(array $filtros = []): LengthAwarePaginator
    {
        $busca = trim((string) ($filtros['busca'] ?? ''));
        $categoria = $filtros['categoria'] ?? null;
        $situacao = trim((string) ($filtros['situacao'] ?? ''));
        $local = $filtros['local'] ?? null;

        $saldos = StockItem::query()
            ->join('product_variants', 'product_variants.id', '=', 'stock_items.product_variant_id')
            ->when(
                $local !== null && $local !== '',
                fn (Builder $q): Builder => $q->where('stock_items.stock_location_id', $local),
            )
            ->groupBy('product_variants.product_id')
            ->selectRaw(implode(', ', [
                'product_variants.product_id as product_id',
                'SUM(stock_items.on_hand) as on_hand',
                'SUM(stock_items.reserved) as reserved',
                'SUM(stock_items.available) as available',
                'SUM(stock_items.minimum) as minimum',
                'SUM(stock_items.restock_point) as restock_point',
            ]));

        return Product::query()
            ->leftJoinSub($saldos, 'saldos', 'saldos.product_id', '=', 'products.id')
            ->with(['collection', 'material', 'finish', 'variants'])
            ->selectRaw(implode(', ', [
                'products.*',
                'COALESCE(saldos.on_hand, 0) as stock_on_hand',
                'COALESCE(saldos.reserved, 0) as stock_reserved',
                'COALESCE(saldos.available, 0) as stock_available',
                'COALESCE(saldos.minimum, 0) as stock_minimum',
                'COALESCE(saldos.restock_point, 0) as stock_restock_point',
            ]))
            ->when($categoria !== null && $categoria !== '', fn (Builder $q): Builder => $q->where('products.category_id', $categoria))
            ->when($busca !== '', function (Builder $q) use ($busca): Builder {
                $termo = '%'.$busca.'%';

                return $q->where(function (Builder $interna) use ($termo): void {
                    $interna->where('products.name', 'like', $termo)
                        ->orWhere('products.sku', 'like', $termo)
                        ->orWhereHas('collection', fn (Builder $colecao): Builder => $colecao->where('product_collections.name', 'like', $termo))
                        ->orWhereHas('variants', fn (Builder $variante): Builder => $variante->where('product_variants.sku', 'like', $termo));
                });
            })
            ->when($situacao !== '', fn (Builder $q): Builder => $this->filtrarSituacao($q, $situacao))
            ->orderBy('products.name')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();
    }

    /**
     * O recorte por situação, escrito uma vez só — a mesma classificação que
     * {@see situacao()} devolve para o chip da linha.
     *
     * @param  Builder<Product>  $consulta
     * @return Builder<Product>
     */
    private function filtrarSituacao(Builder $consulta, string $situacao): Builder
    {
        return match ($situacao) {
            self::SITUACAO_EM_ESTOQUE => $consulta->whereRaw('COALESCE(saldos.on_hand, 0) > COALESCE(saldos.minimum, 0)'),
            self::SITUACAO_BAIXO_ESTOQUE => $consulta
                ->whereRaw('COALESCE(saldos.on_hand, 0) > 0')
                ->whereRaw('COALESCE(saldos.on_hand, 0) <= COALESCE(saldos.minimum, 0)'),
            self::SITUACAO_RESERVADO => $consulta
                ->whereRaw('COALESCE(saldos.on_hand, 0) <= 0')
                ->whereRaw('COALESCE(saldos.reserved, 0) > 0'),
            self::SITUACAO_SEM_ESTOQUE => $consulta
                ->whereRaw('COALESCE(saldos.on_hand, 0) <= 0')
                ->whereRaw('COALESCE(saldos.reserved, 0) <= 0'),
            default => $consulta,
        };
    }

    /**
     * A situação do saldo que vira chip na coluna STATUS.
     *
     * Peça zerada com reserva em aberto é "Reservado", não "Sem estoque": o que
     * existe dela já tem dono, e é essa a leitura do protótipo.
     */
    public function situacao(int $onHand, int $reserved, int $minimum): string
    {
        if ($onHand <= 0) {
            return $reserved > 0 ? self::SITUACAO_RESERVADO : self::SITUACAO_SEM_ESTOQUE;
        }

        return $onHand <= $minimum ? self::SITUACAO_BAIXO_ESTOQUE : self::SITUACAO_EM_ESTOQUE;
    }

    /** Reposição prioritária é a do saldo que já bateu no mínimo. */
    public function reposicao(int $onHand, int $minimum): string
    {
        return $onHand <= $minimum ? self::REPOSICAO_PRIORITARIA : self::REPOSICAO_SUGERIDA;
    }

    /**
     * A faixa de aros que a coluna TAMANHOS mostra ("10 - 33").
     *
     * @param  EloquentCollection<int, ProductVariant>  $variantes
     */
    public function faixaDeAros(EloquentCollection $variantes): ?string
    {
        $aros = $variantes
            ->map(static fn (ProductVariant $variante): int => (int) $variante->getAttribute('ring_size'))
            ->filter(static fn (int $aro): bool => $aro > 0)
            ->sort()
            ->values();

        if ($aros->isEmpty()) {
            return null;
        }

        return $aros->first() === $aros->last()
            ? (string) $aros->first()
            : $aros->first().' - '.$aros->last();
    }

    // ─────────────────────────────── GAVETA ───────────────────────────────

    /**
     * A gaveta lateral do protótipo, servida pelo servidor.
     *
     * O produto vem por `?produto=` na query string — sem `id` interno em rota,
     * e sem JS: a gaveta é uma navegação, não um estado de cliente.
     *
     * @return array<string, mixed>|null
     */
    public function gaveta(?Product $produto, int|string|null $local = null): ?array
    {
        if (! $produto instanceof Product) {
            return null;
        }

        $produto->loadMissing(['collection', 'material', 'finish', 'images', 'variants.stockItems.stockLocation']);

        $itens = $this->saldosDoLocal(
            $produto->variants->flatMap(static fn (ProductVariant $variante): iterable => $variante->stockItems)->all(),
            $local,
        );

        $onHand = (int) $itens->sum('on_hand');
        $reserved = (int) $itens->sum('reserved');
        $minimum = (int) $itens->sum('minimum');

        return [
            'produto' => $produto,
            'capa' => $this->capa($produto),
            'onHand' => $onHand,
            'reserved' => $reserved,
            'available' => (int) $itens->sum('available'),
            'minimum' => $minimum,
            'restockPoint' => (int) $itens->sum('restock_point'),
            'situacao' => $this->situacao($onHand, $reserved, $minimum),
            'local' => $itens->first()?->stockLocation,
            'porFaixa' => $this->porFaixaDeAro($produto),
            'movimentacoes' => $this->ultimasMovimentacoes($produto),
            'reservas' => $this->reservasEmAberto($produto),
            'variantes' => $produto->variants->sortBy(static fn (ProductVariant $v): int => (int) $v->getAttribute('ring_size'))->values(),
        ];
    }

    /**
     * A foto do item — a "foto" que a gaveta e as fichas laterais mostram.
     *
     * Mesmo desenho do catálogo do Portal: a primária primeiro e, quando o
     * produto ainda não tem nenhuma cadastrada, o placeholder vetorial
     * compartilhado (`<x-velaro.ring>`) entra na view. Devolver `null` é dizer
     * "não há fotografia", não "desenhe um ícone".
     *
     * @return array{src: string, alt: string}|null
     */
    public function capa(?Product $produto): ?array
    {
        if (! $produto instanceof Product) {
            return null;
        }

        $produto->loadMissing('images');

        /** @var ProductImage|null $imagem */
        $imagem = $produto->images
            ->sortBy([['is_primary', 'desc'], ['position', 'asc'], ['id', 'asc']])
            ->first();

        if (! $imagem instanceof ProductImage) {
            return null;
        }

        $caminho = trim((string) $imagem->getAttribute('path'));

        if ($caminho === '') {
            return null;
        }

        $alt = trim((string) $imagem->getAttribute('alt'));

        return [
            'src' => asset($caminho),
            'alt' => $alt !== '' ? $alt : (string) $produto->getAttribute('name'),
        ];
    }

    /**
     * O bloco "Estoque por tamanho": os aros agrupados nas cinco faixas do
     * protótipo. Faixa sem nenhum aro cadastrado não vira linha em branco.
     *
     * @return list<array{rotulo: string, onHand: int, reserved: int, available: int, minimum: int}>
     */
    public function porFaixaDeAro(Product $produto): array
    {
        $produto->loadMissing('variants.stockItems');

        $linhas = [];

        foreach (self::FAIXAS_DE_ARO as $faixa) {
            $variantes = $produto->variants->filter(function (ProductVariant $variante) use ($faixa): bool {
                $aro = (int) $variante->getAttribute('ring_size');

                return $aro >= $faixa['de'] && $aro <= $faixa['ate'];
            });

            if ($variantes->isEmpty()) {
                continue;
            }

            $itens = $variantes->flatMap(static fn (ProductVariant $variante): iterable => $variante->stockItems);

            $linhas[] = [
                'rotulo' => $faixa['rotulo'],
                'onHand' => (int) $itens->sum('on_hand'),
                'reserved' => (int) $itens->sum('reserved'),
                'available' => (int) $itens->sum('available'),
                'minimum' => (int) $itens->sum('minimum'),
            ];
        }

        return $linhas;
    }

    /**
     * @return EloquentCollection<int, StockMovement>
     */
    private function ultimasMovimentacoes(Product $produto): EloquentCollection
    {
        return $this->movimentacoesDoProduto($produto)
            ->with(['actor', 'order', 'stockItem.productVariant'])
            ->latest('created_at')
            ->latest('id')
            ->limit(self::ULTIMAS_MOVIMENTACOES)
            ->get();
    }

    /**
     * Os pedidos que hoje seguram peça deste produto — o "Reservas em aberto" da
     * gaveta e a aba "Reservas" do extrato.
     *
     * A reserva é um movimento com pedido vinculado; em aberto é a que pertence
     * a pedido que ainda não foi retirado.
     *
     * @return EloquentCollection<int, StockMovement>
     */
    public function reservasEmAberto(Product $produto): EloquentCollection
    {
        return $this->movimentacoesDoProduto($produto)
            ->where('stock_movements.type', StockMovement::TYPE_RESERVATION)
            ->whereHas('order', fn (Builder $pedido): Builder => $pedido
                ->where('orders.operational_status', '!=', Order::OPERATIONAL_STATUS_PICKED_UP))
            ->with(['order.customer', 'order.reseller', 'stockItem.productVariant'])
            ->latest('created_at')
            ->get();
    }

    /**
     * @return Builder<StockMovement>
     */
    private function movimentacoesDoProduto(Product $produto): Builder
    {
        return StockMovement::query()
            ->whereHas('stockItem.productVariant', fn (Builder $variante): Builder => $variante
                ->where('product_variants.product_id', $produto->getKey()));
    }

    // ─────────────────────────────── EXTRATO (52b) ───────────────────────────────

    /**
     * O extrato do item, aberto por um dos seus aros.
     *
     * A rota entrega uma **variante** (`/backend/estoque/{variant}/historico`),
     * mas o protótipo mostra o extrato do **produto**, com o aro como coluna e
     * como filtro — por isso a variante da rota entra como valor inicial do
     * filtro "Aro" em vez de recortar o extrato inteiro.
     *
     * @param  array{busca?: string|null, tipo?: string|null, aro?: int|string|null, periodo?: int|string|null}  $filtros
     * @return LengthAwarePaginator<int, StockMovement>
     */
    public function extrato(Product $produto, array $filtros = []): LengthAwarePaginator
    {
        $busca = trim((string) ($filtros['busca'] ?? ''));
        $tipo = trim((string) ($filtros['tipo'] ?? ''));
        $aro = $filtros['aro'] ?? null;
        $periodo = (int) ($filtros['periodo'] ?? 30);

        return $this->movimentacoesDoProduto($produto)
            ->with(['actor', 'order', 'stockItem.productVariant', 'stockItem.stockLocation'])
            ->when($tipo !== '', fn (Builder $q): Builder => $q->where('stock_movements.type', $tipo))
            ->when(
                $aro !== null && $aro !== '',
                fn (Builder $q): Builder => $q->whereHas(
                    'stockItem.productVariant',
                    fn (Builder $variante): Builder => $variante->where('product_variants.ring_size', (string) $aro),
                ),
            )
            ->when($periodo > 0, fn (Builder $q): Builder => $q->where('stock_movements.created_at', '>=', Carbon::now()->subDays($periodo)))
            ->when($busca !== '', function (Builder $q) use ($busca): Builder {
                $termo = '%'.$busca.'%';

                return $q->where(function (Builder $interna) use ($termo): void {
                    $interna->where('stock_movements.reason', 'like', $termo)
                        ->orWhereHas('order', fn (Builder $pedido): Builder => $pedido->where('orders.public_number', 'like', $termo))
                        ->orWhereHas('actor', fn (Builder $ator): Builder => $ator->where('users.name', 'like', $termo));
                });
            })
            ->latest('stock_movements.created_at')
            ->latest('stock_movements.id')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();
    }

    /**
     * Os quatro cartões do extrato, dentro do período escolhido.
     *
     * @return list<array{rotulo: string, valor: int, icone: string, tom: string}>
     */
    public function kpisDoExtrato(Product $produto, int $periodo = 30): array
    {
        $desde = $periodo > 0 ? Carbon::now()->subDays($periodo) : null;

        $noPeriodo = fn (string $tipo): int => (int) $this->movimentacoesDoProduto($produto)
            ->where('stock_movements.type', $tipo)
            ->when($desde !== null, fn (Builder $q): Builder => $q->where('stock_movements.created_at', '>=', $desde))
            ->sum('stock_movements.qty');

        $ajustes = $this->movimentacoesDoProduto($produto)
            ->where('stock_movements.type', StockMovement::TYPE_ADJUSTMENT)
            ->when($desde !== null, fn (Builder $q): Builder => $q->where('stock_movements.created_at', '>=', $desde))
            ->count();

        return [
            ['rotulo' => 'Entradas no período', 'valor' => $noPeriodo(StockMovement::TYPE_INBOUND), 'icone' => 'arrow-down', 'tom' => 'ok'],
            ['rotulo' => 'Saídas no período', 'valor' => $noPeriodo(StockMovement::TYPE_OUTBOUND), 'icone' => 'arrow-up', 'tom' => 'danger'],
            ['rotulo' => 'Ajustes manuais', 'valor' => $ajustes, 'icone' => 'edit', 'tom' => 'warn'],
            ['rotulo' => 'Reservas em aberto', 'valor' => $this->reservasEmAberto($produto)->sum('qty'), 'icone' => 'lock', 'tom' => 'info'],
        ];
    }

    // ─────────────────────────────── ESCRITA ───────────────────────────────

    /**
     * As cinco movimentações da tela 52a, num caminho só.
     *
     * Produção não escreve saldo: abre `production_requests` e alimenta o "sob
     * encomenda" — a peça entra no cofre depois, como entrada, quando a bancada
     * entregar. As outras quatro escrevem `stock_items` **e**
     * `stock_movements`, na mesma transação, nunca uma sem a outra (regra 3).
     *
     * @param  array{type: string, product_variant_id: int|string, stock_location_id?: int|string|null, quantity: int|string, reason: string, order_id?: int|string|null, due_date?: string|null, priority?: string|null, occurred_at?: string|null}  $dados
     *
     * @throws ValidationException
     */
    public function registrarMovimentacao(array $dados, User $ator): StockMovement|ProductionRequest
    {
        if ($dados['type'] === StockMovement::TYPE_PRODUCTION) {
            return $this->solicitarProducao($dados, $ator);
        }

        return DB::transaction(function () use ($dados, $ator): StockMovement {
            $item = $this->saldo($dados['product_variant_id'], $dados['stock_location_id'] ?? null);

            $quantidade = (int) $dados['quantity'];
            // `on_hand`, `reserved` e `available` nasceram com nome em portugues
            // e foram renomeadas pela migracao que traduziu o schema. A analise
            // estatica le as migracoes para saber que colunas cada tabela tem, e
            // um `renameColumn` montado em laco nao e legivel para ela — por isso
            // estas leituras usam `getAttribute()`, que devolve exatamente o
            // mesmo valor do acesso por propriedade.
            $antes = (int) $item->getAttribute('available');

            [$onHand, $reservado] = $this->novoSaldo($item, $dados['type'], $quantidade);

            $item->forceFill([
                'on_hand' => $onHand,
                'reserved' => $reservado,
                'available' => $onHand - $reservado,
            ])->save();

            $depois = (int) $item->getAttribute('available');

            $movimento = new StockMovement([
                'stock_item_id' => $item->getKey(),
                'type' => $dados['type'],
                // Ajuste guarda o delta com sinal; os outros, a magnitude — a
                // direcao deles ja esta no `type`.
                'qty' => $dados['type'] === StockMovement::TYPE_ADJUSTMENT ? $depois - $antes : $quantidade,
                'before' => $antes,
                'after' => $depois,
                'reason' => $dados['reason'],
                'actor_id' => $ator->getKey(),
                'order_id' => $dados['order_id'] ?? null,
            ]);

            // "Data e hora" do formulário: o movimento aconteceu quando o
            // operador diz que aconteceu, e `created_at` e a unica coluna de
            // tempo que a tabela tem.
            $momento = $this->momento($dados['occurred_at'] ?? null);

            if ($momento instanceof Carbon) {
                $movimento->created_at = $momento;
                $movimento->updated_at = $momento;
            }

            $movimento->save();

            $this->auditoria->log(
                $dados['type'] === StockMovement::TYPE_ADJUSTMENT ? 'velaro.stock.adjusted' : 'velaro.stock.moved',
                $item,
                ['available' => $antes],
                [
                    'available' => $depois,
                    'type' => $dados['type'],
                    'qty' => $movimento->qty,
                    'reason' => $dados['reason'],
                    'product_variant_id' => $item->product_variant_id,
                    'stock_location_id' => $item->stock_location_id,
                ],
            );

            return $movimento->refresh();
        });
    }

    /**
     * O novo saldo físico e o novo reservado, por tipo de movimentação.
     *
     * Saída e reserva batem no **disponível**, não no saldo físico: peça já
     * reservada tem dono, e deixar o disponível negativo entregaria ao portal
     * uma peça que não existe.
     *
     * @return array{0: int, 1: int}
     *
     * @throws ValidationException
     */
    private function novoSaldo(StockItem $item, string $tipo, int $quantidade): array
    {
        $onHand = (int) $item->getAttribute('on_hand');
        $reservado = (int) $item->getAttribute('reserved');
        $disponivel = (int) $item->getAttribute('available');

        return match ($tipo) {
            StockMovement::TYPE_INBOUND => [$onHand + $quantidade, $reservado],
            StockMovement::TYPE_OUTBOUND => $quantidade > $disponivel
                ? throw ValidationException::withMessages([
                    'quantity' => 'Saída maior que o disponível: há '.$disponivel.' unidade(s) livre(s) neste local.',
                ])
                : [$onHand - $quantidade, $reservado],
            StockMovement::TYPE_RESERVATION => $quantidade > $disponivel
                ? throw ValidationException::withMessages([
                    'quantity' => 'Reserva maior que o disponível: há '.$disponivel.' unidade(s) livre(s) neste local.',
                ])
                : [$onHand, $reservado + $quantidade],
            // No ajuste a quantidade digitada e o novo saldo, nao o delta.
            StockMovement::TYPE_ADJUSTMENT => $quantidade < $reservado
                ? throw ValidationException::withMessages([
                    'quantity' => 'O novo saldo não pode ficar abaixo das '.$reservado.' unidade(s) reservada(s).',
                ])
                : [$quantidade, $reservado],
            default => throw ValidationException::withMessages([
                'type' => 'Tipo de movimentação desconhecido: '.$tipo.'.',
            ]),
        };
    }

    /**
     * "Solicitar produção": abre a ordem para a bancada.
     *
     * Não mexe em saldo — por isso não gera `stock_movements`. A peça entra no
     * cofre quando a bancada entregar, e aí sim como entrada.
     *
     * @param  array{product_variant_id: int|string, stock_location_id?: int|string|null, quantity: int|string, reason: string, due_date?: string|null, priority?: string|null}  $dados
     */
    private function solicitarProducao(array $dados, User $ator): ProductionRequest
    {
        return DB::transaction(function () use ($dados, $ator): ProductionRequest {
            $pedido = ProductionRequest::create([
                'product_variant_id' => $dados['product_variant_id'],
                'stock_location_id' => $dados['stock_location_id'] ?? null,
                'qty_requested' => (int) $dados['quantity'],
                'qty_delivered' => 0,
                'status' => ProductionRequest::STATUS_PENDING,
                'priority' => $dados['priority'] ?? ProductionRequest::PRIORITY_NORMAL,
                'due_date' => $dados['due_date'] ?? null,
                'note' => $dados['reason'],
                'requested_by' => $ator->getKey(),
            ]);

            $this->auditoria->log('velaro.stock.production_requested', $pedido, null, [
                'product_variant_id' => $pedido->product_variant_id,
                'qty_requested' => $pedido->qty_requested,
                'priority' => $pedido->priority,
                'reason' => $dados['reason'],
            ]);

            return $pedido;
        });
    }

    /**
     * A linha de saldo do aro naquele cofre.
     *
     * `firstOrCreate` porque a primeira entrada de um aro num cofre novo é
     * legítima: o `UNIQUE(product_variant_id, stock_location_id)` garante que
     * ela nasce uma vez só.
     */
    private function saldo(int|string $varianteId, int|string|null $localId): StockItem
    {
        $local = $localId !== null && $localId !== ''
            ? (int) $localId
            : StockLocation::query()->where('is_default', true)->value('id');

        return StockItem::query()->firstOrCreate(
            ['product_variant_id' => (int) $varianteId, 'stock_location_id' => $local],
            ['on_hand' => 0, 'reserved' => 0, 'available' => 0, 'minimum' => 0, 'restock_point' => 0],
        );
    }

    /**
     * As linhas de saldo, recortadas pelo cofre escolhido no filtro "Local".
     *
     * @param  list<StockItem>  $itens
     * @return Collection<int, StockItem>
     */
    private function saldosDoLocal(array $itens, int|string|null $local): Collection
    {
        $lista = new Collection($itens);

        if ($local === null || $local === '') {
            return $lista->values();
        }

        return $lista
            ->filter(static fn (StockItem $item): bool => (int) $item->getAttribute('stock_location_id') === (int) $local)
            ->values();
    }

    private function momento(?string $valor): ?Carbon
    {
        if ($valor === null || trim($valor) === '') {
            return null;
        }

        $momento = Carbon::parse($valor);

        return $momento->isFuture() ? Carbon::now() : $momento;
    }

    // ─────────────────────────────── APOIO DAS TELAS ───────────────────────────────

    /**
     * O que os selects das três telas precisam.
     *
     * @return array<string, mixed>
     */
    public function opcoesDeFiltro(): array
    {
        return [
            'locais' => StockLocation::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'is_default']),
            'categorias' => Category::query()->orderBy('name')->get(['id', 'name']),
            'situacoes' => [
                self::SITUACAO_EM_ESTOQUE,
                self::SITUACAO_BAIXO_ESTOQUE,
                self::SITUACAO_RESERVADO,
                self::SITUACAO_SEM_ESTOQUE,
            ],
            'tipos' => [
                StockMovement::TYPE_INBOUND,
                StockMovement::TYPE_OUTBOUND,
                StockMovement::TYPE_ADJUSTMENT,
                StockMovement::TYPE_PRODUCTION,
                StockMovement::TYPE_RESERVATION,
            ],
            'prioridades' => [
                ProductionRequest::PRIORITY_LOW,
                ProductionRequest::PRIORITY_NORMAL,
                ProductionRequest::PRIORITY_HIGH,
                ProductionRequest::PRIORITY_URGENT,
            ],
        ];
    }

    /**
     * O SKU/aro escolhido no formulário 52a, com o saldo que a coluna direita
     * mostra — e o impacto que a movimentação teria.
     *
     * @return array<string, mixed>|null
     */
    public function fichaDaVariante(?ProductVariant $variante, int|string|null $local = null): ?array
    {
        if (! $variante instanceof ProductVariant) {
            return null;
        }

        $variante->loadMissing(['product.collection', 'product.material', 'product.finish', 'stockItems.stockLocation']);

        $itens = $this->saldosDoLocal($variante->stockItems->all(), $local);

        $onHand = (int) $itens->sum('on_hand');
        $reserved = (int) $itens->sum('reserved');
        $minimum = (int) $itens->sum('minimum');

        return [
            'variante' => $variante,
            'capa' => $this->capa($variante->product),
            'onHand' => $onHand,
            'reserved' => $reserved,
            'available' => (int) $itens->sum('available'),
            'minimum' => $minimum,
            'restockPoint' => (int) $itens->sum('restock_point'),
            'situacao' => $this->situacao($onHand, $reserved, $minimum),
            'local' => $itens->first()?->stockLocation,
        ];
    }

    /**
     * Os pedidos que ainda podem segurar peça numa reserva.
     *
     * Pedido já retirado sai da lista: reservar saldo para quem já levou não
     * segura nada, e é o mesmo recorte que {@see reservasEmAberto()} usa para
     * dizer o que continua reservado. A opção mostra o `public_number` — a
     * referência externa do pedido —, nunca o `id` interno, que fica só no
     * `value` do select como em qualquer outro campo de relacionamento.
     *
     * @return EloquentCollection<int, Order>
     */
    public function pedidosQuePodemReservar(): EloquentCollection
    {
        return Order::query()
            ->where('operational_status', '!=', Order::OPERATIONAL_STATUS_PICKED_UP)
            ->with(['reseller:id,trade_name', 'customer:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * As variantes ativas para o select "Tamanho (aro)", agrupadas por produto
     * — o `<optgroup>` que faz o formulário funcionar sem uma linha de JS.
     *
     * @return EloquentCollection<int, ProductVariant>
     */
    public function variantesDoCatalogo(): EloquentCollection
    {
        return ProductVariant::query()
            ->where('is_active', true)
            ->whereHas('product', fn (Builder $produto): Builder => $produto->where('is_active', true))
            ->with('product:id,name,sku')
            ->orderBy('product_id')
            ->orderBy('ring_size')
            ->get();
    }
}
