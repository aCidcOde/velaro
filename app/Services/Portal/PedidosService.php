<?php

/*
[Modulo: app/Services/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Monta a lista e o detalhe de pedidos do lojista (telas 2.5 e 2.11): filtros, itens, valores, timeline e retirada.
*/

namespace App\Services\Portal;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Models\OrderBatch;
use App\Models\OrderItem;
use App\Models\OrderItemEngraving;
use App\Models\OrderStatusEvent;
use App\Models\Payment;
use App\Models\ResellerStore;
use App\Models\Shipment;
use App\Services\Portal\Concerns\FormataDados;
use App\Support\ResellerScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * Pedidos do lojista — telas 2.5 (lista e detalhe) e 2.11 (o detalhe no estado
 * "pronto para retirada").
 *
 * Três regras moldam este arquivo:
 *
 * 1. **Escopo.** Toda consulta sai de {@see ResellerScope::orders()}; não existe
 *    `Order::query()` aqui. Vale inclusive para a gaveta lateral, que é aberta
 *    por `?pedido=` na query string e por isso não passa pelo route model
 *    binding — ela é resolvida dentro do escopo, e o número de outro lojista
 *    simplesmente não abre gaveta nenhuma.
 * 2. **Dois status independentes** (§6): `operational_status` e `payment_status`
 *    nunca são derivados um do outro. Ver {@see StatusDoPedido}.
 * 3. **`public_number` é o identificador externo.** O `orders.id` interno não
 *    entra em URL nem em link desta tela.
 *
 * Os valores exibidos são **custo Velaro** — o que a loja paga à fábrica. É a
 * tela em que esse número aparece de propósito; o que não pode vazar é o preço
 * de um lojista para outro, e disso cuida o escopo.
 */
class PedidosService
{
    use FormataDados;

    /**
     * Horário de atendimento na prévia da mensagem de retirada.
     *
     * `reseller_stores` ainda não tem coluna de horário; enquanto não tiver, o
     * texto é o do protótipo (38-portal-retirada.html) e fica isolado aqui, num
     * lugar só, em vez de espalhado pela view.
     */
    private const HORARIO_DA_LOJA = 'seg. a sex., das 9h às 18h.';

    /**
     * Colunas como `expected_at`, `picked_up_at`, `ring_size` e `shape` nasceram
     * com nome em português e foram renomeadas pela migração que traduziu o
     * schema. A análise estática lê as migrações para saber que colunas cada
     * tabela tem, e um `renameColumn` montado em laço não é legível para ela —
     * por isso essas leituras usam `getAttribute()`, que devolve exatamente o
     * mesmo valor do acesso por propriedade.
     */
    public function __construct(
        private readonly ResellerScope $escopo,
        private readonly StatusDoPedido $status,
    ) {}

    /**
     * Dados de `GET /portal/pedidos`.
     *
     * @param  array{q: string|null, periodo: int, status: string|null, pagamento: string|null, lote: string|null, gravacao: string|null, pedido: string|null, porPagina: int}  $filtros
     * @return array<string, mixed>
     */
    public function montarIndice(array $filtros): array
    {
        $pedidos = $this->listar($filtros);
        $linhas = $this->linhas($pedidos->getCollection());

        return [
            'filtros' => $filtros,
            'kpis' => $this->kpis(),
            'opcoes' => $this->opcoesDeFiltro(),
            'pedidos' => $pedidos,
            'linhas' => $linhas,
            'gaveta' => $this->gaveta($filtros, $pedidos->getCollection()),
            'temFiltro' => $this->temFiltro($filtros),
            'carteiraVazia' => $this->escopo->orders()->count() === 0,
        ];
    }

    /**
     * Dados de `GET /portal/pedidos/{public_number}`.
     *
     * O pedido chega verificado pelo binding escopado — o de outro lojista já
     * virou 404 antes do controller.
     *
     * @return array<string, mixed>
     */
    public function montarDetalhe(Order $pedido): array
    {
        $pedido->loadMissing([
            'customer',
            'batch',
            'shipment',
            'items.product.material',
            'items.product.finish',
            'items.product.images',
            'items.variant',
            'items.engraving',
            'statusEvents.actor',
        ]);

        $itens = $this->itens($pedido);
        $loja = $this->escopo->store();

        return [
            'pedido' => $pedido,
            'numero' => $this->texto($pedido->public_number),
            'operacional' => $this->status->operacional($this->textoOuNulo($pedido->operational_status)),
            'pagamentoStatus' => $this->status->pagamento($this->textoOuNulo($pedido->payment_status)),
            'criadoEm' => $this->dataHora($pedido->created_at),
            'atualizadoEm' => $this->dataHora($pedido->updated_at),
            'identidade' => $this->identidade($pedido, $itens),
            'itens' => $itens,
            'valores' => $this->valores($pedido),
            'gravacao' => $this->gravacao($pedido, $itens),
            'linhaDoTempo' => $this->linhaDoTempo($pedido, $loja),
            'historico' => $this->historico($pedido),
            'entrega' => $this->entrega($pedido, $loja),
            'pagamento' => $this->pagamento($pedido),
            'nota' => $this->nota($pedido),
            'cliente' => $this->cliente($pedido),
            'retirada' => $this->retirada($pedido, $loja),
            'acoes' => $this->acoes($pedido),
            'observacao' => $this->textoOuNulo($pedido->notes),
        ];
    }

    // ─────────────────────────────── LISTA ───────────────────────────────

    /**
     * Os seis KPIs do topo. Cada um conta a carteira inteira e leva para a lista
     * já filtrada com `periodo=0` — sem isso o número do cartão e a lista que ele
     * abre discordariam, porque a tela nasce em "últimos 90 dias".
     *
     * @return list<array{rotulo: string, valor: string, icone: string, tom: string, url: string, cta: string}>
     */
    private function kpis(): array
    {
        $emProducao = [Order::OPERATIONAL_STATUS_IN_PRODUCTION, Order::OPERATIONAL_STATUS_PRODUCTION_COMPLETED];
        $aguardando = [Order::PAYMENT_STATUS_PENDING, Order::PAYMENT_STATUS_AWAITING_CLEARANCE];

        return [
            [
                'rotulo' => 'Todos os pedidos',
                'valor' => (string) $this->escopo->orders()->count(),
                'icone' => 'list', 'tom' => 'gold', 'cta' => 'Ver todos →',
                'url' => route('portal.pedidos.index', ['periodo' => 0]),
            ],
            [
                'rotulo' => 'Aguardando pagamento',
                'valor' => (string) $this->escopo->orders()->whereIn('payment_status', $aguardando)->count(),
                'icone' => 'coin', 'tom' => 'warn', 'cta' => 'Ver pedidos →',
                'url' => route('portal.pedidos.index', ['periodo' => 0, 'pagamento' => Order::PAYMENT_STATUS_PENDING]),
            ],
            [
                'rotulo' => 'Em produção',
                'valor' => (string) $this->escopo->orders()->whereIn('operational_status', $emProducao)->count(),
                'icone' => 'factory', 'tom' => 'violet', 'cta' => 'Ver pedidos →',
                'url' => route('portal.pedidos.index', ['periodo' => 0, 'status' => Order::OPERATIONAL_STATUS_IN_PRODUCTION]),
            ],
            [
                'rotulo' => 'Em transporte',
                'valor' => (string) $this->escopo->orders()->where('operational_status', Order::OPERATIONAL_STATUS_IN_TRANSIT)->count(),
                'icone' => 'truck', 'tom' => 'info', 'cta' => 'Ver pedidos →',
                'url' => route('portal.pedidos.index', ['periodo' => 0, 'status' => Order::OPERATIONAL_STATUS_IN_TRANSIT]),
            ],
            [
                'rotulo' => 'Entregues',
                'valor' => (string) $this->escopo->orders()->where('operational_status', Order::OPERATIONAL_STATUS_PICKED_UP)->count(),
                'icone' => 'check', 'tom' => 'ok', 'cta' => 'Ver pedidos →',
                'url' => route('portal.pedidos.index', ['periodo' => 0, 'status' => Order::OPERATIONAL_STATUS_PICKED_UP]),
            ],
            [
                // O módulo não tem status operacional "cancelado": o cancelamento
                // do pedido B2B mora do lado financeiro, em `payment_status`.
                'rotulo' => 'Cancelados',
                'valor' => (string) $this->escopo->orders()->where('payment_status', Order::PAYMENT_STATUS_CANCELED)->count(),
                'icone' => 'x', 'tom' => 'danger', 'cta' => 'Ver pedidos →',
                'url' => route('portal.pedidos.index', ['periodo' => 0, 'pagamento' => Order::PAYMENT_STATUS_CANCELED]),
            ],
        ];
    }

    /**
     * @param  array{q: string|null, periodo: int, status: string|null, pagamento: string|null, lote: string|null, gravacao: string|null, pedido: string|null, porPagina: int}  $filtros
     * @return LengthAwarePaginator<int, Order>
     */
    private function listar(array $filtros): LengthAwarePaginator
    {
        $consulta = $this->escopo->orders()
            ->with(['customer', 'batch'])
            ->withCount('items')
            ->withSum('items', 'quantity');

        if ($filtros['q'] !== null) {
            $busca = '%'.$filtros['q'].'%';

            $consulta->where(function (Builder $consulta) use ($busca): void {
                $consulta->where('orders.public_number', 'like', $busca)
                    ->orWhere('orders.reference', 'like', $busca)
                    ->orWhereHas('customer', fn (Builder $cliente): Builder => $cliente->where('customers.name', 'like', $busca))
                    ->orWhereHas('items.product', fn (Builder $produto): Builder => $produto->where('products.name', 'like', $busca));
            });
        }

        if ($filtros['periodo'] > 0) {
            $consulta->where('orders.created_at', '>=', Carbon::now()->subDays($filtros['periodo']));
        }

        if ($filtros['status'] !== null) {
            $consulta->where('orders.operational_status', $filtros['status']);
        }

        if ($filtros['pagamento'] !== null) {
            $consulta->where('orders.payment_status', $filtros['pagamento']);
        }

        if ($filtros['lote'] !== null) {
            $consulta->whereHas('batch', fn (Builder $lote): Builder => $lote->where('order_batches.code', $filtros['lote']));
        }

        if ($filtros['gravacao'] !== null) {
            $comGravacao = fn (Builder $item): Builder => $item->whereHas(
                'engraving',
                fn (Builder $gravacao): Builder => $gravacao->where('order_item_engravings.enabled', true),
            );

            $filtros['gravacao'] === 'sim'
                ? $consulta->whereHas('items', $comGravacao)
                : $consulta->whereDoesntHave('items', $comGravacao);
        }

        return $consulta
            ->orderByDesc('orders.created_at')
            ->orderByDesc('orders.id')
            ->paginate($filtros['porPagina'])
            ->withQueryString();
    }

    /**
     * @param  EloquentCollection<int, Order>  $pedidos
     * @return list<array<string, mixed>>
     */
    private function linhas(EloquentCollection $pedidos): array
    {
        return $pedidos->map(fn (Order $pedido): array => [
            'numero' => $this->texto($pedido->public_number),
            'cliente' => $this->textoOuNulo($pedido->customer?->name),
            'clienteUrl' => $pedido->customer instanceof Customer ? route('portal.clientes.show', $pedido->customer) : null,
            'data' => $this->data($pedido->created_at),
            'hora' => $this->hora($pedido->created_at),
            'linhas' => (int) $pedido->getAttribute('items_count'),
            'unidades' => (int) $pedido->getAttribute('items_sum_quantity'),
            'valor' => $this->dinheiro($pedido->total_amount),
            'operacional' => $this->status->operacional($this->textoOuNulo($pedido->operational_status)),
            'pagamento' => $this->status->pagamento($this->textoOuNulo($pedido->payment_status)),
            'previsao' => $this->data($pedido->getAttribute('expected_at')),
            'lote' => $this->textoOuNulo($pedido->batch?->code),
            'url' => route('portal.pedidos.show', $pedido),
            'gavetaUrl' => route('portal.pedidos.index', array_merge(
                request()->query(),
                ['pedido' => $this->texto($pedido->public_number)],
            )),
        ])->all();
    }

    /**
     * A gaveta lateral do protótipo, servida pelo servidor.
     *
     * O parâmetro vem pela query string (`?pedido=ORD012548`) e por isso **não**
     * passa pelo route model binding escopado. A resolução acontece dentro do
     * escopo do revendedor: número de outro lojista não encontra nada e a gaveta
     * some — sem 403 e sem mensagem, que é o que faria a diferença de resposta
     * confirmar a existência do pedido alheio.
     *
     * @param  array{q: string|null, periodo: int, status: string|null, pagamento: string|null, lote: string|null, gravacao: string|null, pedido: string|null, porPagina: int}  $filtros
     * @param  EloquentCollection<int, Order>  $daPagina
     * @return array<string, mixed>|null
     */
    private function gaveta(array $filtros, EloquentCollection $daPagina): ?array
    {
        if ($filtros['pedido'] !== null) {
            $pedido = $this->escopo->orders()->where('public_number', $filtros['pedido'])->first();

            return $pedido instanceof Order ? $this->resumoDaGaveta($pedido) : null;
        }

        // Sem parâmetro a gaveta abre no primeiro pedido da página, como o
        // protótipo mostra a tela: lista à esquerda, detalhe à direita.
        $primeiro = $daPagina->first();

        return $primeiro instanceof Order ? $this->resumoDaGaveta($primeiro) : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function resumoDaGaveta(Order $pedido): array
    {
        $pedido->loadMissing([
            'customer',
            'batch',
            'items.product.material',
            'items.product.finish',
            'items.product.images',
            'items.variant',
            'items.engraving',
        ]);

        $itens = $this->itens($pedido);

        return [
            'numero' => $this->texto($pedido->public_number),
            'cliente' => $this->textoOuNulo($pedido->customer?->name),
            'criadoEm' => $this->dataHora($pedido->created_at),
            'previsao' => $this->data($pedido->getAttribute('expected_at')),
            'operacional' => $this->status->operacional($this->textoOuNulo($pedido->operational_status)),
            'pagamento' => $this->status->pagamento($this->textoOuNulo($pedido->payment_status)),
            'gravacao' => $this->gravacao($pedido, $itens),
            'itens' => $itens,
            'valores' => $this->valores($pedido),
            'url' => route('portal.pedidos.show', $pedido),
            'pagamentoUrl' => $this->urlDoPagamento($pedido),
            // Fechar a gaveta é tirar `?pedido=` da URL, preservando os filtros.
            'fecharUrl' => route('portal.pedidos.index', Arr::except(request()->query(), ['pedido'])),
        ];
    }

    // ─────────────────────────────── DETALHE ───────────────────────────────

    /**
     * A barra de identificação do topo do detalhe.
     *
     * @param  list<array<string, mixed>>  $itens
     * @return list<array{rotulo: string, valor: string, detalhe: string|null}>
     */
    private function identidade(Order $pedido, array $itens): array
    {
        $unidades = array_sum(array_map(static fn (array $item): int => (int) $item['quantidade'], $itens));

        return [
            [
                'rotulo' => 'Cliente final',
                'valor' => (string) ($this->textoOuNulo($pedido->customer?->name) ?? self::VAZIO),
                'detalhe' => $this->textoOuNulo($pedido->customer?->document),
            ],
            ['rotulo' => 'Itens', 'valor' => $unidades.' '.($unidades === 1 ? 'unidade' : 'unidades'), 'detalhe' => count($itens).' '.(count($itens) === 1 ? 'modelo' : 'modelos')],
            ['rotulo' => 'Total (custo Velaro)', 'valor' => $this->dinheiro($pedido->total_amount), 'detalhe' => null],
            ['rotulo' => 'Lote', 'valor' => (string) ($this->textoOuNulo($pedido->batch?->code) ?? self::VAZIO), 'detalhe' => null],
            ['rotulo' => 'Entrega prevista', 'valor' => (string) ($this->data($pedido->getAttribute('expected_at')) ?? self::VAZIO), 'detalhe' => null],
        ];
    }

    /**
     * Itens com o preço unitário congelado no momento da escolha.
     *
     * `order_items.unit_price` é snapshot imutável: mudar o catálogo depois não
     * pode reescrever o que o pedido custou. Por isso o valor da linha sai do
     * item, nunca de `products.price`.
     *
     * @return list<array<string, mixed>>
     */
    private function itens(Order $pedido): array
    {
        return $pedido->items->map(function (OrderItem $item): array {
            $produto = $item->product;
            $gravacao = $item->engraving;
            $capa = $produto?->images
                ->sortBy([['is_primary', 'desc'], ['position', 'asc'], ['id', 'asc']])
                ->first();

            return [
                'nome' => (string) ($this->textoOuNulo($produto?->name) ?? 'Item removido do catálogo'),
                'sku' => $this->textoOuNulo($produto?->sku),
                'especificacao' => $this->especificacao($item),
                'aro' => $this->textoOuNulo($item->variant?->getAttribute('ring_size')),
                'quantidade' => (int) $item->quantity,
                'unitario' => $this->dinheiro($item->unit_price),
                'total' => $this->dinheiro($item->total_price),
                'imagem' => $capa === null ? null : asset($this->texto($capa->path)),
                'alt' => (string) ($this->textoOuNulo($produto?->name) ?? 'Par de alianças'),
                'gravacao' => $gravacao instanceof OrderItemEngraving && $gravacao->enabled === true
                    ? [
                        'texto' => $this->textoOuNulo($gravacao->text),
                        'data' => $this->data($gravacao->date),
                        'caracteres' => (int) $gravacao->chars,
                        'preco' => $this->dinheiro($gravacao->price),
                    ]
                    : null,
            ];
        })->all();
    }

    /**
     * `Ouro 18k · Anatômica / Polido` — a linha de especificação do item.
     */
    private function especificacao(OrderItem $item): string
    {
        $produto = $item->product;

        $partes = array_filter([
            $this->textoOuNulo($produto?->material?->name),
            $this->textoOuNulo($produto?->getAttribute('shape')),
            $this->textoOuNulo($produto?->finish?->name),
        ]);

        return implode(' · ', $partes);
    }

    /**
     * As quatro linhas do "Resumo do pedido" mais o total.
     *
     * Elas são colunas de `orders`, não uma conta feita na view: o que o lojista
     * deve à Velaro é o que está gravado no pedido.
     *
     * @return array{linhas: list<array{rotulo: string, valor: string, destaque: bool}>, total: string, totalBruto: float}
     */
    private function valores(Order $pedido): array
    {
        $gravacao = (float) $pedido->engraving_amount;

        return [
            'linhas' => [
                ['rotulo' => 'Subtotal dos itens', 'valor' => $this->dinheiro($pedido->subtotal_amount), 'destaque' => false],
                ['rotulo' => 'Gravação interna', 'valor' => $this->dinheiro($gravacao), 'destaque' => $gravacao > 0.0],
                ['rotulo' => 'Frete', 'valor' => $this->dinheiro($pedido->shipping_amount), 'destaque' => false],
                ['rotulo' => 'Descontos', 'valor' => $this->dinheiro($pedido->discount_amount), 'destaque' => false],
            ],
            'total' => $this->dinheiro($pedido->total_amount),
            'totalBruto' => (float) $pedido->total_amount,
        ];
    }

    /**
     * Card "Gravação interna": a soma das gravações do pedido.
     *
     * Regra 2 da tela 2.5 — o detalhe registra a gravação adicional. O card
     * aparece mesmo quando ninguém pediu gravação, dizendo "Solicitada: Não", em
     * vez de sumir: ausência de card não é resposta, é dúvida.
     *
     * @param  list<array<string, mixed>>  $itens
     * @return array<string, mixed>
     */
    private function gravacao(Order $pedido, array $itens): array
    {
        $comGravacao = array_values(array_filter($itens, static fn (array $item): bool => $item['gravacao'] !== null));
        $unidades = array_sum(array_map(static fn (array $item): int => (int) $item['quantidade'], $comGravacao));
        $limite = $pedido->items
            ->map(static fn (OrderItem $item): int => (int) $item->product?->getAttribute('engraving_max_chars'))
            ->filter(static fn (int $limite): bool => $limite > 0)
            ->max();

        return [
            'solicitada' => $comGravacao !== [],
            'textos' => array_values(array_map(
                static fn (array $item): array => [
                    'produto' => $item['nome'],
                    'texto' => $item['gravacao']['texto'],
                    'data' => $item['gravacao']['data'],
                    'caracteres' => $item['gravacao']['caracteres'],
                ],
                $comGravacao,
            )),
            'unidades' => (int) $unidades,
            'limite' => is_int($limite) && $limite > 0 ? 'até '.$limite.' caracteres' : null,
            'custo' => $this->dinheiro($pedido->engraving_amount),
        ];
    }

    /**
     * A linha do tempo da tela 2.5 e da 2.11.
     *
     * Os degraus são a cadeia canônica de {@see StatusDoPedido::CADEIA_OPERACIONAL};
     * a data de cada um vem de `order_status_events`, a trilha real. O degrau
     * atual é `now`, os anteriores `done`, os seguintes `todo` — e um degrau sem
     * evento correspondente não vira `done` por estar antes do atual, porque a
     * linha do tempo mostra o que aconteceu, não o que deveria ter acontecido.
     *
     * @return list<array{rotulo: string, descricao: string|null, quando: string, estado: string}>
     */
    private function linhaDoTempo(Order $pedido, ?ResellerStore $loja): array
    {
        $eventos = $pedido->statusEvents
            ->where('scope', OrderStatusEvent::SCOPE_OPERATIONAL)
            ->keyBy(fn (OrderStatusEvent $evento): string => $this->texto($evento->to_status));

        $atual = $this->status->degrau($this->textoOuNulo($pedido->operational_status));
        $pago = $this->texto($pedido->payment_status) === Order::PAYMENT_STATUS_PAID;
        $lote = $this->textoOuNulo($pedido->batch?->code);

        $degraus = [];

        foreach (StatusDoPedido::CADEIA_OPERACIONAL as $posicao => $chave) {
            /** @var OrderStatusEvent|null $evento */
            $evento = $eventos->get($chave);

            $estado = match (true) {
                $posicao === $atual => 'now',
                $posicao < $atual => 'done',
                default => 'todo',
            };

            $degraus[] = [
                'rotulo' => $this->status->operacional($chave)['rotulo'],
                'descricao' => $this->descricaoDoDegrau($chave, $loja, $lote, $pago),
                'quando' => (string) ($this->dataHora($evento?->created_at) ?? self::VAZIO),
                'estado' => $estado,
            ];
        }

        return $degraus;
    }

    private function descricaoDoDegrau(string $chave, ?ResellerStore $loja, ?string $lote, bool $pago): ?string
    {
        $nomeDaLoja = $this->textoOuNulo($loja?->name) ?? $this->textoOuNulo($this->escopo->reseller->trade_name);
        $doLote = $lote === null ? 'do lote' : 'do lote '.$lote;

        return match ($chave) {
            Order::OPERATIONAL_STATUS_REGISTERED => $nomeDaLoja === null ? 'Criado no Portal' : 'Criado no Portal por '.$nomeDaLoja,
            Order::OPERATIONAL_STATUS_PAYMENT_CONFIRMED => $pago
                ? ucfirst($doLote).' quitado'
                : 'Liberada após a confirmação do pagamento '.$doLote,
            Order::OPERATIONAL_STATUS_IN_PRODUCTION => 'Produção da peça na fábrica Velaro',
            Order::OPERATIONAL_STATUS_PRODUCTION_COMPLETED => 'Peça pronta, aguardando expedição',
            Order::OPERATIONAL_STATUS_IN_TRANSIT => 'Envio da fábrica para a loja do revendedor',
            Order::OPERATIONAL_STATUS_READY_FOR_PICKUP => 'Chegada na loja confirmada · notificações disparadas ao cliente',
            Order::OPERATIONAL_STATUS_PICKED_UP => 'Retirado pelo cliente na loja',
            default => null,
        };
    }

    /**
     * Card "Histórico de atualizações": os eventos como aconteceram, do mais
     * recente para o mais antigo, com os dois escopos misturados na mesma trilha.
     *
     * @return list<array{rotulo: string, escopo: string, de: string|null, nota: string|null, ator: string|null, quando: string|null}>
     */
    private function historico(Order $pedido): array
    {
        return $pedido->statusEvents
            // Data mais o id como desempate: dois eventos podem cair no mesmo
            // segundo, e sem o segundo critério a ordem do histórico oscilaria
            // entre uma carga e outra.
            ->sortByDesc(fn (OrderStatusEvent $evento): string => (string) $evento->created_at?->format('Y-m-d H:i:s').str_pad((string) $evento->getKey(), 10, '0', STR_PAD_LEFT))
            ->map(function (OrderStatusEvent $evento): array {
                $escopo = $this->texto($evento->scope);
                $traduz = fn (?string $chave): ?string => $chave === null
                    ? null
                    : ($escopo === OrderStatusEvent::SCOPE_PAYMENT
                        ? $this->status->pagamento($chave)['rotulo']
                        : $this->status->operacional($chave)['rotulo']);

                return [
                    'rotulo' => (string) $traduz($this->textoOuNulo($evento->to_status)),
                    'escopo' => (string) ($this->rotulo('order.event_scope.'.$escopo) ?? $escopo),
                    'de' => $traduz($this->textoOuNulo($evento->from_status)),
                    'nota' => $this->textoOuNulo($evento->note),
                    'ator' => $this->textoOuNulo($evento->actor?->name),
                    'quando' => $this->diaHora($evento->created_at),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Card "Entrega e retirada". A Velaro entrega na loja do lojista; quem
     * entrega ao consumidor final é a loja.
     *
     * @return array<string, mixed>
     */
    private function entrega(Order $pedido, ?ResellerStore $loja): array
    {
        $remessa = $pedido->shipment;

        return [
            'modo' => 'Retirada na loja',
            'loja' => $this->textoOuNulo($loja?->name) ?? $this->textoOuNulo($this->escopo->reseller->trade_name),
            'endereco' => $this->enderecoDaLoja($loja),
            'chegadaPrevista' => $this->data($pedido->getAttribute('expected_at')),
            'chegadaEm' => $this->dataHora($pedido->arrived_at),
            'transportadora' => $remessa instanceof Shipment ? $this->textoOuNulo($remessa->carrier) : null,
            'rastreio' => $remessa instanceof Shipment ? $this->textoOuNulo($remessa->tracking_code) : null,
            'rastreioUrl' => $remessa instanceof Shipment ? $this->textoOuNulo($remessa->tracking_url) : null,
        ];
    }

    private function enderecoDaLoja(?ResellerStore $loja): ?string
    {
        $daLoja = $this->textoOuNulo($loja?->getAttribute('address'));

        if ($daLoja !== null) {
            return $daLoja;
        }

        $revendedor = $this->escopo->reseller;
        $partes = array_filter([
            $this->textoOuNulo($revendedor->street),
            $this->textoOuNulo($revendedor->street_number),
            $this->textoOuNulo($revendedor->district),
        ]);

        return $partes === [] ? null : implode(', ', $partes);
    }

    /**
     * Card "Pagamento": o pedido é cobrado dentro do lote semanal, e é o lote que
     * tem vencimento e forma de pagamento — não o pedido isolado.
     *
     * @return array<string, mixed>
     */
    private function pagamento(Order $pedido): array
    {
        $lote = $pedido->batch;
        $cobranca = $lote instanceof OrderBatch
            ? $lote->payments()->orderByDesc('id')->first()
            : null;

        return [
            'status' => $this->status->pagamento($this->textoOuNulo($pedido->payment_status)),
            'lote' => $this->textoOuNulo($lote?->code),
            'loteJanela' => $lote instanceof OrderBatch ? $this->data($lote->cut_date) : null,
            'vencimento' => $lote instanceof OrderBatch ? $this->data($lote->due_date) : null,
            'quitadoEm' => $lote instanceof OrderBatch ? $this->dataHora($lote->paid_at) : null,
            'forma' => $cobranca instanceof Payment ? $this->rotulo('payment.method.'.$this->texto($cobranca->method)) : null,
            'valorDoPedido' => $this->dinheiro($pedido->total_amount),
            'totalDoLote' => $lote instanceof OrderBatch ? $this->dinheiro($lote->total_amount) : null,
            'url' => $this->urlDoPagamento($pedido),
        ];
    }

    /**
     * Card "Nota fiscal": a Velaro emite a NF-e da venda B2B ao lojista depois da
     * quitação do lote. O documento fiscal da venda ao consumidor final é da loja.
     *
     * @return array<string, mixed>
     */
    private function nota(Order $pedido): array
    {
        $lote = $pedido->batch;
        $nota = $lote instanceof OrderBatch ? $lote->invoices()->orderByDesc('id')->first() : null;
        $rateio = $nota instanceof Invoice
            ? $nota->items()->where('order_id', $pedido->getKey())->first()
            : null;

        $emitida = $nota instanceof Invoice;

        return [
            'emitida' => $emitida,
            'situacao' => $emitida ? 'Emitida' : 'Aguardando emissão',
            'chip' => $emitida ? 'chip--ok' : 'chip--neutral',
            'numero' => $nota instanceof Invoice ? $this->textoOuNulo($nota->number) : null,
            'serie' => $nota instanceof Invoice ? $this->textoOuNulo($nota->series) : null,
            'emitidaEm' => $nota instanceof Invoice ? $this->data($nota->issued_at) : null,
            'competencia' => $nota instanceof Invoice ? $this->competencia($nota->issued_at) : null,
            'destinatario' => $this->textoOuNulo($this->escopo->reseller->legal_name)
                ?? $this->textoOuNulo($this->escopo->reseller->trade_name),
            'cnpj' => $this->textoOuNulo($this->escopo->reseller->cnpj),
            'valorDoPedido' => $rateio instanceof InvoiceItem ? $this->dinheiro($rateio->amount) : $this->dinheiro($pedido->total_amount),
            'url' => route('portal.financeiro.notas'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function cliente(Order $pedido): array
    {
        $cliente = $pedido->customer;

        if (! $cliente instanceof Customer) {
            return ['existe' => false];
        }

        return [
            'existe' => true,
            'nome' => $this->texto($cliente->name),
            'iniciais' => $this->iniciais($this->texto($cliente->name)),
            'desde' => $this->data($cliente->created_at),
            'documento' => $this->textoOuNulo($cliente->document),
            'telefone' => $this->textoOuNulo($cliente->phone),
            'email' => $this->textoOuNulo($cliente->email),
            'cidadeUf' => $this->cidadeUf($this->textoOuNulo($cliente->getAttribute('city')), $this->textoOuNulo($cliente->getAttribute('state'))),
            'url' => route('portal.clientes.show', $cliente),
        ];
    }

    // ─────────────────────────────── TELA 2.11 ───────────────────────────────

    /**
     * Bloco "pronto para retirada" — a tela 2.11 é este estado do detalhe, não
     * uma rota à parte.
     *
     * Aparece a partir de `ready_for_pickup` e continua depois de `picked_up`,
     * porque nesse ponto ele deixa de ser um painel de disparo e vira o
     * comprovante de quem retirou, quando e com qual documento.
     *
     * @return array<string, mixed>|null
     */
    private function retirada(Order $pedido, ?ResellerStore $loja): ?array
    {
        $situacao = $this->texto($pedido->operational_status);

        if (! in_array($situacao, [Order::OPERATIONAL_STATUS_READY_FOR_PICKUP, Order::OPERATIONAL_STATUS_PICKED_UP], true)) {
            return null;
        }

        $retirado = $situacao === Order::OPERATIONAL_STATUS_PICKED_UP;

        return [
            'pronto' => true,
            'retirado' => $retirado,
            'chegadaEm' => $this->dataHora($pedido->arrived_at),
            'notificacoes' => $this->notificacoes($pedido),
            'previa' => $this->previaDaMensagem($pedido, $loja),
            'confirmacao' => [
                'retiradoPor' => $this->textoOuNulo($pedido->getAttribute('picked_up_by_name')),
                'documento' => $this->textoOuNulo($pedido->getAttribute('picked_up_by_document')),
                'em' => $this->dataHora($pedido->getAttribute('picked_up_at')),
                'peloProprioCliente' => $pedido->getAttribute('picked_up_by_customer_id') !== null,
            ],
            'lote' => $this->textoOuNulo($pedido->batch?->code),
        ];
    }

    /**
     * Log de envio da comunicação de chegada, do mais recente para o mais antigo.
     *
     * O filtro por revendedor é redundante — o pedido já é do lojista — e está
     * aqui de propósito: `notification_logs` guarda destinatário e telefone do
     * consumidor final, e é a última tabela em que valeria a pena economizar uma
     * cláusula.
     *
     * @return list<array<string, mixed>>
     */
    private function notificacoes(Order $pedido): array
    {
        return NotificationLog::query()
            ->where('order_id', $pedido->getKey())
            ->where('reseller_id', $this->escopo->reseller->getKey())
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (NotificationLog $log): array => [
                'destinatarioTipo' => (string) ($this->rotulo('notification.recipient_type.'.$this->texto($log->recipient_type)) ?? $this->texto($log->recipient_type)),
                'canal' => (string) ($this->rotulo('notification.channel.'.$this->texto($log->channel)) ?? $this->texto($log->channel)),
                'icone' => $this->texto($log->channel) === NotificationLog::CHANNEL_WHATSAPP ? 'whats' : 'mail',
                'destinatario' => $this->textoOuNulo($log->recipient),
                'situacao' => (string) ($this->rotulo('notification.status.'.$this->texto($log->status)) ?? $this->texto($log->status)),
                'enviadoEm' => $this->dataHora($log->sent_at),
                'erro' => $this->textoOuNulo($log->error_message),
                'chip' => match ($this->texto($log->status)) {
                    'sent' => 'chip--ok',
                    'failed' => 'chip--danger',
                    default => 'chip--warn',
                },
            ])
            ->all();
    }

    /**
     * Prévia da mensagem que o consumidor recebe.
     *
     * Regra 1 da tela 2.11: a comunicação sai **em nome do revendedor**. A marca
     * Velaro não aparece para o consumidor final — quem assina é a loja, e o
     * endereço, o telefone e o nome do remetente saem de `reseller_stores`.
     *
     * @return array<string, mixed>
     */
    private function previaDaMensagem(Order $pedido, ?ResellerStore $loja): array
    {
        $nomeDaLoja = $this->textoOuNulo($loja?->name)
            ?? $this->textoOuNulo($this->escopo->reseller->trade_name)
            ?? 'Sua loja';
        $cliente = $this->textoOuNulo($pedido->customer?->name) ?? 'Cliente';
        $numero = $this->texto($pedido->public_number);
        $endereco = $this->enderecoDaLoja($loja);

        $agora = Carbon::now();

        return [
            'remetente' => $nomeDaLoja,
            'destinatario' => $cliente,
            // O relógio do celular do protótipo. Mostra a hora real em vez de um
            // horário fixo: é uma prévia, não uma captura de tela decorada.
            'relogio' => ['hora' => $agora->format('H:i'), 'data' => ucfirst($agora->locale('pt_BR')->isoFormat('dddd, D [de] MMMM'))],
            // Cada linha carrega o próprio tom para a view não precisar deduzir
            // pelo índice qual é o endereço e qual é a despedida.
            'whatsapp' => array_values(array_filter([
                ['tom' => 'corpo', 'texto' => 'Olá, '.$cliente.'! Seu pedido #'.$numero.' já chegou à loja e está pronto para retirada.'],
                $endereco === null ? null : ['tom' => 'meta', 'texto' => '📍 Endereço: '.$endereco],
                ['tom' => 'meta', 'texto' => '🕐 Horário: '.self::HORARIO_DA_LOJA],
                ['tom' => 'ok', 'texto' => '✓ Estamos te esperando!'],
            ])),
            'email' => [
                'assunto' => 'Seu pedido está pronto para retirada',
                'corpo' => 'Olá, '.$cliente.'. Informamos que o seu pedido #'.$numero
                    .' já está disponível para retirada na loja '.$nomeDaLoja.'.',
                'assinatura' => 'Agradecemos a sua preferência.',
            ],
            'canais' => [
                ['rotulo' => (string) $this->rotulo('notification.channel.'.NotificationLog::CHANNEL_WHATSAPP), 'destino' => $this->textoOuNulo($pedido->customer?->phone)],
                ['rotulo' => (string) $this->rotulo('notification.channel.'.NotificationLog::CHANNEL_EMAIL), 'destino' => $this->textoOuNulo($pedido->customer?->email)],
            ],
        ];
    }

    // ─────────────────────────────── APOIO ───────────────────────────────

    /**
     * @return array<string, string>
     */
    private function acoes(Order $pedido): array
    {
        $acoes = [
            'voltar' => route('portal.pedidos.index'),
            'pagamento' => $this->urlDoPagamento($pedido),
            'financeiro' => route('portal.financeiro.index'),
            'notas' => route('portal.financeiro.notas'),
            'chamado' => route('portal.suporte.create', ['pedido' => $this->texto($pedido->public_number)]),
            'catalogo' => route('portal.catalogo'),
        ];

        if ($pedido->customer instanceof Customer) {
            $acoes['cliente'] = route('portal.clientes.show', $pedido->customer);
        }

        return $acoes;
    }

    /**
     * O pagamento é do lote. Pedido ainda sem lote cai no financeiro geral, em
     * vez de montar uma rota com parâmetro nulo.
     */
    private function urlDoPagamento(Order $pedido): string
    {
        $lote = $pedido->batch;

        return $lote instanceof OrderBatch
            ? route('portal.financeiro.pagamento', $lote)
            : route('portal.financeiro.index');
    }

    /**
     * @return array<string, list<array{valor: string, rotulo: string}>>
     */
    private function opcoesDeFiltro(): array
    {
        $status = $this->status->opcoesDeFiltro();

        $lotes = $this->escopo->batches()
            ->orderByDesc('cut_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (OrderBatch $lote): array => [
                'valor' => $this->texto($lote->code),
                'rotulo' => $this->texto($lote->code),
            ])
            ->all();

        return [
            'operacional' => $status['operacional'],
            'pagamento' => $status['pagamento'],
            'lotes' => array_values($lotes),
            'periodos' => [
                ['valor' => '30', 'rotulo' => 'Últimos 30 dias'],
                ['valor' => '90', 'rotulo' => 'Últimos 90 dias'],
                ['valor' => '180', 'rotulo' => 'Últimos 6 meses'],
                ['valor' => '365', 'rotulo' => 'Último ano'],
                ['valor' => '0', 'rotulo' => 'Todos os períodos'],
            ],
            'gravacao' => [
                ['valor' => 'sim', 'rotulo' => 'Com gravação'],
                ['valor' => 'nao', 'rotulo' => 'Sem gravação'],
            ],
            'porPagina' => [
                ['valor' => '8', 'rotulo' => '8 por página'],
                ['valor' => '16', 'rotulo' => '16 por página'],
                ['valor' => '32', 'rotulo' => '32 por página'],
            ],
        ];
    }

    /**
     * @param  array{q: string|null, periodo: int, status: string|null, pagamento: string|null, lote: string|null, gravacao: string|null, pedido: string|null, porPagina: int}  $filtros
     */
    private function temFiltro(array $filtros): bool
    {
        return $filtros['q'] !== null
            || $filtros['status'] !== null
            || $filtros['pagamento'] !== null
            || $filtros['lote'] !== null
            || $filtros['gravacao'] !== null
            || $filtros['periodo'] > 0;
    }
}
