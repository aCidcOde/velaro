<?php

/*
[Modulo: app/Services/Vitrine]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Carrinho de sessao da vitrine, por loja: linhas, gravacao discriminada a parte e os totais que orientam o pagamento no caixa.
*/

namespace App\Services\Vitrine;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ResellerStore;
use App\Services\Portal\ResellerPriceResolver;
use App\Services\Portal\ResellerPricingService;
use App\Services\Site\SiteContentService;
use App\Support\ResellerScope;
use App\Support\ValorPtBr;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Session;

/**
 * O carrinho da tela 2.10 — e ele é um **PDV de balcão**, não uma loja online.
 *
 * Quem opera o tablet é o vendedor da loja, com o cliente na frente. O consumidor
 * final não tem conta em lugar nenhum (ele só passa a existir como `customers` na
 * carteira do lojista depois que o pedido é fechado), então o carrinho não pode
 * morar no banco preso a um usuário: ele mora na **sessão do navegador**, e uma
 * sessão pode ter carrinho aberto em mais de uma loja ao mesmo tempo — daí a
 * chave ser por `reseller_stores.id`.
 *
 * As três regras do ambiente valem aqui como valem na vitrine:
 *
 * 1. **Zero marca Velaro.** Nenhum método público devolve `Product`: o painel sai
 *    daqui como array de texto já formatado, e o nome da peça passa pelo mesmo
 *    filtro de marca do catálogo. A exceção é {@see itensResolvidos()}, que existe
 *    para outro *service* (o que grava o pedido) e nunca para uma view.
 *
 * 2. **Preço é o B2C do revendedor.** `products.price` é o custo B2B, e ele entra
 *    aqui só como entrada do {@see ResellerPriceResolver}. O que a linha mostra —
 *    e o que vira `order_items.unit_price` — é o preço resolvido pelas regras do
 *    próprio lojista.
 *
 * 3. **Nenhum pagamento.** O carrinho totaliza e diz onde pagar. Não há gateway,
 *    Pix, cartão nem link: `payment_in_store` vira texto de tela, e o botão do
 *    rodapé só registra o pedido.
 *
 * ## Por que as ações chegam por GET
 *
 * O grupo `vitrine.` tem cinco rotas e uma só é POST — `vitrine.finalizar`. Somar
 * uma peça, mexer no stepper ou tirar uma linha não têm rota própria, então
 * chegam como parâmetro de `GET /loja/{slug}/carrinho` e o controller responde com
 * **redirect** para a URL limpa. É o padrão PRG: a mutação nunca é a resposta que
 * fica no histórico, e recarregar a página não repete a ação.
 */
final class VitrineCarrinhoService
{
    /** Somar a peça escolhida na ficha — ou aumentar a linha que já existe. */
    public const ACAO_ADICIONAR = 'adicionar';

    /** A lixeira da linha, no protótipo. */
    public const ACAO_REMOVER = 'remover';

    /** O stepper `− 1 +`: quantidade zero remove a linha. */
    public const ACAO_QUANTIDADE = 'quantidade';

    /** O par de rádios "Sim, desejo gravação" / "Não, obrigado". */
    public const ACAO_GRAVACAO = 'gravacao';

    /**
     * Vocabulário fechado das ações que a URL do carrinho aceita.
     *
     * @var list<string>
     */
    public const ACOES = [
        self::ACAO_ADICIONAR,
        self::ACAO_REMOVER,
        self::ACAO_QUANTIDADE,
        self::ACAO_GRAVACAO,
    ];

    /**
     * Tetos da sessão. A vitrine é pública e sem login: sem limite, um robô
     * enche a sessão de quem passar por ali. Vinte peças por linha e trinta
     * linhas cobrem qualquer atendimento de balcão com folga.
     */
    public const QUANTIDADE_MAXIMA = 20;

    public const LINHAS_MAXIMAS = 30;

    /** Prefixo da chave de sessão; o sufixo é o id da loja. */
    private const CHAVE_SESSAO = 'vitrine.carrinho';

    /** Chave do aviso de uma linha só que o painel mostra depois da ação. */
    public const CHAVE_AVISO = 'vitrine.aviso';

    // ⚠ Nada de cache de instância aqui.
    //
    // Uma requisição do carrinho relê as mesmas linhas várias vezes — o painel,
    // os totais, o limite da gravação, o Form Request do fechamento — e guardá-las
    // numa propriedade parece a economia óbvia. Não é: `Route::getController()`
    // guarda o controller no objeto da rota, e a rota vive na aplicação. Onde a
    // aplicação atravessa requisições (Octane, e os testes HTTP desta suíte), o
    // controller e tudo o que ele injeta atravessam junto — e este service
    // carrega **o carrinho de um visitante**. Um cache de instância aqui seria o
    // carrinho de um consumidor aparecendo no tablet do próximo.
    //
    // Foi um teste que pegou isto: a peça desativada continuava no painel porque
    // a resposta anterior tinha ficado guardada. As consultas a mais são o preço
    // de a sessão ser sempre lida agora.

    public function __construct(
        private readonly VitrineCatalogoService $catalogo,
        private readonly ResellerPricingService $precos,
        private readonly SiteContentService $conteudo,
    ) {}

    // ───────────────────────────── mutação ─────────────────────────────

    /**
     * Aplica uma ação do painel e devolve o aviso a piscar na tela, se houver.
     *
     * Ação desconhecida, peça fora do catálogo desta loja ou aro inexistente não
     * são erro de validação: a vitrine é pública e indexável, e um link velho
     * tem de abrir o carrinho, não uma página de erro. O que acontece é nada —
     * e o aviso explica.
     *
     * @param  array{acao: string|null, peca: string|null, aro: string|null, quantidade: int|null, gravacao: bool|null}  $acao
     */
    public function aplicar(ResellerStore $loja, array $acao): ?string
    {
        return match ($acao['acao']) {
            self::ACAO_ADICIONAR => $this->adicionar($loja, $acao['peca'], $acao['aro']),
            self::ACAO_REMOVER => $this->remover($loja, $acao['peca'], $acao['aro']),
            self::ACAO_QUANTIDADE => $this->ajustar($loja, $acao['peca'], $acao['aro'], $acao['quantidade'] ?? 0),
            self::ACAO_GRAVACAO => $this->gravar($loja, (bool) $acao['gravacao']),
            default => null,
        };
    }

    /**
     * Zera o carrinho da loja. Chamado quando o pedido é registrado: o próximo
     * cliente que chegar ao balcão começa do zero.
     */
    public function esvaziar(ResellerStore $loja): void
    {
        Session::forget($this->chave($loja));
    }

    /**
     * Quantas peças a sacola do topo mostra — a soma das quantidades, não o
     * número de linhas: duas alianças iguais são duas peças na sacola.
     *
     * Conta o mesmo que o painel conta, e não o que está cru na sessão: peça
     * que saiu do catálogo desta loja some da tela, e o número no alto tem de
     * sumir junto. O contador é a única leitura do carrinho nas telas 2.9 —
     * por isso ele resolve o recorte com uma consulta só, sem carregar relação
     * nem resolver preço.
     */
    public function sacola(ResellerStore $loja): int
    {
        $itens = $this->estado($loja)['itens'];

        if ($itens === []) {
            return 0;
        }

        $ids = array_values(array_unique(array_map(
            static fn (array $linha): int => $linha['produto'],
            $itens,
        )));

        /** @var list<int> $visiveis */
        $visiveis = $this->catalogo->consultaVisivel($loja)
            ->whereIn('products.id', $ids)
            ->pluck('products.id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $total = 0;

        foreach ($itens as $linha) {
            if (in_array($linha['produto'], $visiveis, true)) {
                $total += $linha['quantidade'];
            }
        }

        return $total;
    }

    public function vazio(ResellerStore $loja): bool
    {
        return $this->itensResolvidos($loja) === [];
    }

    // ───────────────────────────── painel ─────────────────────────────

    /**
     * O painel `.cart` do protótipo, inteiro e já formatado.
     *
     * ## `show_prices` não vale aqui, e é decisão, não esquecimento
     *
     * O toggle `reseller_stores.show_prices` é "mostrar preços ao cliente final"
     * e governa o **catálogo**: com ele desligado, a grade da tela 2.9 e a ficha
     * da peça dizem "Consulte na loja" e o valor não sai do
     * {@see VitrineCatalogoService}. O painel do carrinho continua mostrando
     * tudo — linha, subtotal, gravação e TOTAL — por duas razões:
     *
     * 1. A seção 5 da tela 2.10 lista as quatro linhas de "Totais" mais o TOTAL
     *    como campos obrigatórios da tela, sem condicionante. Ausência de campo
     *    ali descrito é pendência de escopo (Anexo I §9).
     * 2. Este painel é o fecho de uma venda presencial: o vendedor já cotou as
     *    peças com o cliente e vai mandá-lo ao caixa. Um pedido sem total não é
     *    pedido — é o cliente pagando um número que ninguém mostrou.
     *
     * Nada disso afrouxa a regra 2: o que aparece aqui é o **preço B2C** do
     * revendedor, resolvido pelo {@see ResellerPriceResolver}. `products.price`
     * — o custo que a Velaro cobra do lojista — não chega a este array em forma
     * nenhuma, com `show_prices` ligado ou desligado.
     *
     * @return array<string, mixed>
     */
    public function montarPainel(ResellerStore $loja): array
    {
        $itens = $this->itensResolvidos($loja);
        $valores = $this->valores($loja, $itens);
        $gravacao = $this->gravacao($loja, $itens);

        return [
            'vazio' => $itens === [],
            'pecas' => array_sum(array_map(static fn (array $item): int => $item['quantidade'], $itens)),
            'linhas' => array_map(fn (array $item): array => $this->linha($loja, $item), $itens),
            'gravacao' => $gravacao,
            'totais' => [
                'subtotal' => ValorPtBr::moeda($valores['subtotal']),
                'gravacao' => ValorPtBr::moeda($valores['gravacao']),
                'frete' => $this->rotuloDoFrete($loja, $valores['frete']),
                'desconto' => ValorPtBr::moeda($valores['desconto']),
                'total' => ValorPtBr::moeda($valores['total']),
            ],
            // Retirada e pagamento no balcão: orientação, nunca meio de cobrança.
            'retirada' => $this->catalogo->montarRetirada($loja),
            'aviso' => $this->aviso(),
            'urlFinalizar' => route('vitrine.finalizar', $loja),
            // O "X para fechar" que a seção 5 da tela 2.10 pede no cabeçalho do
            // painel. Fechar aqui é sair da tela do carrinho e voltar à vitrine:
            // o carrinho vive na sessão, não na tela, e nada se perde — o
            // vendedor reabre pela sacola do topo com as peças no lugar.
            'urlFechar' => route('vitrine.index', $loja),
        ];
    }

    /**
     * Uma linha `.line` do painel.
     *
     * @param  array{produto: Product, variante: ProductVariant|null, aro: string|null, quantidade: int, unitario: float, total: float, gravavel: bool}  $item
     * @return array<string, mixed>
     */
    private function linha(ResellerStore $loja, array $item): array
    {
        $produto = $item['produto'];
        $slug = (string) $produto->slug;
        $aro = $item['aro'];

        $endereco = fn (string $acao, ?int $quantidade = null): string => route('vitrine.carrinho', array_filter([
            'store' => $loja,
            'acao' => $acao,
            'peca' => $slug,
            'aro' => $aro,
            'quantidade' => $quantidade,
        ], static fn (mixed $valor): bool => $valor !== null));

        return [
            'nome' => $this->catalogo->rotuloPublico((string) $produto->name),
            'especificacao' => $this->catalogo->especificacaoPublica($produto, $aro),
            'aro' => $aro,
            'quantidade' => $item['quantidade'],
            'valor' => ValorPtBr::moeda($item['total']),
            'imagem' => $this->catalogo->capaPublica($produto),
            'urlProduto' => route('vitrine.produto', ['store' => $loja, 'product' => $slug]),
            'urlMais' => $item['quantidade'] < self::QUANTIDADE_MAXIMA
                ? $endereco(self::ACAO_QUANTIDADE, $item['quantidade'] + 1)
                : null,
            'urlMenos' => $endereco(self::ACAO_QUANTIDADE, $item['quantidade'] - 1),
            'urlRemover' => $endereco(self::ACAO_REMOVER),
        ];
    }

    /**
     * O bloco `.engrave`: os dois rádios, o limite de caracteres e o valor.
     *
     * O preço é sempre o de `settings` (`gravacao.preco`) — é o que a tela 2.10
     * chama de "cobrada à parte por aliança" e o que vai discriminado na linha
     * "Adicional de gravação". O limite é o **menor** entre os das peças que
     * aceitam gravação, com o `gravacao.max_chars` global como piso: um texto
     * que não cabe na aliança mais estreita do carrinho não cabe no pedido.
     *
     * @param  list<array{produto: Product, variante: ProductVariant|null, aro: string|null, quantidade: int, unitario: float, total: float, gravavel: bool}>  $itens
     * @return array{disponivel: bool, ativa: bool, pecas: int, preco: string|null, maxChars: int|null, urlSim: string, urlNao: string}
     */
    private function gravacao(ResellerStore $loja, array $itens): array
    {
        $pecas = $this->pecasGravaveis($itens);
        $preco = $this->precoDaGravacao();

        $endereco = fn (bool $ativa): string => route('vitrine.carrinho', [
            'store' => $loja,
            'acao' => self::ACAO_GRAVACAO,
            'gravacao' => $ativa ? 1 : 0,
        ]);

        return [
            'disponivel' => $pecas > 0,
            'ativa' => $pecas > 0 && $this->gravacaoAtiva($loja),
            'pecas' => $pecas,
            'preco' => $preco > 0.0 ? ValorPtBr::moeda($preco) : null,
            'maxChars' => $this->limiteDeGravacao($loja),
            'urlSim' => $endereco(true),
            'urlNao' => $endereco(false),
        ];
    }

    /**
     * O recado de uma linha que a ação anterior deixou na sessão — "peça
     * adicionada", "peça retirada". Some sozinho: é flash.
     */
    private function aviso(): ?string
    {
        $aviso = Session::get(self::CHAVE_AVISO);

        return is_string($aviso) && $aviso !== '' ? $aviso : null;
    }

    /**
     * "Retirada na loja" quando o lojista só entrega no balcão; senão o valor —
     * que hoje é sempre zero, porque a entrega é combinada com a equipe da loja
     * e não há frete cobrado pela vitrine.
     */
    private function rotuloDoFrete(ResellerStore $loja, float $frete): string
    {
        if ((bool) $loja->pickup_only) {
            return 'Retirada na loja';
        }

        return $frete > 0.0 ? ValorPtBr::moeda($frete) : 'A combinar com a loja';
    }

    // ───────────────────────────── valores ─────────────────────────────

    /**
     * As quatro linhas de "Totais" mais o TOTAL, em número.
     *
     * A identidade é fechada e é ela que o pedido grava:
     * `total = subtotal + gravação + frete − desconto`.
     *
     * `desconto` é zero enquanto a vitrine não tiver promoção do revendedor: o
     * motor de `order_promotions` é do Painel Master (tela 3.8) e nenhuma regra
     * dele chega até aqui. A linha existe na tela porque o protótipo a mostra, e
     * porque zerá-la é diferente de escondê-la.
     *
     * @param  list<array{produto: Product, variante: ProductVariant|null, aro: string|null, quantidade: int, unitario: float, total: float, gravavel: bool}>|null  $itens
     * @return array{subtotal: float, gravacao: float, frete: float, desconto: float, total: float}
     */
    public function valores(ResellerStore $loja, ?array $itens = null): array
    {
        $itens ??= $this->itensResolvidos($loja);

        $subtotal = 0.0;

        foreach ($itens as $item) {
            $subtotal += $item['total'];
        }

        $gravacao = $this->gravacaoAtiva($loja)
            ? round($this->precoDaGravacao() * $this->pecasGravaveis($itens), 2)
            : 0.0;

        $subtotal = round($subtotal, 2);
        $frete = 0.0;
        $desconto = 0.0;

        return [
            'subtotal' => $subtotal,
            'gravacao' => $gravacao,
            'frete' => $frete,
            'desconto' => $desconto,
            'total' => round($subtotal + $gravacao + $frete - $desconto, 2),
        ];
    }

    /**
     * Preço da gravação por peça, de `settings.gravacao.preco`.
     *
     * Parametrizável de propósito (regra 3 da tela 2.10): quem muda o valor é a
     * configuração, não uma constante escondida no código.
     */
    public function precoDaGravacao(): float
    {
        $preco = $this->conteudo->group(VitrineCatalogoService::GRUPO_GRAVACAO)['preco'] ?? null;

        return is_numeric($preco) ? round((float) $preco, 2) : 0.0;
    }

    /**
     * Limite de caracteres da gravação deste carrinho.
     *
     * O piso é o `gravacao.max_chars` global; cada peça que declara um limite
     * menor puxa o número para baixo, porque o texto é um só para o pedido
     * inteiro e o que não cabe na aliança mais estreita não cabe no pedido.
     * Nulo é "sem limite declarado", não "sem gravação" — quem responde isso é
     * {@see pecasComGravacao()}.
     */
    public function limiteDeGravacao(ResellerStore $loja): ?int
    {
        $global = $this->conteudo->group(VitrineCatalogoService::GRUPO_GRAVACAO)['max_chars'] ?? null;
        $limite = is_numeric($global) && (int) $global > 0 ? (int) $global : null;

        foreach ($this->itensResolvidos($loja) as $item) {
            if (! $item['gravavel']) {
                continue;
            }

            $daPeca = (int) $item['produto']->getAttribute('engraving_max_chars');

            if ($daPeca > 0 && ($limite === null || $daPeca < $limite)) {
                $limite = $daPeca;
            }
        }

        return $limite;
    }

    /**
     * Quantas peças do carrinho aceitam gravação — é sobre elas que o adicional
     * é cobrado, uma vez por aliança.
     */
    public function pecasComGravacao(ResellerStore $loja): int
    {
        return $this->pecasGravaveis($this->itensResolvidos($loja));
    }

    /**
     * A gravação foi pedida **e** há peça que a aceita. É esta a condição que
     * torna o texto obrigatório no formulário e que faz o adicional entrar na
     * conta — pedir gravação de um carrinho só com acessórios não cobra nada.
     */
    public function gravacaoAplicavel(ResellerStore $loja): bool
    {
        return $this->gravacaoAtiva($loja) && $this->pecasComGravacao($loja) > 0;
    }

    public function gravacaoAtiva(ResellerStore $loja): bool
    {
        return $this->estado($loja)['gravacao'];
    }

    /**
     * @param  list<array{produto: Product, variante: ProductVariant|null, aro: string|null, quantidade: int, unitario: float, total: float, gravavel: bool}>  $itens
     */
    private function pecasGravaveis(array $itens): int
    {
        $pecas = 0;

        foreach ($itens as $item) {
            if ($item['gravavel']) {
                $pecas += $item['quantidade'];
            }
        }

        return $pecas;
    }

    // ───────────────────────────── resolução ─────────────────────────────

    /**
     * As linhas da sessão reabertas contra o catálogo visível **desta loja**,
     * com o preço B2C já resolvido.
     *
     * Este é o único método público que devolve `Product`, e ele existe para o
     * service que grava o pedido — nunca para uma view. Quem monta tela usa
     * {@see montarPainel()}: `products.price` é o custo B2B e não sai daqui.
     *
     * Peça que saiu do catálogo (foi desativada, ou o lojista tirou da curadoria)
     * some do carrinho em silêncio: ela não existe mais nesta loja, e insistir
     * nela colocaria no pedido algo que a vitrine não vende.
     *
     * @return list<array{produto: Product, variante: ProductVariant|null, aro: string|null, quantidade: int, unitario: float, total: float, gravavel: bool}>
     */
    public function itensResolvidos(ResellerStore $loja): array
    {
        $estado = $this->estado($loja);

        if ($estado['itens'] === []) {
            return [];
        }

        $ids = array_values(array_unique(array_map(
            static fn (array $linha): int => $linha['produto'],
            $estado['itens'],
        )));

        /** @var EloquentCollection<int, Product> $pecas */
        $pecas = $this->catalogo->consultaVisivel($loja)->whereIn('products.id', $ids)->get();
        $porId = $pecas->keyBy(static fn (Product $peca): int => (int) $peca->getKey());

        $resolvedor = $this->precos->resolvedor(ResellerScope::for($loja->reseller));
        $linhas = [];

        foreach ($estado['itens'] as $linha) {
            $peca = $porId->get($linha['produto']);

            if (! $peca instanceof Product) {
                continue;
            }

            $variante = $this->variante($peca, $linha['variante']);
            $unitario = round((float) $resolvedor->resolve($peca)['price'], 2);

            $linhas[] = [
                'produto' => $peca,
                'variante' => $variante,
                'aro' => $variante instanceof ProductVariant ? (string) $variante->getAttribute('ring_size') : null,
                'quantidade' => $linha['quantidade'],
                'unitario' => $unitario,
                'total' => round($unitario * $linha['quantidade'], 2),
                'gravavel' => (bool) $peca->getAttribute('allows_engraving'),
            ];
        }

        return $linhas;
    }

    private function variante(Product $peca, ?int $id): ?ProductVariant
    {
        if ($id === null) {
            return null;
        }

        $variante = $peca->variants->first(
            static fn (ProductVariant $item): bool => (int) $item->getKey() === $id,
        );

        return $variante instanceof ProductVariant ? $variante : null;
    }

    // ───────────────────────────── ações ─────────────────────────────

    private function adicionar(ResellerStore $loja, ?string $slug, ?string $aro): string
    {
        $peca = $this->peca($loja, $slug);

        if (! $peca instanceof Product) {
            return 'Esta peça não está mais disponível nesta loja.';
        }

        $variante = $this->varianteDoAro($peca, $aro);
        $estado = $this->estado($loja);
        $chave = $this->indiceDaLinha($estado['itens'], (int) $peca->getKey(), $variante);

        if ($chave === null && count($estado['itens']) >= self::LINHAS_MAXIMAS) {
            return 'O carrinho já está com o número máximo de peças deste atendimento.';
        }

        if ($chave === null) {
            $estado['itens'][] = [
                'produto' => (int) $peca->getKey(),
                'variante' => $variante,
                'quantidade' => 1,
            ];
        } else {
            $estado['itens'][$chave]['quantidade'] = min(
                self::QUANTIDADE_MAXIMA,
                $estado['itens'][$chave]['quantidade'] + 1,
            );
        }

        $this->guardar($loja, $estado);

        return $this->catalogo->rotuloPublico((string) $peca->name).' foi adicionada ao carrinho.';
    }

    private function remover(ResellerStore $loja, ?string $slug, ?string $aro): ?string
    {
        return $this->ajustar($loja, $slug, $aro, 0);
    }

    private function ajustar(ResellerStore $loja, ?string $slug, ?string $aro, int $quantidade): ?string
    {
        $peca = $this->peca($loja, $slug);

        if (! $peca instanceof Product) {
            return null;
        }

        $variante = $this->varianteDoAro($peca, $aro);
        $estado = $this->estado($loja);
        $indice = $this->indiceDaLinha($estado['itens'], (int) $peca->getKey(), $variante);

        if ($indice === null) {
            return null;
        }

        $quantidade = min(self::QUANTIDADE_MAXIMA, max(0, $quantidade));

        if ($quantidade === 0) {
            unset($estado['itens'][$indice]);
            $estado['itens'] = array_values($estado['itens']);
            $this->guardar($loja, $estado);

            return $this->catalogo->rotuloPublico((string) $peca->name).' foi retirada do carrinho.';
        }

        $estado['itens'][$indice]['quantidade'] = $quantidade;
        $this->guardar($loja, $estado);

        return null;
    }

    private function gravar(ResellerStore $loja, bool $ativa): string
    {
        $estado = $this->estado($loja);
        $estado['gravacao'] = $ativa;
        $this->guardar($loja, $estado);

        return $ativa
            ? 'Gravação incluída no pedido — o texto é escrito abaixo, com o cliente.'
            : 'Gravação retirada do pedido.';
    }

    /**
     * A peça pedida, dentro do catálogo visível desta loja. Fora dele não existe.
     */
    private function peca(ResellerStore $loja, ?string $slug): ?Product
    {
        if ($slug === null || $slug === '') {
            return null;
        }

        $peca = $this->catalogo->consultaVisivel($loja)->where('products.slug', $slug)->first();

        return $peca instanceof Product ? $peca : null;
    }

    /**
     * O id do aro escolhido. Aro que a peça não tem — ou que foi desativado —
     * vira ausência de aro, e a equipe da loja confirma o tamanho no balcão.
     */
    private function varianteDoAro(Product $peca, ?string $aro): ?int
    {
        if ($aro === null || $aro === '') {
            return null;
        }

        $variante = $peca->variants
            ->where('is_active', true)
            ->first(static fn (ProductVariant $item): bool => (string) $item->getAttribute('ring_size') === $aro);

        return $variante instanceof ProductVariant ? (int) $variante->getKey() : null;
    }

    /**
     * Peça e aro juntos são a identidade da linha: a mesma aliança em dois aros
     * são duas linhas, como o protótipo mostra.
     *
     * @param  list<array{produto: int, variante: int|null, quantidade: int}>  $itens
     */
    private function indiceDaLinha(array $itens, int $produto, ?int $variante): ?int
    {
        foreach ($itens as $indice => $linha) {
            if ($linha['produto'] === $produto && $linha['variante'] === $variante) {
                return $indice;
            }
        }

        return null;
    }

    // ───────────────────────────── sessão ─────────────────────────────

    /**
     * O que está gravado na sessão, já saneado.
     *
     * Sessão é dado de fora: pode ter sido escrita por uma versão anterior desta
     * tela, ou ter envelhecido no cookie do tablet. Nada sai daqui sem forma —
     * o resto do service confia neste formato.
     *
     * @return array{itens: list<array{produto: int, variante: int|null, quantidade: int}>, gravacao: bool}
     */
    private function estado(ResellerStore $loja): array
    {
        $bruto = Session::get($this->chave($loja));

        if (! is_array($bruto)) {
            return ['itens' => [], 'gravacao' => false];
        }

        $itens = [];
        $guardados = $bruto['itens'] ?? null;

        if (is_array($guardados)) {
            foreach ($guardados as $linha) {
                if (! is_array($linha) || ! isset($linha['produto']) || ! is_numeric($linha['produto'])) {
                    continue;
                }

                $variante = $linha['variante'] ?? null;
                $quantidade = $linha['quantidade'] ?? 1;

                $itens[] = [
                    'produto' => (int) $linha['produto'],
                    'variante' => is_numeric($variante) ? (int) $variante : null,
                    'quantidade' => is_numeric($quantidade)
                        ? min(self::QUANTIDADE_MAXIMA, max(1, (int) $quantidade))
                        : 1,
                ];

                if (count($itens) >= self::LINHAS_MAXIMAS) {
                    break;
                }
            }
        }

        return [
            'itens' => $itens,
            'gravacao' => (bool) ($bruto['gravacao'] ?? false),
        ];
    }

    /**
     * @param  array{itens: list<array{produto: int, variante: int|null, quantidade: int}>, gravacao: bool}  $estado
     */
    private function guardar(ResellerStore $loja, array $estado): void
    {
        if ($estado['itens'] === []) {
            // Carrinho vazio não precisa ocupar sessão — e sem linha nenhuma a
            // escolha de gravação não tem sobre o que valer.
            Session::forget($this->chave($loja));

            return;
        }

        Session::put($this->chave($loja), $estado);
    }

    /**
     * A chave é por loja: o mesmo tablet pode ter carrinho aberto em duas
     * vitrines, e misturar as duas montaria um pedido com peça de outra loja.
     */
    private function chave(ResellerStore $loja): string
    {
        return self::CHAVE_SESSAO.'.'.$loja->getKey();
    }
}
