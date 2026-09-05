<?php

/*
[Modulo: app/Services/Vitrine]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Registra o pedido do balcao a partir do carrinho da vitrine e monta o comprovante, sem processar pagamento nenhum.
*/

namespace App\Services\Vitrine;

use App\Models\Customer;
use App\Models\CustomerConsent;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemEngraving;
use App\Models\OrderStatusEvent;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ResellerStore;
use App\Services\Portal\ClientesService;
use App\Services\Portal\StatusDoPedido;
use App\Support\ResellerScope;
use App\Support\ValorPtBr;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * O fecho do atendimento presencial: o carrinho da tela 2.10 vira pedido, e o
 * pedido vira o comprovante do balcão.
 *
 * ## O que este service **não** faz
 *
 * Não cobra. Não emite Pix, não abre link de pagamento, não fala com gateway
 * nenhum e não recebe do consumidor em nome da Velaro. O pedido nasce em
 * `draft`, com `payment_status = pending`, e o pagamento acontece no caixa do
 * lojista — é a regra 2 da tela 2.10 e a razão de o botão do carrinho dizer
 * exatamente isso. `payment_confirmed` é decisão de quem opera o caixa, na
 * retaguarda; a vitrine não tem como saber que o dinheiro entrou, então não
 * finge saber.
 *
 * ## O cliente final
 *
 * Ele não tem login e não terá: passa a existir como `customers` na **carteira
 * do lojista** no momento em que o pedido é registrado, e acompanha o resto pelo
 * WhatsApp. Por isso o vínculo é `customers.reseller_id`, e o CPF é a identidade
 * que decide entre criar e reaproveitar a ficha.
 *
 * ## Por que o comprovante é mais fechado do que a rota sugere
 *
 * `/loja/{slug}/pedido/{public_number}` é público e o número é curto. Duas
 * proteções, por motivos diferentes:
 *
 * 1. **Só pedido nascido nesta vitrine abre aqui.** O mesmo revendedor tem
 *    pedidos B2B na mesma tabela, e neles `order_items.unit_price` é o **custo
 *    Velaro**. Aceitar qualquer pedido do lojista entregaria esse custo ao
 *    consumidor — violação da regra 2 pela porta dos fundos. O marcador é
 *    `orders.meta`, gravado no registro.
 * 2. **PII sai mascarada.** CPF, telefone e e-mail do cliente final aparecem
 *    reduzidos, como em qualquer comprovante: o comprovante confirma o pedido a
 *    quem tem o número, não entrega a ficha do cliente a quem varrer números.
 *
 *    É por isso que o botão "Enviar por WhatsApp" do protótipo não virou um
 *    `wa.me/<telefone do cliente>`: o número inteiro no `href` devolveria, em
 *    texto puro, exatamente o dado que a máscara acabou de esconder. O contato
 *    que a tela oferece é o **da loja**, que já é público.
 *
 * Fora dessas duas, a resposta é 404 — nunca 403, pela mesma razão documentada
 * em {@see ResellerScope}.
 */
final class VitrinePedidoService
{
    /** Marcador de `orders.meta` que diz que o pedido nasceu no balcão da vitrine. */
    public const ORIGEM = 'vitrine';

    /**
     * Chave de `customers.contact_source` para quem chegou pela loja do lojista.
     * O vocabulário é o mesmo da tela 2.3 — ver
     * {@see ClientesService::ORIGENS_DO_CONTATO}.
     */
    public const ORIGEM_DO_CONTATO = 'vitrine';

    /**
     * Os degraus que o consumidor vê no comprovante, com o nome que ele entende.
     *
     * É um recorte da cadeia operacional completa
     * ({@see StatusDoPedido::CADEIA_OPERACIONAL}), não uma segunda cadeia: quem
     * decide o que é passado, presente e futuro é a posição do status do pedido
     * naquela lista. "Pagamento confirmado" e "Produção finalizada" ficam de
     * fora porque são degraus de retaguarda, sem tradução no balcão.
     *
     * @var array<string, string>
     */
    private const ETAPAS = [
        Order::OPERATIONAL_STATUS_REGISTERED => 'Pedido registrado',
        Order::OPERATIONAL_STATUS_IN_PRODUCTION => 'Em produção',
        Order::OPERATIONAL_STATUS_IN_TRANSIT => 'A caminho da loja',
        Order::OPERATIONAL_STATUS_READY_FOR_PICKUP => 'Pronto para retirada',
    ];

    public function __construct(
        private readonly VitrineCarrinhoService $carrinho,
        private readonly VitrineCatalogoService $catalogo,
        private readonly StatusDoPedido $status,
    ) {}

    // ───────────────────────────── registro ─────────────────────────────

    /**
     * Grava o pedido do balcão e devolve o registro pronto para o comprovante.
     *
     * Tudo numa transação: pedido, itens, gravações, cliente e consentimento
     * nascem juntos ou não nascem. Um pedido com metade dos itens seria pior do
     * que nenhum — o balcão cobraria por peça que o pedido não tem.
     *
     * @param  array{nome: string, whatsapp: string, documento: string, email: string|null, dataCasamento: string|null, aceiteMarketing: bool, gravacaoTexto: string|null, gravacaoData: string|null, observacao: string|null}  $dados
     */
    public function registrar(ResellerStore $loja, array $dados): Order
    {
        $itens = $this->carrinho->itensResolvidos($loja);
        $valores = $this->carrinho->valores($loja, $itens);
        $gravar = $this->carrinho->gravacaoAplicavel($loja);

        $pedido = DB::transaction(function () use ($loja, $dados, $itens, $valores, $gravar): Order {
            $cliente = $this->cliente($loja, $dados);

            $pedido = new Order([
                'user_id' => null,
                'reseller_id' => $loja->reseller_id,
                'customer_id' => $cliente->getKey(),
                // Espelho do scaffold. A autoridade são os dois status abaixo,
                // que são independentes entre si (decisão 1.2 do banco).
                'status' => Order::STATUS_DRAFT,
                'operational_status' => Order::OPERATIONAL_STATUS_REGISTERED,
                // Nunca `paid`: a vitrine não recebe. Quem confirma é o caixa.
                'payment_status' => Order::PAYMENT_STATUS_PENDING,
                'subtotal_amount' => $valores['subtotal'],
                'engraving_amount' => $valores['gravacao'],
                'shipping_amount' => $valores['frete'],
                'discount_amount' => $valores['desconto'],
                'total_amount' => $valores['total'],
                'currency' => 'BRL',
                'expected_at' => $this->previsao($itens),
                'notes' => $dados['observacao'],
                'meta' => [
                    'origin' => self::ORIGEM,
                    'store_id' => (int) $loja->getKey(),
                    'store_slug' => (string) $loja->slug,
                ],
            ]);

            $pedido->save();

            foreach ($itens as $item) {
                $this->item($pedido, $item, $gravar, $dados);
            }

            OrderStatusEvent::create([
                'order_id' => $pedido->getKey(),
                'scope' => OrderStatusEvent::SCOPE_OPERATIONAL,
                'from_status' => null,
                'to_status' => Order::OPERATIONAL_STATUS_REGISTERED,
                // Sem ator: quem operou o tablet não tem login nesta tela.
                'actor_id' => null,
                'note' => 'Pedido registrado no balcão da loja.',
            ]);

            return $pedido;
        });

        // Só depois de a transação fechar: se a gravação do pedido falhar, o
        // carrinho continua na tela para o vendedor tentar de novo.
        $this->carrinho->esvaziar($loja);

        return $pedido;
    }

    /**
     * Uma linha do pedido, com o **preço B2C congelado** no momento da escolha.
     *
     * `unit_price` é o preço ao consumidor resolvido pelas regras do lojista, e
     * não o custo do catálogo: é o número que o caixa vai cobrar, e ele não pode
     * mudar depois porque o lojista mexeu na margem.
     *
     * A linha de gravação existe sempre, ligada ou desligada, para o comprovante
     * poder dizer "sem gravação" sem inferir nada da ausência do registro — é o
     * mesmo formato que o seed dos pedidos grava.
     *
     * @param  array{produto: Product, variante: ProductVariant|null, aro: string|null, quantidade: int, unitario: float, total: float, gravavel: bool}  $item
     * @param  array{nome: string, whatsapp: string, documento: string, email: string|null, dataCasamento: string|null, aceiteMarketing: bool, gravacaoTexto: string|null, gravacaoData: string|null, observacao: string|null}  $dados
     */
    private function item(Order $pedido, array $item, bool $gravar, array $dados): void
    {
        $linha = OrderItem::create([
            'order_id' => $pedido->getKey(),
            'product_id' => $item['produto']->getKey(),
            'product_variant_id' => $item['variante']?->getKey(),
            'quantity' => $item['quantidade'],
            'unit_price' => $item['unitario'],
            'total_price' => $item['total'],
        ]);

        $ligada = $gravar && $item['gravavel'];
        $texto = $ligada ? (string) $dados['gravacaoTexto'] : null;

        OrderItemEngraving::create([
            'order_item_id' => $linha->getKey(),
            'enabled' => $ligada,
            'text' => $texto,
            'date' => $ligada ? $dados['gravacaoData'] : null,
            'chars' => $texto === null ? 0 : mb_strlen($texto),
            // Cobrada por aliança: o valor da linha é o unitário vezes a
            // quantidade dela, e a soma das linhas é `orders.engraving_amount`.
            'price' => $ligada
                ? round($this->carrinho->precoDaGravacao() * $item['quantidade'], 2)
                : 0,
        ]);
    }

    /**
     * A ficha do cliente final na carteira **deste** lojista.
     *
     * O CPF é a identidade: o mesmo cliente que voltou ao balcão reaproveita a
     * ficha, com os dados atualizados pelo que o vendedor acabou de conferir com
     * ele. A busca é escopada por `reseller_id` — a carteira de um lojista não
     * enxerga a do outro, e um CPF conhecido de outra loja não vira cliente
     * desta sem passar pelo balcão dela.
     *
     * @param  array{nome: string, whatsapp: string, documento: string, email: string|null, dataCasamento: string|null, aceiteMarketing: bool, gravacaoTexto: string|null, gravacaoData: string|null, observacao: string|null}  $dados
     */
    private function cliente(ResellerStore $loja, array $dados): Customer
    {
        $cliente = Customer::query()
            ->where('reseller_id', $loja->reseller_id)
            ->where('document', $dados['documento'])
            ->first();

        $atributos = [
            'reseller_id' => $loja->reseller_id,
            'name' => $dados['nome'],
            'person_type' => Customer::PERSON_TYPE_INDIVIDUAL,
            'document' => $dados['documento'],
            'phone' => $dados['whatsapp'],
        ];

        // E-mail e data do casamento são opcionais na tela; em cima de uma ficha
        // que já existe, o campo em branco é "não perguntei agora", não "apague".
        if ($dados['email'] !== null) {
            $atributos['email'] = $dados['email'];
        }

        if ($dados['dataCasamento'] !== null) {
            $atributos['wedding_date'] = $dados['dataCasamento'];
        }

        if ($cliente instanceof Customer) {
            $cliente->fill($atributos)->save();
        } else {
            $atributos['contact_source'] = self::ORIGEM_DO_CONTATO;
            $cliente = Customer::create($atributos);
        }

        $this->consentir($cliente, $loja, $dados['aceiteMarketing']);

        return $cliente;
    }

    /**
     * Consentimento de marketing, com data e evidência (LGPD).
     *
     * Só o "sim" é gravado. Caixa desmarcada é ausência de consentimento nova, e
     * não revogação de um consentimento anterior: quem revoga é o titular, e a
     * revogação tem canal próprio. O aviso de retirada não depende disto — ele é
     * transacional.
     */
    private function consentir(Customer $cliente, ResellerStore $loja, bool $aceitou): void
    {
        if (! $aceitou) {
            return;
        }

        CustomerConsent::updateOrCreate(
            ['customer_id' => $cliente->getKey(), 'type' => CustomerConsent::TYPE_MARKETING],
            [
                'granted' => true,
                'granted_at' => Carbon::now(),
                'revoked_at' => null,
                'channel' => self::ORIGEM,
                'evidence' => 'Aceite marcado no atendimento presencial da loja '.$loja->name.'.',
            ],
        );
    }

    /**
     * Previsão de chegada à loja: o maior prazo de produção entre as peças,
     * contado em dias úteis.
     *
     * Peça nenhuma declarando prazo deixa a data em branco, e o comprovante
     * simplesmente não mostra a linha — melhor do que uma promessa inventada.
     *
     * @param  list<array{produto: Product, variante: ProductVariant|null, aro: string|null, quantidade: int, unitario: float, total: float, gravavel: bool}>  $itens
     */
    private function previsao(array $itens): ?Carbon
    {
        $prazo = 0;

        foreach ($itens as $item) {
            $dias = (int) $item['produto']->getAttribute('delivery_days');
            $prazo = max($prazo, $dias);
        }

        return $prazo > 0 ? Carbon::today()->addWeekdays($prazo) : null;
    }

    // ───────────────────────────── comprovante ─────────────────────────────

    /**
     * Dados de `GET /loja/{slug}/pedido/{public_number}`.
     *
     * @return array<string, mixed>
     */
    public function montarConfirmacao(ResellerStore $loja, Order $pedido): array
    {
        $this->assertDaLoja($loja, $pedido);

        $pedido->loadMissing([
            'customer',
            'items.product.material',
            'items.product.finish',
            'items.product.category',
            'items.product.images',
            'items.variant',
            'items.engraving',
        ]);

        $itens = $this->itens($pedido);

        return [
            'loja' => $loja,
            'abas' => $this->catalogo->montarAbas($loja),
            'pedido' => [
                'numero' => (string) $pedido->public_number,
                'registradoEm' => $this->dataHora($pedido->created_at),
                'pecas' => array_sum(array_map(static fn (array $item): int => $item['quantidade'], $itens)),
                'operacional' => $this->status->operacional($pedido->operational_status)['rotulo'],
                'pagamento' => $this->rotuloDoPagamento($pedido),
                'pagoNoCaixa' => (string) $pedido->payment_status === Order::PAYMENT_STATUS_PAID,
            ],
            'etapas' => $this->etapas($pedido),
            'cliente' => $this->identificacao($pedido),
            'itens' => $itens,
            'gravacao' => $this->resumoDaGravacao($pedido),
            'valores' => $this->valores($loja, $pedido),
            'prazo' => $this->prazo($loja, $pedido),
            'retirada' => $this->catalogo->montarRetirada($loja),
            'contato' => $this->catalogo->montarContato($loja),
            'urlNovoAtendimento' => route('vitrine.index', $loja),
        ];
    }

    /**
     * O comprovante desta loja só abre pedido **desta** loja, e só pedido
     * nascido no balcão dela.
     *
     * As duas condições são a mesma resposta: 404. Pedido de outro lojista e
     * pedido B2B do próprio lojista não existem nesta vitrine — o segundo, aliás,
     * carrega o custo Velaro nos itens, e é justamente o que a regra 2 proíbe
     * mostrar ao consumidor.
     */
    private function assertDaLoja(ResellerStore $loja, Order $pedido): void
    {
        // `getAttribute()` porque `orders.meta` é nulável: o pedido B2B do
        // lojista não tem marcador nenhum, e é exatamente o caso que precisa
        // cair fora daqui.
        $meta = $pedido->getAttribute('meta');
        $meta = is_array($meta) ? $meta : [];
        $daLoja = $meta['store_id'] ?? null;

        $confere = (int) $pedido->reseller_id === (int) $loja->reseller_id
            && ($meta['origin'] ?? null) === self::ORIGEM
            && is_numeric($daLoja)
            && (int) $daLoja === (int) $loja->getKey();

        if (! $confere) {
            throw (new ModelNotFoundException)->setModel(
                Order::class,
                [(string) $pedido->public_number],
            );
        }
    }

    /**
     * As linhas do comprovante. `unit_price` já é o preço ao consumidor: o custo
     * B2B não entra em conta nenhuma aqui.
     *
     * @return list<array{nome: string, especificacao: string, quantidade: int, valor: string, imagem: array{src: string, alt: string}|null}>
     */
    private function itens(Order $pedido): array
    {
        $linhas = [];

        foreach ($pedido->items as $item) {
            $produto = $item->product;

            if (! $produto instanceof Product) {
                continue;
            }

            $aro = $item->variant instanceof ProductVariant
                ? (string) $item->variant->getAttribute('ring_size')
                : null;

            $linhas[] = [
                'nome' => $this->catalogo->rotuloPublico((string) $produto->name),
                'especificacao' => $this->catalogo->especificacaoPublica($produto, $aro),
                'quantidade' => (int) $item->quantity,
                'valor' => ValorPtBr::moeda((float) $item->total_price),
                'imagem' => $this->catalogo->capaPublica($produto),
            ];
        }

        return $linhas;
    }

    /**
     * A gravação do pedido, resumida numa linha só — texto, data, quantas peças
     * levam e quanto custou ao todo.
     *
     * @return array{texto: string, data: string|null, pecas: int, valor: string}|null
     */
    private function resumoDaGravacao(Order $pedido): ?array
    {
        $texto = null;
        $data = null;
        $pecas = 0;
        $valor = 0.0;

        foreach ($pedido->items as $item) {
            $gravacao = $item->engraving;

            if (! $gravacao instanceof OrderItemEngraving || ! (bool) $gravacao->enabled) {
                continue;
            }

            $texto ??= (string) $gravacao->text;
            $data ??= $gravacao->date instanceof Carbon ? $gravacao->date->format('d/m/Y') : null;
            $pecas += (int) $item->quantity;
            $valor += (float) $gravacao->price;
        }

        if ($texto === null || $texto === '') {
            return null;
        }

        return [
            'texto' => $texto,
            'data' => $data,
            'pecas' => $pecas,
            'valor' => ValorPtBr::moeda(round($valor, 2)),
        ];
    }

    /**
     * As quatro linhas de valor e o total, direto de `orders` — os mesmos
     * números que o carrinho mostrou, congelados.
     *
     * `show_prices` não é consultado aqui, pelo mesmo motivo de
     * {@see VitrineCarrinhoService::montarPainel()}: o toggle esconde o preço no
     * **catálogo**, não no comprovante de uma compra já fechada. Um comprovante
     * sem valor não comprova nada, e o cliente vai ao caixa pagar justamente
     * este número. O que está aqui é o preço B2C congelado em `orders`; o custo
     * B2B não entra em conta nenhuma desta classe.
     *
     * @return array{subtotal: string, gravacao: string, frete: string, desconto: string, total: string, pagamento: string}
     */
    private function valores(ResellerStore $loja, Order $pedido): array
    {
        return [
            'subtotal' => ValorPtBr::moeda((float) $pedido->subtotal_amount),
            'gravacao' => ValorPtBr::moeda((float) $pedido->engraving_amount),
            'frete' => (bool) $loja->pickup_only
                ? 'Retirada na loja'
                : ValorPtBr::moeda((float) $pedido->shipping_amount),
            'desconto' => ValorPtBr::moeda((float) $pedido->discount_amount),
            'total' => ValorPtBr::moeda((float) $pedido->total_amount),
            'pagamento' => $this->rotuloDoPagamento($pedido),
        ];
    }

    /**
     * A vitrine não recebe: enquanto o pagamento não for confirmado na
     * retaguarda, o comprovante diz onde ele acontece.
     */
    private function rotuloDoPagamento(Order $pedido): string
    {
        return (string) $pedido->payment_status === Order::PAYMENT_STATUS_PAID
            ? 'Recebido no caixa da loja'
            : 'A pagar no caixa da loja';
    }

    /**
     * A linha do tempo do comprovante.
     *
     * @return list<array{rotulo: string, estado: string}>
     */
    private function etapas(Order $pedido): array
    {
        $cadeia = StatusDoPedido::CADEIA_OPERACIONAL;
        $atual = array_search((string) $pedido->operational_status, $cadeia, true);
        $atual = is_int($atual) ? $atual : 0;

        $etapas = [];

        foreach (self::ETAPAS as $status => $rotulo) {
            $posicao = array_search($status, $cadeia, true);
            $posicao = is_int($posicao) ? $posicao : 0;

            $etapas[] = [
                'rotulo' => $rotulo,
                'estado' => match (true) {
                    $posicao < $atual => 'is-done',
                    $posicao === $atual => 'is-on',
                    default => '',
                },
            ];
        }

        return $etapas;
    }

    /**
     * A identificação do cliente final, mascarada.
     *
     * @return array{nome: string, whatsapp: string|null, documento: string|null, email: string|null, dataCasamento: string|null}
     */
    private function identificacao(Order $pedido): array
    {
        $cliente = $pedido->customer;

        if (! $cliente instanceof Customer) {
            return ['nome' => '—', 'whatsapp' => null, 'documento' => null, 'email' => null, 'dataCasamento' => null];
        }

        // Coluna renomeada na migration de tradução do schema: o leitor de
        // migrations do Larastan não a acompanha como propriedade.
        $casamento = $cliente->getAttribute('wedding_date');

        return [
            'nome' => (string) $cliente->name,
            'whatsapp' => $this->telefoneMascarado($cliente->phone),
            'documento' => $this->documentoMascarado($cliente->document),
            'email' => $this->emailMascarado($cliente->email),
            'dataCasamento' => $casamento instanceof Carbon ? $casamento->format('d/m/Y') : null,
        ];
    }

    /**
     * Prazo, previsão e onde retirar.
     *
     * @return list<array{rotulo: string, valor: string}>
     */
    private function prazo(ResellerStore $loja, Order $pedido): array
    {
        $previsao = $pedido->getAttribute('expected_at');
        $endereco = trim((string) $loja->getAttribute('address'));

        $linhas = [
            'Previsão de chegada à loja' => $previsao instanceof Carbon ? $previsao->format('d/m/Y') : '',
            'Local de retirada' => $endereco,
            'Retirada' => 'No balcão, com documento',
        ];

        $prazo = [];

        foreach ($linhas as $rotulo => $valor) {
            if ($valor !== '') {
                $prazo[] = ['rotulo' => $rotulo, 'valor' => $valor];
            }
        }

        return $prazo;
    }

    // ───────────────────────────── texto ─────────────────────────────

    private function dataHora(mixed $valor): string
    {
        return $valor instanceof Carbon ? $valor->format('d/m/Y \à\s H:i') : '—';
    }

    /**
     * `123.456.789-00` vira `***.456.789-**`.
     */
    private function documentoMascarado(?string $documento): ?string
    {
        $digitos = (string) preg_replace('/\D+/', '', (string) $documento);

        if (mb_strlen($digitos) !== 11) {
            return $documento === null || trim($documento) === '' ? null : '***';
        }

        return '***.'.mb_substr($digitos, 3, 3).'.'.mb_substr($digitos, 6, 3).'-**';
    }

    /**
     * `(11) 98765-4321` vira `(11) *****-4321`: o DDD e os quatro últimos bastam
     * para o cliente reconhecer o próprio número.
     */
    private function telefoneMascarado(?string $telefone): ?string
    {
        $digitos = (string) preg_replace('/\D+/', '', (string) $telefone);

        if (mb_strlen($digitos) < 8) {
            return $telefone === null || trim($telefone) === '' ? null : '***';
        }

        $ddd = mb_strlen($digitos) > 9 ? '('.mb_substr($digitos, 0, 2).') ' : '';

        return $ddd.'*****-'.mb_substr($digitos, -4);
    }

    /**
     * `maria.silva@email.com` vira `ma***@email.com`.
     */
    private function emailMascarado(?string $email): ?string
    {
        $email = trim((string) $email);

        if ($email === '' || ! str_contains($email, '@')) {
            return $email === '' ? null : '***';
        }

        [$usuario, $dominio] = explode('@', $email, 2);

        return mb_substr($usuario, 0, 2).'***@'.$dominio;
    }
}
