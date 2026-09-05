<?php

/*
[Modulo: app/Services/Backend]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Ciclo completo do pedido no Painel Master: abas, lista, detalhe, cadastro manual e as confirmacoes de chegada e retirada.
*/

namespace App\Services\Backend;

use App\Models\Customer;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Models\OrderBatch;
use App\Models\OrderItem;
use App\Models\OrderItemEngraving;
use App\Models\OrderPromotion;
use App\Models\OrderStatusEvent;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Models\PromotionRule;
use App\Models\Reseller;
use App\Models\User;
use App\Services\AdminAuditLogger;
use App\Services\Portal\StatusDoPedido;
use App\Services\Site\SiteContentService;
use App\Services\Vitrine\VitrineCatalogoService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Tela 3.6 — o pedido visto de cima.
 *
 * Quatro coisas mandam aqui, e todas vêm do doc da tela:
 *
 * 1. **Os dois status são independentes** (regra 2). `operational_status` e
 *    `payment_status` nunca são derivados um do outro: são duas colunas, dois
 *    chips e duas escritas separadas. O `status` do scaffold continua sendo só o
 *    espelho documentado em {@see Order} e ninguém o lê como autoridade.
 * 2. **A esteira operacional tem sete degraus** (regra 1) e a ordem canônica é a
 *    de {@see StatusDoPedido::CADEIA_OPERACIONAL} — a mesma que o Portal do
 *    Lojista desenha na tela 2.5. Não existe uma segunda cadeia no Master.
 * 3. **Chegada e retirada se confirmam por pedido e por lote inteiro**
 *    (regra 3). São quatro métodos aqui, não um com um `if`, porque a retirada
 *    do lote carimba `order_batches` além dos pedidos.
 * 4. **O Master enxerga a base inteira.** Ao contrário do Portal, nada aqui
 *    passa por `ResellerScope`; em compensação toda linha mostra o revendedor
 *    dono, como manda a regra 3.2.
 *
 * O que este service **não** faz: não cobra e não move estoque. Reserva de saldo
 * é movimento de estoque com `order_id`, e nasce na tela 3.4 — ver
 * {@see EstoqueService}.
 */
class PedidoService
{
    /** Marcador de `orders.meta`: o pedido nasceu no Painel Interno, não no portal nem na vitrine. */
    public const ORIGEM = 'backend';

    /**
     * Canal do atendimento que gerou o pedido interno (mockup 61, "Canal de
     * origem"). Mora em `orders.meta`, e não numa coluna: é atributo de
     * atendimento, não estado do pedido — nenhuma consulta filtra por ele e
     * nenhuma regra do Anexo I o lê. Chave gravada => rótulo na tela.
     *
     * @var array<string, string>
     */
    public const CANAIS_DE_ORIGEM = [
        'telefone' => 'Telefone',
        'whatsapp' => 'WhatsApp',
        'email' => 'E-mail',
    ];

    /**
     * Modo de entrega do pedido B2B. O contrato tem um só (Anexo I §5.6): a
     * remessa semanal para a loja do lojista. Fica como vocabulário de uma
     * entrada, e não como texto solto na view, para o dia em que houver o
     * segundo.
     *
     * @var array<string, string>
     */
    public const MODOS_DE_ENTREGA = [
        'remessa_semanal' => 'Remessa semanal para a loja do revendedor',
    ];

    /** Aba "Todos" — o padrão da tela. */
    public const ABA_TODOS = 'todos';

    public const ABA_AGUARDANDO_PAGAMENTO = 'aguardando-pagamento';

    public const ABA_EM_PRODUCAO = 'em-producao';

    public const ABA_EM_TRANSPORTE = 'em-transporte';

    public const ABA_CONCLUIDOS = 'concluidos';

    /** Quantos cartões a coluna esquerda mostra por página (protótipo: 1 a 5 de 1.248). */
    private const POR_PAGINA = 5;

    public function __construct(
        private readonly AdminAuditLogger $auditoria,
        private readonly StatusDoPedido $status,
        private readonly SiteContentService $conteudo,
    ) {}

    // ─────────────────────────────── LISTA ───────────────────────────────

    /**
     * Os cinco cartões do topo, que no protótipo também são as abas da lista.
     *
     * Cada um conta a base inteira e leva para a lista já filtrada pela própria
     * aba — sem isso o número do cartão e a lista que ele abre discordariam.
     *
     * @return list<array{aba: string, rotulo: string, valor: int, icone: string, tom: string}>
     */
    public function kpis(): array
    {
        return [
            [
                'aba' => self::ABA_TODOS,
                'rotulo' => 'Todos',
                'valor' => Order::query()->count(),
                'icone' => 'list',
                'tom' => 'gold',
            ],
            [
                'aba' => self::ABA_AGUARDANDO_PAGAMENTO,
                'rotulo' => 'Aguardando pagamento',
                'valor' => Order::query()->whereIn('payment_status', $this->statusDaAba(self::ABA_AGUARDANDO_PAGAMENTO))->count(),
                'icone' => 'coin',
                'tom' => 'warn',
            ],
            [
                'aba' => self::ABA_EM_PRODUCAO,
                'rotulo' => 'Em produção',
                'valor' => Order::query()->whereIn('operational_status', $this->statusDaAba(self::ABA_EM_PRODUCAO))->count(),
                'icone' => 'factory',
                'tom' => 'violet',
            ],
            [
                'aba' => self::ABA_EM_TRANSPORTE,
                'rotulo' => 'Em transporte',
                'valor' => Order::query()->whereIn('operational_status', $this->statusDaAba(self::ABA_EM_TRANSPORTE))->count(),
                'icone' => 'truck',
                'tom' => 'info',
            ],
            [
                'aba' => self::ABA_CONCLUIDOS,
                'rotulo' => 'Concluídos',
                'valor' => Order::query()->whereIn('operational_status', $this->statusDaAba(self::ABA_CONCLUIDOS))->count(),
                'icone' => 'check',
                'tom' => 'ok',
            ],
        ];
    }

    /**
     * A coluna esquerda: busca por número, cliente ou revendedor, recorte por
     * aba, por status operacional e por período.
     *
     * @param  array{aba?: string|null, busca?: string|null, status?: string|null, periodo?: int|string|null}  $filtros
     * @return LengthAwarePaginator<int, Order>
     */
    public function listar(array $filtros = []): LengthAwarePaginator
    {
        $aba = $this->aba($filtros['aba'] ?? null);
        $busca = trim((string) ($filtros['busca'] ?? ''));
        $status = trim((string) ($filtros['status'] ?? ''));
        $periodo = (int) ($filtros['periodo'] ?? 30);

        return Order::query()
            ->with(['customer', 'reseller', 'batch'])
            ->withCount('items')
            ->when($aba === self::ABA_AGUARDANDO_PAGAMENTO, fn (Builder $q): Builder => $q->whereIn('payment_status', $this->statusDaAba($aba)))
            ->when(
                in_array($aba, [self::ABA_EM_PRODUCAO, self::ABA_EM_TRANSPORTE, self::ABA_CONCLUIDOS], true),
                fn (Builder $q): Builder => $q->whereIn('operational_status', $this->statusDaAba($aba)),
            )
            ->when($status !== '', fn (Builder $q): Builder => $q->where('operational_status', $status))
            ->when($periodo > 0, fn (Builder $q): Builder => $q->where('orders.created_at', '>=', Carbon::now()->subDays($periodo)))
            ->when($busca !== '', function (Builder $q) use ($busca): Builder {
                $termo = '%'.$busca.'%';

                return $q->where(function (Builder $interna) use ($termo): void {
                    $interna->where('orders.public_number', 'like', $termo)
                        ->orWhere('orders.reference', 'like', $termo)
                        ->orWhereHas('customer', fn (Builder $cliente): Builder => $cliente->where('customers.name', 'like', $termo))
                        ->orWhereHas('reseller', fn (Builder $lojista): Builder => $lojista
                            ->where('resellers.trade_name', 'like', $termo)
                            ->orWhere('resellers.code', 'like', $termo));
                });
            })
            ->orderByDesc('orders.created_at')
            ->orderByDesc('orders.id')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();
    }

    /**
     * Opções do select "Status" da barra de filtros — a cadeia operacional
     * inteira, com o rótulo traduzido que o Portal também usa.
     *
     * @return list<array{valor: string, rotulo: string}>
     */
    public function opcoesDeStatus(): array
    {
        return $this->status->opcoesDeFiltro()['operacional'];
    }

    /**
     * Rótulo e classe de chip de cada status operacional, num mapa só.
     *
     * A lista precisa do chip de cada linha e a view não faz lógica: o mapa
     * chega pronto do service, com a mesma tradução e a mesma cor que o Portal
     * usa na tela 2.5.
     *
     * @return array<string, array{chave: string, rotulo: string, chip: string}>
     */
    public function chipsOperacionais(): array
    {
        $mapa = [];

        foreach (StatusDoPedido::CADEIA_OPERACIONAL as $chave) {
            $mapa[$chave] = $this->status->operacional($chave);
        }

        return $mapa;
    }

    /**
     * Normaliza a aba pedida na query string. Valor desconhecido cai em "Todos"
     * em vez de devolver lista vazia sem explicação.
     */
    public function aba(?string $aba): string
    {
        $abas = [
            self::ABA_TODOS,
            self::ABA_AGUARDANDO_PAGAMENTO,
            self::ABA_EM_PRODUCAO,
            self::ABA_EM_TRANSPORTE,
            self::ABA_CONCLUIDOS,
        ];

        return in_array($aba, $abas, true) ? (string) $aba : self::ABA_TODOS;
    }

    // ─────────────────────────────── DETALHE ───────────────────────────────

    /**
     * A coluna central e a direita do protótipo: itens com gravação, as linhas
     * de valor, a timeline de sete etapas, o histórico e as notificações.
     *
     * @return array<string, mixed>
     */
    public function detalhe(Order $pedido): array
    {
        $pedido->loadMissing([
            'customer',
            'reseller',
            'batch.payments',
            'shipment',
            'items.product.material',
            'items.product.images',
            'items.variant',
            'items.engraving',
            'statusEvents.actor',
            'notificationLogs',
            'promotions.promotion',
        ]);

        /** @var EloquentCollection<int, OrderStatusEvent> $eventos */
        $eventos = $pedido->statusEvents;

        return [
            'operacional' => $this->status->operacional($pedido->operational_status),
            'pagamento' => $this->status->pagamento($pedido->payment_status),
            'linhaDoTempo' => $this->linhaDoTempo($pedido, $eventos),
            'historico' => $eventos->sortByDesc('created_at')->values(),
            'notificacoes' => $pedido->notificationLogs->sortByDesc('created_at')->values(),
            'formaDePagamento' => $this->formaDePagamento($pedido),
            'canal' => self::CANAIS_DE_ORIGEM[$this->metaTexto($pedido, 'origin_channel')] ?? null,
            'entrega' => $this->enderecoDeEntrega($pedido),
            'proximoStatus' => $this->proximoStatus($pedido),
        ];
    }

    /**
     * As sete etapas da regra 1, com a data em que cada uma foi carimbada.
     *
     * O degrau atual é a posição do `operational_status` na cadeia — o que já
     * passou vem de `order_status_events`, e não de uma segunda fonte de
     * verdade. Status fora do vocabulário devolve `-1` e desenha tudo pendente,
     * em vez de marcar um degrau errado como concluído.
     *
     * @param  EloquentCollection<int, OrderStatusEvent>  $eventos
     * @return list<array{chave: string, rotulo: string, estado: string, em: \DateTimeInterface|null}>
     */
    private function linhaDoTempo(Order $pedido, EloquentCollection $eventos): array
    {
        $atual = $this->status->degrau($pedido->operational_status);

        $carimbos = $eventos
            ->where('scope', OrderStatusEvent::SCOPE_OPERATIONAL)
            ->sortBy('created_at')
            ->keyBy('to_status');

        $etapas = [];

        foreach (StatusDoPedido::CADEIA_OPERACIONAL as $posicao => $chave) {
            /** @var OrderStatusEvent|null $evento */
            $evento = $carimbos->get($chave);

            $etapas[] = [
                'chave' => $chave,
                'rotulo' => $this->status->operacional($chave)['rotulo'],
                'estado' => match (true) {
                    $atual < 0 => 'todo',
                    $posicao < $atual => 'done',
                    $posicao === $atual => 'now',
                    default => 'todo',
                },
                'em' => $evento?->created_at,
            ];
        }

        return $etapas;
    }

    /**
     * "Forma de pagamento (PIX)" do protótipo.
     *
     * O recebimento é do lote, não do pedido: quem paga a Velaro é o lojista,
     * uma vez por lote (Anexo I §5.2). Vale primeiro o meio do último
     * recebimento registrado no lote — o que de fato aconteceu; enquanto ele não
     * existe, vale o meio combinado no atendimento, que o cadastro manual grava
     * em `orders.meta`. Sem nenhum dos dois, a tela mostra o travessão.
     */
    private function formaDePagamento(Order $pedido): ?string
    {
        $lote = $pedido->batch;
        $meio = null;

        if ($lote instanceof OrderBatch) {
            /** @var Payment|null $pagamento */
            $pagamento = $lote->payments->sortByDesc('created_at')->first();
            $meio = $pagamento?->method;
        }

        $meio = is_string($meio) && $meio !== '' ? $meio : $this->metaTexto($pedido, 'payment_method');

        if ($meio === '') {
            return null;
        }

        $rotulo = trans('payment.method.'.$meio, [], 'pt_BR');

        return is_string($rotulo) && $rotulo !== 'payment.method.'.$meio ? $rotulo : $meio;
    }

    /**
     * O endereço da loja do revendedor — o destino de toda remessa (regra do
     * Anexo I §5.6: a Velaro entrega na loja, nunca no consumidor).
     *
     * @return array{nome: string, linha1: string, linha2: string}|null
     */
    private function enderecoDeEntrega(Order $pedido): ?array
    {
        $lojista = $pedido->reseller;

        if (! $lojista instanceof Reseller) {
            return null;
        }

        $rua = trim((string) $lojista->street.' '.(string) $lojista->street_number);
        $bairro = trim((string) $lojista->district);

        return [
            'nome' => (string) $lojista->trade_name,
            'linha1' => trim($rua.($bairro !== '' ? ' - '.$bairro : '')),
            'linha2' => trim((string) $lojista->city.' / '.(string) $lojista->state.' - '.(string) $lojista->postal_code),
        ];
    }

    /**
     * O degrau seguinte da esteira, ou nulo quando o pedido já foi retirado.
     *
     * @return array{chave: string, rotulo: string}|null
     */
    public function proximoStatus(Order $pedido): ?array
    {
        $degrau = $this->status->degrau($pedido->operational_status);

        if ($degrau < 0 || ! isset(StatusDoPedido::CADEIA_OPERACIONAL[$degrau + 1])) {
            return null;
        }

        $chave = StatusDoPedido::CADEIA_OPERACIONAL[$degrau + 1];

        return ['chave' => $chave, 'rotulo' => $this->status->operacional($chave)['rotulo']];
    }

    // ─────────────────────────────── CADASTRO MANUAL ───────────────────────────────

    /**
     * O que o formulário do mockup 61 precisa para desenhar seus selects.
     *
     * @return array<string, mixed>
     */
    public function dadosDoFormulario(): array
    {
        return [
            'revendedores' => Reseller::query()
                ->where('status', Reseller::STATUS_APPROVED)
                ->orderBy('trade_name')
                ->get(['id', 'code', 'trade_name', 'city', 'state']),
            'variantes' => ProductVariant::query()
                ->where('is_active', true)
                ->whereHas('product', fn (Builder $produto): Builder => $produto->where('is_active', true))
                ->with(['product:id,name,price,allows_engraving,engraving_max_chars'])
                ->orderBy('sku')
                ->get(),
            'lotes' => OrderBatch::query()
                ->where('status', OrderBatch::STATUS_OPEN)
                ->with('reseller:id,trade_name')
                ->orderByDesc('cut_date')
                ->get(),
            'promocoes' => Promotion::query()
                ->where('status', Promotion::STATUS_ACTIVE)
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'type']),
            'canais' => self::CANAIS_DE_ORIGEM,
            'modosDeEntrega' => self::MODOS_DE_ENTREGA,
            'meiosDePagamento' => [
                Payment::METHOD_PIX => trans('payment.method.'.Payment::METHOD_PIX, [], 'pt_BR'),
                Payment::METHOD_BOLETO => trans('payment.method.'.Payment::METHOD_BOLETO, [], 'pt_BR'),
                Payment::METHOD_BANK_TRANSFER => trans('payment.method.'.Payment::METHOD_BANK_TRANSFER, [], 'pt_BR'),
            ],
        ];
    }

    /**
     * Cria o pedido interno — o atendimento que a Velaro registra em nome do
     * lojista (mockup 61).
     *
     * Tudo numa transação: pedido, itens, gravações, promoção e o primeiro
     * evento da timeline nascem juntos ou não nascem. Um pedido com metade dos
     * itens cobraria por peça que ele não tem.
     *
     * O pedido nasce em `registered` / `pending`: os dois status são
     * independentes (regra 2) e nenhum deles é decidido por quem digita o
     * cadastro — pagamento é baixa do financeiro e produção é a esteira.
     *
     * @param  array<string, mixed>  $dados
     */
    public function criar(array $dados, User $ator): Order
    {
        /** @var Reseller $lojista */
        $lojista = Reseller::query()->findOrFail($dados['reseller_id']);

        return DB::transaction(function () use ($dados, $ator, $lojista): Order {
            $cliente = $this->clienteFinal($lojista, $dados);

            $pedido = new Order([
                // O atendente logado é o autor do pedido interno.
                'user_id' => $ator->getKey(),
                'reseller_id' => $lojista->getKey(),
                'customer_id' => $cliente?->getKey(),
                'batch_id' => $dados['batch_id'] ?? null,
                'reference' => $dados['reference'] ?? null,
                // Espelho do scaffold; a autoridade são os dois status abaixo.
                'status' => Order::STATUS_DRAFT,
                'operational_status' => Order::OPERATIONAL_STATUS_REGISTERED,
                'payment_status' => Order::PAYMENT_STATUS_PENDING,
                'currency' => 'BRL',
                'expected_at' => $dados['expected_at'] ?? null,
                'notes' => $dados['notes'] ?? null,
                'meta' => [
                    'origin' => self::ORIGEM,
                    'origin_channel' => $dados['origin_channel'],
                    'delivery_mode' => $dados['delivery_mode'],
                    // O meio combinado no atendimento. A baixa de verdade e do
                    // lote, em `payments` — quem a registra e a tela 3.5.
                    'payment_method' => $dados['payment_method'],
                    // Prazo de produção e vencimento são atributos do
                    // atendimento, não estados do pedido: nenhuma consulta desta
                    // tela filtra por eles e o vencimento de verdade é o do lote
                    // (`order_batches.due_date`), que é quem o lojista paga.
                    'production_days' => $dados['production_days'] ?? null,
                    'due_date' => $dados['due_date'] ?? null,
                    'created_by_master' => true,
                ],
            ]);

            $pedido->save();

            $subtotal = 0.0;
            $gravacoes = 0.0;

            foreach ($dados['itens'] as $linha) {
                [$valorDaLinha, $valorDaGravacao] = $this->item($pedido, $linha);
                $subtotal += $valorDaLinha;
                $gravacoes += $valorDaGravacao;
            }

            $desconto = $this->descontoDaPromocao($pedido, $dados['promotion_id'] ?? null, $subtotal);

            $pedido->forceFill([
                'subtotal_amount' => round($subtotal, 2),
                'engraving_amount' => round($gravacoes, 2),
                // Frete incluso na remessa semanal (mockup 61, "Frete").
                'shipping_amount' => 0,
                'discount_amount' => round($desconto, 2),
                'total_amount' => round($subtotal + $gravacoes - $desconto, 2),
            ])->save();

            OrderStatusEvent::create([
                'order_id' => $pedido->getKey(),
                'scope' => OrderStatusEvent::SCOPE_OPERATIONAL,
                'from_status' => null,
                'to_status' => Order::OPERATIONAL_STATUS_REGISTERED,
                'actor_id' => $ator->getKey(),
                'note' => 'Pedido criado pela Velaro em nome do revendedor.',
            ]);

            $this->auditoria->log('velaro.order.created', $pedido, null, [
                'origin' => self::ORIGEM,
                'reseller_id' => $lojista->getKey(),
                'operational_status' => Order::OPERATIONAL_STATUS_REGISTERED,
                'payment_status' => Order::PAYMENT_STATUS_PENDING,
                'total_amount' => (string) $pedido->total_amount,
            ]);

            return $pedido->refresh();
        });
    }

    /**
     * Uma linha do pedido, com o **preço congelado** no momento da escolha.
     *
     * `order_items.unit_price` é snapshot imutável: mudar o catálogo depois não
     * reescreve o que o pedido custou. O número copiado é `products.price` — o
     * custo B2B que a Velaro cobra do lojista, que é o valor desta tela.
     *
     * @param  array{product_variant_id: int|string, quantity: int|string, engraving_text?: string|null}  $linha
     * @return array{0: float, 1: float}
     */
    private function item(Order $pedido, array $linha): array
    {
        /** @var ProductVariant $variante */
        $variante = ProductVariant::query()->with('product')->findOrFail($linha['product_variant_id']);
        $produto = $variante->product;

        $quantidade = max(1, (int) $linha['quantity']);
        $unitario = round((float) $produto->price, 2);
        $total = round($unitario * $quantidade, 2);

        $item = OrderItem::create([
            'order_id' => $pedido->getKey(),
            'product_id' => $produto->getKey(),
            'product_variant_id' => $variante->getKey(),
            'quantity' => $quantidade,
            'unit_price' => $unitario,
            'total_price' => $total,
        ]);

        $texto = trim((string) ($linha['engraving_text'] ?? ''));
        // `allows_engraving` nasceu com nome em portugues e foi renomeada pela
        // migracao que traduziu o schema; a analise estatica le as migracoes e
        // nao enxerga rename montado em laco. `getAttribute()` devolve o mesmo
        // valor do acesso por propriedade — e o mesmo contorno do Portal.
        $ligada = $texto !== '' && (bool) $produto->getAttribute('allows_engraving');
        $valorDaGravacao = $ligada ? round($this->precoDaGravacao() * $quantidade, 2) : 0.0;

        // A linha de gravação existe sempre, ligada ou desligada, para o detalhe
        // poder dizer "sem gravação" sem inferir nada da ausência do registro.
        OrderItemEngraving::create([
            'order_item_id' => $item->getKey(),
            'enabled' => $ligada,
            'text' => $ligada ? $texto : null,
            'date' => null,
            'chars' => $ligada ? mb_strlen($texto) : 0,
            'price' => $valorDaGravacao,
        ]);

        return [$total, $valorDaGravacao];
    }

    /**
     * Preço da gravação por peça, de `settings.gravacao.preco` — a mesma fonte
     * que a vitrine lê. Parametrizável de propósito: quem muda o valor é a
     * configuração, não uma constante escondida no código.
     */
    private function precoDaGravacao(): float
    {
        $preco = $this->conteudo->group(VitrineCatalogoService::GRUPO_GRAVACAO)['preco'] ?? null;

        return is_numeric($preco) ? round((float) $preco, 2) : 0.0;
    }

    /**
     * Aplica a campanha escolhida no cadastro e congela o desconto em reais.
     *
     * A campanha em si — faixas, produtos e público-alvo — é assunto da tela
     * 3.8; aqui só se lê a faixa já gravada em `promotion_rules` e se registra o
     * resultado em `order_promotions`, que é onde a auditoria procura depois.
     * Faixa aplicada é a de maior `min_amount` que o subtotal alcança.
     */
    private function descontoDaPromocao(Order $pedido, int|string|null $promocaoId, float $subtotal): float
    {
        if ($promocaoId === null || $promocaoId === '') {
            return 0.0;
        }

        /** @var Promotion $promocao */
        $promocao = Promotion::query()->with('rules')->findOrFail($promocaoId);

        /** @var PromotionRule|null $faixa */
        $faixa = $promocao->rules
            ->filter(static fn (PromotionRule $regra): bool => (float) $regra->min_amount <= $subtotal)
            ->sortByDesc(static fn (PromotionRule $regra): float => (float) $regra->min_amount)
            ->first();

        $desconto = 0.0;

        if ($faixa instanceof PromotionRule) {
            $desconto = $faixa->discount_percent !== null
                ? round($subtotal * ((float) $faixa->discount_percent / 100), 2)
                : round((float) $faixa->discount_amount, 2);
        }

        $desconto = min($desconto, $subtotal);

        OrderPromotion::create([
            'order_id' => $pedido->getKey(),
            'promotion_id' => $promocao->getKey(),
            'type' => $promocao->type,
            'discount_amount' => $desconto,
            'applied_at' => now(),
        ]);

        return $desconto;
    }

    /**
     * A ficha do cliente final na carteira **deste** lojista.
     *
     * O consumidor não tem login e não paga a Velaro: existe como pessoa
     * vinculada ao pedido (Anexo I §5.2). O documento é a identidade — o mesmo
     * cliente reaproveita a ficha, e a busca é escopada por `reseller_id`
     * porque a carteira de um lojista não enxerga a do outro.
     *
     * @param  array<string, mixed>  $dados
     */
    private function clienteFinal(Reseller $lojista, array $dados): ?Customer
    {
        $nome = trim((string) ($dados['customer_name'] ?? ''));

        if ($nome === '') {
            return null;
        }

        $documento = trim((string) ($dados['customer_document'] ?? ''));

        $atributos = [
            'reseller_id' => $lojista->getKey(),
            'name' => $nome,
            'person_type' => Customer::PERSON_TYPE_INDIVIDUAL,
            'document' => $documento !== '' ? $documento : null,
            'phone' => $dados['customer_phone'] ?? null,
            'email' => $dados['customer_email'] ?? null,
        ];

        if ($documento === '') {
            return Customer::create($atributos);
        }

        $cliente = Customer::query()
            ->where('reseller_id', $lojista->getKey())
            ->where('document', $documento)
            ->first();

        if ($cliente instanceof Customer) {
            $cliente->fill($atributos)->save();

            return $cliente;
        }

        return Customer::create($atributos);
    }

    // ─────────────────────────────── ESTEIRA E RETIRADA ───────────────────────────────

    /**
     * Move o pedido um degrau na esteira operacional (regra 1).
     *
     * Só o degrau **seguinte** da cadeia é aceito: pular de `registered` para
     * `in_transit` deixaria a timeline com buracos que nenhum evento explica, e
     * `picked_up` é terminal. O status financeiro não é tocado aqui — regra 2.
     *
     * @throws ValidationException
     */
    public function avancarStatus(Order $pedido, string $para, User $ator, ?string $nota = null): Order
    {
        $proximo = $this->proximoStatus($pedido);

        if ($proximo === null || $proximo['chave'] !== $para) {
            throw ValidationException::withMessages([
                'operational_status' => sprintf(
                    'Transição inválida: %s → %s.',
                    (string) $pedido->operational_status,
                    $para,
                ),
            ]);
        }

        return DB::transaction(function () use ($pedido, $para, $ator, $nota): Order {
            $origem = (string) $pedido->operational_status;

            $this->registrarTransicao($pedido, $origem, $para, $ator, $nota);
            $pedido->forceFill(['operational_status' => $para])->save();

            $this->auditoria->log('velaro.order.status_updated', $pedido, ['operational_status' => $origem], [
                'operational_status' => $para,
                'note' => $nota,
            ]);

            return $pedido->refresh();
        });
    }

    /**
     * Chegada do pedido na loja: carimba `arrived_at`, leva a esteira até
     * "pronto para retirada" e enfileira o aviso ao lojista e ao cliente — o
     * que o protótipo promete no card de notificações.
     */
    public function confirmarChegada(Order $pedido, User $ator, ?string $nota = null): Order
    {
        return DB::transaction(function () use ($pedido, $ator, $nota): Order {
            $origem = (string) $pedido->operational_status;

            $this->registrarTransicao(
                $pedido,
                $origem,
                Order::OPERATIONAL_STATUS_READY_FOR_PICKUP,
                $ator,
                $nota ?? 'Pedido recebido na loja do revendedor.',
            );

            $pedido->forceFill([
                'operational_status' => Order::OPERATIONAL_STATUS_READY_FOR_PICKUP,
                'arrived_at' => now(),
            ])->save();

            $this->notificarProntoParaRetirada($pedido);

            $this->auditoria->log('velaro.order.arrived', $pedido, ['operational_status' => $origem], [
                'operational_status' => Order::OPERATIONAL_STATUS_READY_FOR_PICKUP,
                'arrived_at' => (string) $pedido->arrived_at,
            ]);

            return $pedido->refresh();
        });
    }

    /**
     * Retirada de **um** pedido pelo cliente na loja (regra 3, primeira metade).
     *
     * Quem retirou fica gravado com nome e documento: é a prova da entrega ao
     * consumidor, e o Anexo I §7 pede ator e justificativa em ação sensível.
     *
     * @param  array{picked_up_by_name: string, picked_up_by_document?: string|null, picked_up_by_customer_id?: int|string|null, note?: string|null}  $dados
     */
    public function confirmarRetirada(Order $pedido, User $ator, array $dados): Order
    {
        return DB::transaction(function () use ($pedido, $ator, $dados): Order {
            $this->carimbarRetirada($pedido, $ator, $dados);

            $this->auditoria->log('velaro.order.picked_up', $pedido, null, [
                'operational_status' => Order::OPERATIONAL_STATUS_PICKED_UP,
                'picked_up_by_name' => $dados['picked_up_by_name'],
                'picked_up_by_document' => $dados['picked_up_by_document'] ?? null,
                'note' => $dados['note'] ?? null,
            ]);

            return $pedido->refresh();
        });
    }

    /**
     * Retirada do **lote inteiro** (regra 3, segunda metade).
     *
     * O lojista costuma buscar a remessa da semana de uma vez; confirmar pedido
     * a pedido nesse caso é convite a esquecer um. Carimba `order_batches` e
     * cada pedido do lote que ainda não foi retirado — os já retirados ficam
     * como estão, com a data e o portador que já tinham.
     *
     * @param  array{picked_up_by_name: string, picked_up_by_document?: string|null, note?: string|null}  $dados
     * @return int quantos pedidos foram carimbados
     */
    public function confirmarRetiradaDoLote(OrderBatch $lote, User $ator, array $dados): int
    {
        return DB::transaction(function () use ($lote, $ator, $dados): int {
            $pedidos = $lote->orders()
                ->whereNull('picked_up_at')
                ->get();

            foreach ($pedidos as $pedido) {
                $this->carimbarRetirada($pedido, $ator, $dados);
            }

            $lote->forceFill([
                'picked_up_at' => now(),
                'picked_up_by_name' => $dados['picked_up_by_name'],
                'picked_up_by_document' => $dados['picked_up_by_document'] ?? null,
            ])->save();

            $this->auditoria->log('velaro.order_batch.picked_up', $lote, null, [
                'picked_up_by_name' => $dados['picked_up_by_name'],
                'picked_up_by_document' => $dados['picked_up_by_document'] ?? null,
                'orders' => $pedidos->count(),
                'note' => $dados['note'] ?? null,
            ]);

            return $pedidos->count();
        });
    }

    /**
     * Chegada do lote inteiro na loja — a contraparte de
     * {@see confirmarChegada()} para a remessa da semana.
     *
     * @return int quantos pedidos foram carimbados
     */
    public function confirmarChegadaDoLote(OrderBatch $lote, User $ator, ?string $nota = null): int
    {
        return DB::transaction(function () use ($lote, $ator, $nota): int {
            $pedidos = $lote->orders()
                ->whereNull('arrived_at')
                ->get();

            foreach ($pedidos as $pedido) {
                $origem = (string) $pedido->operational_status;

                $this->registrarTransicao(
                    $pedido,
                    $origem,
                    Order::OPERATIONAL_STATUS_READY_FOR_PICKUP,
                    $ator,
                    $nota ?? 'Lote recebido na loja do revendedor.',
                );

                $pedido->forceFill([
                    'operational_status' => Order::OPERATIONAL_STATUS_READY_FOR_PICKUP,
                    'arrived_at' => now(),
                ])->save();

                $this->notificarProntoParaRetirada($pedido);
            }

            $lote->forceFill(['arrived_at' => now()])->save();

            $this->auditoria->log('velaro.order_batch.arrived', $lote, null, [
                'arrived_at' => (string) $lote->arrived_at,
                'orders' => $pedidos->count(),
                'note' => $nota,
            ]);

            return $pedidos->count();
        });
    }

    /**
     * O carimbo de retirada de um pedido, sem log — quem loga é o método
     * público que chamou, porque a retirada avulsa e a do lote são duas ações
     * diferentes na trilha.
     *
     * @param  array{picked_up_by_name: string, picked_up_by_document?: string|null, picked_up_by_customer_id?: int|string|null, note?: string|null}  $dados
     */
    private function carimbarRetirada(Order $pedido, User $ator, array $dados): void
    {
        $origem = (string) $pedido->operational_status;

        $this->registrarTransicao(
            $pedido,
            $origem,
            Order::OPERATIONAL_STATUS_PICKED_UP,
            $ator,
            $dados['note'] ?? 'Retirada confirmada na loja do revendedor.',
        );

        $pedido->forceFill([
            'operational_status' => Order::OPERATIONAL_STATUS_PICKED_UP,
            'picked_up_at' => now(),
            'picked_up_by_name' => $dados['picked_up_by_name'],
            'picked_up_by_document' => $dados['picked_up_by_document'] ?? null,
            'picked_up_by_customer_id' => $dados['picked_up_by_customer_id'] ?? $pedido->customer_id,
        ])->save();
    }

    /**
     * Um degrau na timeline. Transição para o mesmo status não vira evento —
     * repetir a confirmação não deve encher o histórico de linhas iguais.
     */
    private function registrarTransicao(Order $pedido, string $de, string $para, User $ator, ?string $nota): void
    {
        if ($de === $para) {
            return;
        }

        OrderStatusEvent::create([
            'order_id' => $pedido->getKey(),
            'scope' => OrderStatusEvent::SCOPE_OPERATIONAL,
            'from_status' => $de !== '' ? $de : null,
            'to_status' => $para,
            'actor_id' => $ator->getKey(),
            'note' => $nota,
        ]);
    }

    /**
     * Enfileira o aviso de "pronto para retirada" para o lojista e para o
     * cliente final, como o protótipo declara no card de notificações.
     *
     * Nasce `pending`: quem carimba `sent` é quem fala com o provedor, num job.
     * Esta tela só registra que o aviso entrou na fila.
     */
    private function notificarProntoParaRetirada(Order $pedido): void
    {
        $lojista = $pedido->reseller;

        if ($lojista instanceof Reseller && filled($lojista->email)) {
            NotificationLog::create([
                'type' => NotificationLog::TYPE_ORDER_READY,
                'channel' => NotificationLog::CHANNEL_EMAIL,
                'recipient' => (string) $lojista->email,
                'recipient_type' => NotificationLog::RECIPIENT_TYPE_RESELLER,
                'order_id' => $pedido->getKey(),
                'reseller_id' => $lojista->getKey(),
                'status' => NotificationLog::STATUS_PENDING,
            ]);
        }

        $cliente = $pedido->customer;

        if ($cliente instanceof Customer && filled($cliente->email)) {
            NotificationLog::create([
                'type' => NotificationLog::TYPE_ORDER_READY,
                'channel' => NotificationLog::CHANNEL_EMAIL,
                'recipient' => (string) $cliente->email,
                'recipient_type' => NotificationLog::RECIPIENT_TYPE_CUSTOMER,
                'order_id' => $pedido->getKey(),
                'reseller_id' => $pedido->reseller_id,
                'customer_id' => $cliente->getKey(),
                'status' => NotificationLog::STATUS_PENDING,
            ]);
        }
    }

    /**
     * Os status que cada aba conta e filtra.
     *
     * @return list<string>
     */
    private function statusDaAba(string $aba): array
    {
        return match ($aba) {
            self::ABA_AGUARDANDO_PAGAMENTO => [
                Order::PAYMENT_STATUS_PENDING,
                Order::PAYMENT_STATUS_AWAITING_CLEARANCE,
            ],
            self::ABA_EM_PRODUCAO => [
                Order::OPERATIONAL_STATUS_IN_PRODUCTION,
                Order::OPERATIONAL_STATUS_PRODUCTION_COMPLETED,
            ],
            self::ABA_EM_TRANSPORTE => [Order::OPERATIONAL_STATUS_IN_TRANSIT],
            self::ABA_CONCLUIDOS => [Order::OPERATIONAL_STATUS_PICKED_UP],
            default => [],
        };
    }

    /**
     * Leitura de uma chave de `orders.meta` como texto.
     */
    private function metaTexto(Order $pedido, string $chave): string
    {
        $meta = $pedido->meta;
        $valor = is_array($meta) ? ($meta[$chave] ?? null) : null;

        return is_scalar($valor) ? trim((string) $valor) : '';
    }
}
