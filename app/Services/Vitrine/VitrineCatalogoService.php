<?php

/*
[Modulo: app/Services/Vitrine]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Monta a vitrine white label do lojista: catalogo curado, preco B2C resolvido e ficha da peca — sem custo B2B e sem marca Velaro.
*/

namespace App\Services\Vitrine;

use App\Models\Category;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ResellerStore;
use App\Services\Portal\ResellerPriceResolver;
use App\Services\Portal\ResellerPricingService;
use App\Services\Site\SiteContentService;
use App\Support\ResellerScope;
use App\Support\ValorPtBr;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * O **único ambiente público e sem login** do sistema, e o único em que quem
 * está do outro lado da tela não é lojista nem operador: é o consumidor final,
 * na loja física do revendedor.
 *
 * Três regras mandam aqui (telas 2.9 e 2.10, seção 3), e as três são estruturais
 * neste service — não avisos na view:
 *
 * 1. **Zero marca Velaro.** Nada que sai daqui nomeia a fábrica. O que a tela
 *    mostra vem de `reseller_stores` (nome, slogan, cores, contato, banner) e do
 *    catálogo, que é descrito por material, acabamento e categoria — nunca por
 *    fornecedor. Vazamento de marca é pendência de escopo (Anexo I §9).
 *
 * 2. **Preço é o B2C do revendedor.** `products.price` é o custo que a Velaro
 *    cobra do lojista, e ele **não pode chegar à view em forma nenhuma** — nem
 *    como atributo de model, nem em `data-*`, nem em JSON. O preço ao consumidor
 *    sai do {@see ResellerPriceResolver}, reaproveitado do portal para que a
 *    cascata (regra por produto > por coleção > global > padrão) seja a mesma
 *    que o lojista configurou e a mesma que a prévia da tela 2.6 mostra.
 *
 *    Como o resolvedor precisa do custo para fazer a conta, a proteção não pode
 *    ser "não selecionar a coluna", como faz o catálogo público do site: aqui a
 *    fronteira é o **tipo de retorno**. Todo montador de tela devolve `array` de
 *    texto já formatado; nenhum devolve `Product`. A única saída de model é
 *    {@see consultaVisivel()}, que entrega a **consulta** para os outros services
 *    do ambiente — carrinho e comprovante — e nunca para uma view.
 *
 * 3. **Nenhum pagamento.** A vitrine não processa Pix, cartão nem link, e não
 *    recebe do consumidor. O que ela faz é totalizar e orientar o pagamento no
 *    caixa do lojista — por isso `pickup_only` e `payment_in_store` viram texto
 *    de tela, e não meio de cobrança.
 *
 * ## O que é "o catálogo desta loja"
 *
 * O catálogo é da Velaro e é o mesmo para todo lojista; o que muda de loja para
 * loja é **o recorte** e **o preço**. O recorte é a curadoria do lojista:
 * `reseller_store_products` diz quais peças ele expõe e `reseller_store_categories`
 * quais seções aparecem na navegação. As duas listas são filtros independentes e
 * ambas valem — quem escolheu categorias escolheu esconder o resto, e quem
 * escolheu produtos escolheu a mesma coisa. Lista vazia é "não escolhi": a loja
 * mostra o catálogo ativo inteiro em vez de uma vitrine vazia.
 *
 * @phpstan-type CartaoDaVitrine array{nome: string, slug: string, especificacao: string, aro: string|null, preco: string|null, imagem: array{src: string, alt: string}|null, favorito: bool, url: string}
 */
class VitrineCatalogoService
{
    /**
     * Rótulo da aba que não filtra nada — o primeiro item da navegação, como o
     * protótipo escreve.
     */
    public const ABA_TODOS = 'Todos os produtos';

    /**
     * O cookie que identifica o navegador do visitante, chave de `favorites`.
     *
     * O consumidor final **não tem conta**: ele existe como `customers` na
     * carteira do lojista, e só depois de comprar. Por isso o coração dos cards
     * é preso a `favorites.visitor_token`, e não a um usuário.
     *
     * Hoje a vitrine só **lê** este token: as cinco rotas do grupo `vitrine.`
     * não têm endpoint de favoritar, então não há por onde gravar nem por onde
     * emitir o cookie. O nome mora aqui para que a rota que vier depois use o
     * mesmo, em vez de inventar um segundo.
     */
    public const COOKIE_VISITANTE = 'vitrine_visitante';

    /** Grade 4×3 do protótipo: doze peças por página. */
    private const POR_PAGINA = 12;

    /** Quantas peças a faixa "Você também pode gostar" mostra (quatro no protótipo). */
    private const RELACIONADOS = 4;

    /**
     * Em quantas vezes a ficha simula a parcela ("ou 3x de R$ 88,33").
     *
     * É **simulação**, não cobrança: a vitrine não processa pagamento (regra 3),
     * e o texto ao lado diz que o parcelamento é acertado no caixa da loja.
     */
    private const PARCELAS = 3;

    /**
     * Grupo de `settings` que parametriza a gravação (`gravacao.*`).
     *
     * Público porque o carrinho lê o mesmo par — `max_chars` e `preco` — para
     * discriminar a gravação à parte (regra 3 da tela 2.10). Um segundo literal
     * `'gravacao'` do outro lado seria a mesma configuração com dois donos.
     */
    public const GRUPO_GRAVACAO = 'gravacao';

    // ⚠ Aqui havia um cache de instância das categorias visíveis, sob a premissa
    // de que "o service é resolvido por requisição". A premissa é falsa:
    // `Route::getController()` guarda o controller no objeto da rota, e a rota
    // vive na aplicação — onde a aplicação atravessa requisições (Octane, e os
    // testes HTTP desta suíte), o controller e tudo o que ele injeta atravessam
    // junto. O cache não era per-request: era per-processo, e servia navegação
    // velha depois que o lojista mexesse nas categorias da loja dele.
    //
    // A economia era de uma consulta por requisição. Não paga o preço.

    public function __construct(
        private readonly ResellerPricingService $precos,
        private readonly SiteContentService $conteudo,
    ) {}

    /**
     * Loja não publicada **não existe** para o consumidor final.
     *
     * O binding de `{store:slug}` é o implícito do Laravel e entrega qualquer
     * linha de `reseller_stores`, inclusive a de quem ainda está montando a loja
     * ou teve a vitrine desligada. Aqui ela some — com 404, nunca 403: 403
     * responderia "existe, mas está fechada", e confirmaria a um concorrente que
     * o slug está tomado e que aquele lojista opera na plataforma. A mesma
     * exceção do binding implícito vale para "não existe" e "não está no ar".
     *
     * É público: o lote do carrinho chama daqui, para que as cinco rotas do
     * grupo `vitrine.` tenham uma porta só.
     */
    public function assertPublicada(ResellerStore $loja): void
    {
        $publicada = (bool) $loja->getAttribute('is_active')
            && $loja->getAttribute('published_at') !== null;

        if (! $publicada) {
            throw (new ModelNotFoundException)->setModel(
                ResellerStore::class,
                [(string) $loja->getAttribute('slug')],
            );
        }
    }

    /**
     * Dados da vitrine (`/loja/{slug}`).
     *
     * @param  array{categoria: string|null, visitante: string|null}  $filtros
     * @return array<string, mixed>
     */
    public function montarIndice(ResellerStore $loja, array $filtros): array
    {
        $categorias = $this->categoriasVisiveis($loja);
        $aba = $this->abaAtual($categorias, $filtros['categoria']);

        $produtos = $this->consultaVisivel($loja)
            ->when($aba !== null, fn (Builder $consulta): Builder => $consulta->whereHas(
                'category',
                static fn (Builder $categoria): Builder => $categoria->where('slug', $aba),
            ))
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        /** @var list<Product> $itens */
        $itens = $produtos->items();
        $cartoes = $this->cartoes($loja, $itens, $filtros['visitante']);

        return [
            'loja' => $loja,
            'banner' => $this->banner($loja),
            'abas' => $this->abas($categorias),
            'aba' => $aba,
            'titulo' => $this->tituloDaGrade($categorias, $aba),
            'paginacao' => $this->paginacao($produtos),
            'cartoes' => $cartoes,
            'contato' => $this->contato($loja),
            'retirada' => $this->retirada($loja),
        ];
    }

    /**
     * Dados da ficha da peça (`/loja/{slug}/produto/{slug}`).
     *
     * Peça fora do catálogo visível **daquela loja** é 404, e não uma ficha
     * vazia: para o consumidor, o que o lojista não expõe não existe na loja
     * dele. Inclui a peça inativa e a que ficou fora da curadoria.
     *
     * @param  array{visitante: string|null}  $filtros
     * @return array<string, mixed>
     */
    public function montarProduto(ResellerStore $loja, Product $produto, array $filtros): array
    {
        /** @var Product $peca */
        $peca = $this->consultaVisivel($loja)
            ->whereKey($produto->getKey())
            ->firstOrFail();

        $resolvedor = $this->resolvedor($loja);
        $preco = $this->preco($loja, $peca, $resolvedor);

        return [
            'loja' => $loja,
            'abas' => $this->abas($this->categoriasVisiveis($loja)),
            'aba' => $this->textoOuNulo($peca->category?->slug),
            'produto' => [
                'nome' => $this->rotuloSemFornecedor($this->texto($peca->name)),
                'referencia' => $this->referencia($peca),
                'descricao' => $this->textoSemFornecedor($this->textoOuNulo($peca->description)),
            ],
            'trilha' => $this->trilha($loja, $peca),
            'imagens' => $this->imagens($peca),
            'preco' => $preco,
            'parcelamento' => $this->parcelamento($loja, $peca, $resolvedor),
            'ficha' => $this->fichaTecnica($peca),
            'aros' => $this->arosComAcao($loja, $peca),
            'urlAdicionar' => $this->urlDeAdicionar($loja, $peca, $this->primeiroAroDisponivel($peca)),
            'gravacao' => $this->gravacao($peca),
            'retirada' => $this->retirada($loja),
            'contato' => $this->contato($loja),
            'favorito' => $this->favoritos($loja, [(int) $peca->getKey()], $filtros['visitante']) !== [],
            'relacionados' => $this->cartoes($loja, $this->relacionados($loja, $peca)->all(), $filtros['visitante']),
            'urlCarrinho' => route('vitrine.carrinho', $loja),
        ];
    }

    /**
     * A navegação de páginas da grade, em números e URLs — não o paginador.
     *
     * O objeto do Laravel carrega a coleção consultada dentro de si, e essa
     * coleção é de `Product`, com `products.price` junto: bastaria um
     * `@json($produtos)` no Blade para o custo B2B aparecer no HTML da loja.
     * A view recebe só o que precisa para desenhar a barra — é a mesma
     * fronteira dos cartões, e vale pelo mesmo motivo.
     *
     * @param  LengthAwarePaginator<int, Product>  $consulta
     * @return array{total: int, de: int, ate: int, atual: int, ultima: int, anterior: string|null, proxima: string|null, paginas: list<array{numero: int, url: string, atual: bool}>}
     */
    private function paginacao(LengthAwarePaginator $consulta): array
    {
        $atual = $consulta->currentPage();
        $ultima = $consulta->lastPage();

        // A janela do protótipo: duas páginas para cada lado da atual.
        $paginas = [];

        foreach ($consulta->getUrlRange(max(1, $atual - 2), min($ultima, $atual + 2)) as $numero => $url) {
            $paginas[] = [
                'numero' => (int) $numero,
                'url' => (string) $url,
                'atual' => (int) $numero === $atual,
            ];
        }

        return [
            'total' => $consulta->total(),
            'de' => (int) $consulta->firstItem(),
            'ate' => (int) $consulta->lastItem(),
            'atual' => $atual,
            'ultima' => $ultima,
            'anterior' => $consulta->onFirstPage() ? null : $consulta->previousPageUrl(),
            'proxima' => $consulta->hasMorePages() ? $consulta->nextPageUrl() : null,
            'paginas' => $paginas,
        ];
    }

    // ───────────────────────────── catálogo ─────────────────────────────

    /**
     * O catálogo que **esta loja** expõe.
     *
     * Devolve a consulta, e não o resultado, de propósito: o model traz `price`
     * junto — o custo B2B — porque o resolvedor de preço precisa dele para fazer
     * a conta. Quem chama é outro *service* do ambiente (o carrinho reabre por
     * aqui as peças que estão na sessão, para que uma peça tirada da curadoria
     * suma do pedido também); a view continua recebendo só array de texto, como
     * a nota da classe manda.
     *
     * @return Builder<Product>
     */
    public function consultaVisivel(ResellerStore $loja): Builder
    {
        // `variants.stockItems` entra já na listagem: o card diz o aro disponível
        // e, sem o eager load, a grade faria duas consultas por peça.
        //
        // O slug não é enfeite: a ficha da vitrine é `/produto/{product:slug}`, e
        // peça sem slug não tem endereço na loja — o card levaria a lugar nenhum.
        // É a mesma condição do catálogo público do site.
        $consulta = Product::query()
            ->where('is_active', true)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->with(['material', 'finish', 'category', 'images', 'variants.stockItems']);

        $produtos = $this->produtosCurados($loja);

        if ($produtos !== []) {
            // Destaque primeiro, depois a ordem que o lojista arrastou na tela.
            $consulta->whereIn('products.id', $produtos)
                ->orderByRaw($this->ordemDaCuradoria($produtos));
        }

        // Só a escolha **gravada** recorta a grade. A lista derivada, que existe
        // para as abas quando o lojista não escolheu nada, não pode virar filtro:
        // ela nasce das categorias que têm peça, e usá-la aqui esconderia da
        // vitrine justamente as peças sem categoria — que continuam sendo
        // catálogo ativo, e o lojista não pediu para tirá-las.
        $categorias = $this->categoriasEscolhidas($loja);

        if ($categorias !== []) {
            $consulta->whereIn('category_id', $categorias);
        }

        return $consulta->orderBy('products.id');
    }

    /**
     * Os ids que `reseller_store_products` seleciona, na ordem da vitrine:
     * destaque na frente, depois `position`, e o id desempata.
     *
     * @return list<int>
     */
    private function produtosCurados(ResellerStore $loja): array
    {
        /** @var list<int> $ids */
        $ids = $loja->storeProducts()
            ->orderByDesc('is_featured')
            ->orderBy('position')
            ->orderBy('id')
            ->pluck('product_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        return $ids;
    }

    /**
     * `ORDER BY FIELD(...)` portátil: o MySQL tem `FIELD()`, o SQLite dos testes
     * não, e um `CASE` explícito ordena igual nos dois. Os ids já são inteiros
     * (o `pluck` os converte), então não há string de fora da aplicação entrando
     * na expressão.
     *
     * @param  list<int>  $ids
     */
    private function ordemDaCuradoria(array $ids): string
    {
        $ramos = [];

        foreach (array_values($ids) as $posicao => $id) {
            $ramos[] = 'WHEN '.$id.' THEN '.$posicao;
        }

        return '(CASE products.id '.implode(' ', $ramos).' ELSE '.count($ids).' END)';
    }

    /**
     * As categorias que o lojista **escolheu** exibir, na ordem dele.
     *
     * Lista vazia é "não escolhi", e é o que separa esta leitura da de
     * {@see categoriasVisiveis()}: só a escolha explícita recorta o catálogo.
     *
     * @return list<int>
     */
    private function categoriasEscolhidas(ResellerStore $loja): array
    {
        /** @var list<int> $escolhidas */
        $escolhidas = $loja->storeCategories()
            ->orderBy('position')
            ->orderBy('id')
            ->pluck('category_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        return $escolhidas;
    }

    /**
     * As categorias que a loja mostra na navegação.
     *
     * Sem escolha gravada, a navegação nasce do próprio catálogo: entram as
     * categorias ativas que têm peça ativa, para que nenhuma aba leve a uma
     * grade vazia. Essa lista derivada serve **só para navegar** — quem recorta
     * a grade é a escolha do lojista, e mais nada.
     *
     * @return EloquentCollection<int, Category>
     */
    private function categoriasVisiveis(ResellerStore $loja): EloquentCollection
    {
        $escolhidas = $this->categoriasEscolhidas($loja);

        if ($escolhidas === []) {
            /** @var EloquentCollection<int, Category> $todas */
            $todas = Category::query()
                ->where('is_active', true)
                ->whereHas('products', static fn (Builder $produto): Builder => $produto->where('is_active', true))
                ->orderBy('position')
                ->orderBy('name')
                ->get();

            return $this->semMarcaDoFornecedor($todas);
        }

        /** @var EloquentCollection<int, Category> $categorias */
        $categorias = Category::query()
            ->whereIn('id', $escolhidas)
            ->where('is_active', true)
            ->get()
            ->sortBy(static fn (Category $categoria): int => (int) array_search(
                (int) $categoria->getKey(),
                $escolhidas,
                true,
            ))
            ->values();

        return $this->semMarcaDoFornecedor($categorias);
    }

    /**
     * Tira da navegação a categoria que **nomeia o fornecedor** — no nome ou no
     * slug.
     *
     * O nome já saía limpo: `rotuloSemFornecedor()` apaga o termo do rótulo, e
     * "Velaro Signature" vira a aba "Signature". O slug não passa por filtro
     * nenhum, e não pode passar: ele é a chave da rota. O resultado era uma aba
     * de texto limpo apontando para `?categoria=velaro-signature` — a marca do
     * fornecedor na barra de endereço do consumidor final, que é exatamente o
     * que a regra 1 das telas 2.9 e 2.10 proíbe. A migalha da ficha repetia o
     * mesmo endereço.
     *
     * Some a **aba**, não o catálogo: o recorte da grade é
     * {@see categoriasEscolhidas()}, e estas peças continuam aparecendo em
     * "Todos os produtos". Uma seção batizada com o nome da fábrica não tem
     * lugar no menu de uma loja white label; as joias dela têm.
     *
     * @param  EloquentCollection<int, Category>  $categorias
     * @return EloquentCollection<int, Category>
     */
    private function semMarcaDoFornecedor(EloquentCollection $categorias): EloquentCollection
    {
        $marcas = $this->marcasDoFornecedor();

        if ($marcas === []) {
            return $categorias;
        }

        /** @var EloquentCollection<int, Category> $limpas */
        $limpas = $categorias
            ->reject(fn (Category $categoria): bool => $this->citaFornecedor($this->texto($categoria->name), $marcas)
                || $this->citaFornecedor($this->texto($categoria->slug), $marcas))
            ->values();

        return $limpas;
    }

    /**
     * Abas da navegação no formato que o layout consome.
     *
     * @param  EloquentCollection<int, Category>  $categorias
     * @return list<array{rotulo: string, slug: string|null}>
     */
    private function abas(EloquentCollection $categorias): array
    {
        $abas = [['rotulo' => self::ABA_TODOS, 'slug' => null]];

        foreach ($categorias as $categoria) {
            $slug = $this->texto($categoria->slug);

            if ($slug !== '') {
                $abas[] = ['rotulo' => $this->rotuloSemFornecedor($this->texto($categoria->name)), 'slug' => $slug];
            }
        }

        return $abas;
    }

    /**
     * A aba pedida na URL, se ela existir entre as visíveis.
     *
     * Slug desconhecido cai em "Todos os produtos" em vez de 404: a vitrine é
     * pública e um link velho tem de abrir a loja, não uma página de erro.
     *
     * @param  EloquentCollection<int, Category>  $categorias
     */
    private function abaAtual(EloquentCollection $categorias, ?string $pedida): ?string
    {
        if ($pedida === null) {
            return null;
        }

        $existe = $categorias->contains(
            fn (Category $categoria): bool => $this->texto($categoria->slug) === $pedida,
        );

        return $existe ? $pedida : null;
    }

    /**
     * Título da seção: "Todos os produtos" ou o nome da categoria aberta.
     *
     * @param  EloquentCollection<int, Category>  $categorias
     */
    private function tituloDaGrade(EloquentCollection $categorias, ?string $aba): string
    {
        if ($aba === null) {
            return self::ABA_TODOS;
        }

        $categoria = $categorias->first(
            fn (Category $item): bool => $this->texto($item->slug) === $aba,
        );

        return $categoria instanceof Category ? $this->texto($categoria->name) : self::ABA_TODOS;
    }

    // ───────────────────────────── preço B2C ─────────────────────────────

    /**
     * Resolvedor do lojista **dono desta vitrine**.
     *
     * O escopo é montado a partir da loja, e não do usuário autenticado: aqui
     * não há usuário autenticado nenhum. `ResellerScope::for()` amarra o escopo
     * ao revendedor da loja aberta, então as regras lidas são as dele e de mais
     * ninguém — a margem de um lojista continua invisível para os outros.
     */
    private function resolvedor(ResellerStore $loja): ResellerPriceResolver
    {
        return $this->precos->resolvedor(ResellerScope::for($loja->reseller));
    }

    /**
     * Preço ao consumidor, já formatado — ou nulo quando o lojista desligou
     * `show_prices` e prefere que a equipe dele informe o valor no balcão.
     *
     * Só o campo `price` do resolvedor é lido. `cost`, `margin` e `markup` são a
     * conta do lojista e não existem para o consumidor.
     */
    private function preco(ResellerStore $loja, Product $produto, ResellerPriceResolver $resolvedor): ?string
    {
        if (! (bool) $loja->show_prices) {
            return null;
        }

        return ValorPtBr::moeda($resolvedor->resolve($produto)['price']);
    }

    /**
     * "3x de R$ 88,33" — simulação de parcela da ficha, nunca uma cobrança.
     */
    private function parcelamento(ResellerStore $loja, Product $produto, ResellerPriceResolver $resolvedor): ?string
    {
        if (! (bool) $loja->show_prices) {
            return null;
        }

        $preco = (float) $resolvedor->resolve($produto)['price'];

        if ($preco <= 0.0) {
            return null;
        }

        return self::PARCELAS.'x de '.ValorPtBr::moeda(round($preco / self::PARCELAS, 2));
    }

    // ───────────────────────────── cartões ─────────────────────────────

    /**
     * @param  array<int, Product>  $produtos
     * @return list<CartaoDaVitrine>
     */
    private function cartoes(ResellerStore $loja, array $produtos, ?string $visitante): array
    {
        if ($produtos === []) {
            return [];
        }

        $resolvedor = $this->resolvedor($loja);
        $favoritos = $this->favoritos(
            $loja,
            array_map(static fn (Product $produto): int => (int) $produto->getKey(), $produtos),
            $visitante,
        );

        return array_values(array_map(
            fn (Product $produto): array => $this->cartao($loja, $produto, $resolvedor, $favoritos),
            $produtos,
        ));
    }

    /**
     * Card `.prod` da grade, já resolvido: a view não faz conta nem conhece o
     * model — e, por construção, não há caminho daqui até o custo B2B.
     *
     * @param  list<int>  $favoritos
     * @return CartaoDaVitrine
     */
    private function cartao(
        ResellerStore $loja,
        Product $produto,
        ResellerPriceResolver $resolvedor,
        array $favoritos,
    ): array {
        $capa = $this->imagens($produto)[0] ?? null;
        $slug = $this->texto($produto->slug);

        return [
            'nome' => $this->rotuloSemFornecedor($this->texto($produto->name)),
            'slug' => $slug,
            'especificacao' => $this->especificacao($produto),
            'aro' => $this->aroDeVitrine($produto),
            'preco' => $this->preco($loja, $produto, $resolvedor),
            'imagem' => $capa,
            'favorito' => in_array((int) $produto->getKey(), $favoritos, true),
            'url' => route('vitrine.produto', ['store' => $loja, 'product' => $slug]),
        ];
    }

    /**
     * Peças da mesma categoria, fora a que está aberta — e sempre dentro do
     * catálogo visível desta loja.
     *
     * @return EloquentCollection<int, Product>
     */
    private function relacionados(ResellerStore $loja, Product $produto): EloquentCollection
    {
        $categoria = $produto->category_id;

        /** @var EloquentCollection<int, Product> $relacionados */
        $relacionados = $this->consultaVisivel($loja)
            ->whereKeyNot($produto->getKey())
            ->when($categoria !== null, static fn (Builder $consulta): Builder => $consulta->where('category_id', $categoria))
            ->limit(self::RELACIONADOS)
            ->get();

        return $relacionados;
    }

    // ───────── o que o carrinho e o comprovante reaproveitam daqui ─────────
    //
    // A peça é descrita do mesmo jeito nas quatro telas do ambiente: card da
    // grade, ficha, linha do carrinho e item do comprovante. Se cada tela
    // montasse o próprio texto, a que fosse escrita por último seria a que
    // esqueceria de tirar a marca do fornecedor.

    /**
     * As abas da navegação desta loja, para as telas que não passam por
     * {@see montarIndice()} — o comprovante do pedido, por exemplo.
     *
     * Sem elas o casco cairia no menu de exemplo do protótipo, com categorias
     * que aquela loja pode nem expor.
     *
     * @return list<array{rotulo: string, slug: string|null}>
     */
    public function montarAbas(ResellerStore $loja): array
    {
        return $this->abas($this->categoriasVisiveis($loja));
    }

    /**
     * Rótulo curto do catálogo, sem a marca do fornecedor (regra 1).
     */
    public function rotuloPublico(string $rotulo): string
    {
        return $this->rotuloSemFornecedor($rotulo);
    }

    /**
     * `Ouro 18k · Anel · Polido · Aro 18` — a linha de descrição da peça, com o
     * aro no fim quando ele já foi escolhido (carrinho e comprovante).
     */
    public function especificacaoPublica(Product $produto, ?string $aro = null): string
    {
        $especificacao = $this->especificacao($produto);

        if ($aro === null || $aro === '') {
            return $especificacao;
        }

        return $especificacao === '' ? 'Aro '.$aro : $especificacao.' · Aro '.$aro;
    }

    /**
     * A foto de capa da peça, ou nulo quando ela não tem imagem gravada.
     *
     * @return array{src: string, alt: string}|null
     */
    public function capaPublica(Product $produto): ?array
    {
        return $this->imagens($produto)[0] ?? null;
    }

    // ───────────────────────────── ficha da peça ─────────────────────────────

    /**
     * Linha do card: `Ouro 18k · Anel · Polido`, como o protótipo escreve.
     */
    private function especificacao(Product $produto): string
    {
        return collect([
            $produto->material?->name,
            $produto->category?->name,
            $produto->finish?->name,
        ])
            ->map(fn (mixed $parte): string => $this->rotuloSemFornecedor($this->texto($parte)))
            ->filter(static fn (string $parte): bool => $parte !== '')
            ->implode(' · ');
    }

    /**
     * Subtítulo da ficha: `Ref. ALTD-6MM · Alianças`.
     */
    private function referencia(Product $produto): string
    {
        return collect(['Ref. '.$this->texto($produto->sku), $produto->category?->name])
            ->map(fn (mixed $parte): string => $this->rotuloSemFornecedor($this->texto($parte)))
            ->filter(static fn (string $parte): bool => $parte !== '' && $parte !== 'Ref.')
            ->implode(' · ');
    }

    /**
     * Migalha da ficha, já com as URLs desta loja.
     *
     * @return list<array{rotulo: string, url: string|null}>
     */
    private function trilha(ResellerStore $loja, Product $produto): array
    {
        $trilha = [['rotulo' => self::ABA_TODOS, 'url' => route('vitrine.index', $loja)]];

        $nome = $this->texto($produto->category?->name);
        $categoria = $this->rotuloSemFornecedor($nome);
        $slug = $this->texto($produto->category?->slug);

        // Mesma regra da navegação: o degrau da categoria só entra quando nem o
        // nome nem o slug nomeiam a fábrica. Sem esta guarda a migalha reporia,
        // no `href`, o `?categoria=velaro-…` que a aba deixou de mostrar.
        $marcas = $this->marcasDoFornecedor();
        $daFabrica = $this->citaFornecedor($nome, $marcas) || $this->citaFornecedor($slug, $marcas);

        if ($categoria !== '' && $slug !== '' && ! $daFabrica) {
            $trilha[] = [
                'rotulo' => $categoria,
                'url' => route('vitrine.index', [$loja, 'categoria' => $slug]),
            ];
        }

        $trilha[] = ['rotulo' => $this->rotuloSemFornecedor($this->texto($produto->name)), 'url' => null];

        return $trilha;
    }

    /**
     * Ficha técnica da peça, na ordem do protótipo. Linha sem valor não entra —
     * "Largura: —" não informa nada ao consumidor.
     *
     * @return list<array{rotulo: string, valor: string}>
     */
    private function fichaTecnica(Product $produto): array
    {
        $largura = $produto->getAttribute('width_mm');
        $prazo = $produto->getAttribute('delivery_days');

        $linhas = [
            'Material' => $this->rotuloSemFornecedor($this->texto($produto->material?->name)),
            'Acabamento' => $this->rotuloSemFornecedor($this->texto($produto->finish?->name)),
            'Largura' => is_numeric($largura) ? ValorPtBr::numero((float) $largura).'mm' : '',
            'Formato' => $this->texto($produto->getAttribute('shape')),
            'Prazo de produção' => is_numeric($prazo) && (int) $prazo > 0
                ? 'Até '.(int) $prazo.' dias úteis'
                : '',
        ];

        $ficha = [];

        foreach ($linhas as $rotulo => $valor) {
            if ($valor !== '') {
                $ficha[] = ['rotulo' => $rotulo, 'valor' => $valor];
            }
        }

        return $ficha;
    }

    /**
     * Disponibilidade por aro, lida de `stock_items.available`.
     *
     * A vitrine **só lê** o cofre: `stock_items` é da operação da Velaro e nada
     * nesta tela escreve saldo. Aro sem saldo continua aparecendo, marcado como
     * indisponível — o protótipo mostra o chip riscado, e sumir com ele faria o
     * consumidor achar que a peça não existe naquele tamanho.
     *
     * @return list<array{aro: string, disponivel: bool}>
     */
    private function aros(Product $produto): array
    {
        /** @var list<array{aro: string, disponivel: bool}> $aros */
        $aros = $produto->variants
            ->where('is_active', true)
            ->sortBy(static fn (ProductVariant $variante): int => (int) $variante->getAttribute('ring_size'))
            ->map(static fn (ProductVariant $variante): array => [
                'aro' => (string) $variante->getAttribute('ring_size'),
                // `available` já é o saldo livre (`on_hand` menos `reserved`).
                'disponivel' => (int) $variante->stockItems->sum('available') > 0,
            ])
            ->values()
            ->all();

        return $aros;
    }

    /**
     * Os mesmos chips de aro, com o endereço que soma a peça daquele tamanho ao
     * carrinho da loja.
     *
     * O chip é um **link**, não um botão de formulário: a ficha não tem `<form>`
     * nenhum, porque a única ação dela é escolher o tamanho e seguir para o
     * balcão. Aro sem saldo continua na tela, riscado e sem endereço — o prazo
     * de produção é conversa de balcão, e some-lo faria o consumidor achar que a
     * peça não existe naquele tamanho.
     *
     * @return list<array{aro: string, disponivel: bool, url: string|null}>
     */
    private function arosComAcao(ResellerStore $loja, Product $produto): array
    {
        return array_map(
            fn (array $aro): array => [
                'aro' => $aro['aro'],
                'disponivel' => $aro['disponivel'],
                'url' => $aro['disponivel'] ? $this->urlDeAdicionar($loja, $produto, $aro['aro']) : null,
            ],
            $this->aros($produto),
        );
    }

    /**
     * "Adicionar ao carrinho": um GET para `vitrine.carrinho`, que soma a peça e
     * responde com redirect para a URL limpa do painel.
     *
     * Não é checkout e não é cobrança (regra 3): o que este endereço faz é
     * montar o pedido do balcão, que será pago no caixa da loja.
     */
    private function urlDeAdicionar(ResellerStore $loja, Product $produto, ?string $aro): string
    {
        return route('vitrine.carrinho', array_filter([
            'store' => $loja,
            'acao' => VitrineCarrinhoService::ACAO_ADICIONAR,
            'peca' => $this->texto($produto->slug),
            'aro' => $aro,
        ], static fn (mixed $valor): bool => $valor !== null));
    }

    /**
     * O aro que o botão principal da ficha leva: o menor com saldo. Sem nenhum
     * disponível a peça vai sem aro, e o tamanho é confirmado no balcão.
     */
    private function primeiroAroDisponivel(Product $produto): ?string
    {
        foreach ($this->aros($produto) as $aro) {
            if ($aro['disponivel']) {
                return $aro['aro'];
            }
        }

        return null;
    }

    /**
     * O "Aro: 18" do card — o menor aro com saldo, ou nulo quando a grade da
     * peça não foi carregada nem há aro disponível.
     */
    private function aroDeVitrine(Product $produto): ?string
    {
        if (! $produto->relationLoaded('variants')) {
            return null;
        }

        return $this->primeiroAroDisponivel($produto);
    }

    /**
     * Gravação: o que a peça permite e o que `settings` parametriza.
     *
     * O limite de caracteres é o do produto quando ele tem um; senão vale o
     * `gravacao.max_chars` global. O preço é sempre o de `settings` — é o valor
     * cobrado do consumidor pela gravação, discriminado à parte no carrinho
     * (regra 3 da tela 2.10).
     *
     * @return array{permite: bool, maxChars: int|null, preco: string|null}
     */
    private function gravacao(Product $produto): array
    {
        // `getAttribute()` e não a propriedade: `allows_engraving` e
        // `engraving_max_chars` nasceram em português e foram renomeadas na
        // migration de tradução do schema, que o leitor de migrations do
        // Larastan não acompanha — é a mesma razão do `width_mm` logo acima.
        if (! (bool) $produto->getAttribute('allows_engraving')) {
            return ['permite' => false, 'maxChars' => null, 'preco' => null];
        }

        $configuracao = $this->conteudo->group(self::GRUPO_GRAVACAO);
        $limiteGlobal = $configuracao['max_chars'] ?? null;
        $preco = $configuracao['preco'] ?? null;

        $limite = (int) $produto->getAttribute('engraving_max_chars');

        if ($limite <= 0 && is_numeric($limiteGlobal)) {
            $limite = (int) $limiteGlobal;
        }

        return [
            'permite' => true,
            'maxChars' => $limite > 0 ? $limite : null,
            'preco' => is_numeric($preco) ? ValorPtBr::moeda((float) $preco) : null,
        ];
    }

    /**
     * Galeria da peça: a capa primeiro, depois a ordem gravada.
     *
     * A ordenação é uma só, com três critérios — encadear `sortByDesc` com
     * `sortBy` reordenaria tudo e a posição passaria a mandar na capa.
     *
     * @return list<array{src: string, alt: string}>
     */
    private function imagens(Product $produto): array
    {
        if (! $produto->relationLoaded('images')) {
            return [];
        }

        /** @var list<array{src: string, alt: string}> $imagens */
        $imagens = $produto->images
            ->sortBy([['is_primary', 'desc'], ['position', 'asc'], ['id', 'asc']])
            ->map(fn (ProductImage $imagem): array => [
                'src' => asset($this->texto($imagem->path)),
                'alt' => $this->rotuloSemFornecedor(
                    $this->textoOuNulo($imagem->alt) ?? $this->texto($produto->name),
                ),
            ])
            ->values()
            ->all();

        return $imagens;
    }

    // ───────────────────────────── loja ─────────────────────────────

    /**
     * O banner do topo. É a mesma leitura da prévia da tela 2.6 — o lojista
     * precisa ver na personalização exatamente o que o cliente vê aqui.
     *
     * @return array{titulo: string, slogan: string, imagem: string|null}
     */
    private function banner(ResellerStore $loja): array
    {
        $imagem = $this->textoOuNulo($loja->banner_path);

        return [
            'titulo' => $this->textoOuNulo($loja->name) ?? 'Nossa loja',
            'slogan' => $this->textoOuNulo($loja->slogan) ?? 'Símbolo de amor. Promessa para a vida toda.',
            'imagem' => $imagem === null ? null : asset('storage/'.$imagem),
        ];
    }

    /**
     * Contato da loja — do lojista, nunca da Velaro (regra 1).
     *
     * Público porque o comprovante do pedido termina com o mesmo bloco de
     * atendimento da vitrine, e porque é aqui que mora a garantia de que o
     * telefone impresso é o do balcão.
     *
     * @return array{whatsapp: string|null, whatsappUrl: string|null, phone: string|null, phoneUrl: string|null, email: string|null, address: string|null}
     */
    public function montarContato(ResellerStore $loja): array
    {
        return $this->contato($loja);
    }

    /**
     * Os dois avisos de retirada e pagamento, para as telas que não passam pelos
     * montadores desta classe — o carrinho e o comprovante.
     *
     * @return array{apenasRetirada: bool, pagamentoNaLoja: bool, aviso: string}
     */
    public function montarRetirada(ResellerStore $loja): array
    {
        return $this->retirada($loja);
    }

    /**
     * @return array{whatsapp: string|null, whatsappUrl: string|null, phone: string|null, phoneUrl: string|null, email: string|null, address: string|null}
     */
    private function contato(ResellerStore $loja): array
    {
        $whatsapp = $this->textoOuNulo($loja->whatsapp);
        $telefone = $this->textoOuNulo($loja->phone);

        return [
            'whatsapp' => $whatsapp,
            'whatsappUrl' => $whatsapp === null ? null : 'https://wa.me/'.$this->e164($whatsapp),
            'phone' => $telefone,
            'phoneUrl' => $telefone === null ? null : 'tel:+'.$this->e164($telefone),
            'email' => $this->textoOuNulo($loja->email),
            'address' => $this->textoOuNulo($loja->getAttribute('address')),
        ];
    }

    /**
     * Telefone só com dígitos e com o código do país na frente.
     *
     * O lojista digita o número como quiser — `(11) 98888-2020`, `+55 11 …`,
     * `5511…`. O `wa.me` só aceita dígitos com DDI, e prefixar 55 sem olhar
     * transformaria quem já digitou o país em `5555…`. A regra é simples: já
     * tem DDI quem começa com 55 e tem comprimento de número brasileiro
     * completo (12 ou 13 dígitos, com DDD e 8 ou 9 no assinante).
     */
    private function e164(string $telefone): string
    {
        $digitos = (string) preg_replace('/\D+/', '', $telefone);

        if (str_starts_with($digitos, '55') && in_array(mb_strlen($digitos), [12, 13], true)) {
            return $digitos;
        }

        return '55'.$digitos;
    }

    /**
     * Os dois chips de retirada e pagamento, com o texto que o protótipo dá a
     * cada um. Nenhum deles cobra nada: são a orientação de que o pedido é
     * retirado e pago no balcão do lojista.
     *
     * @return array{apenasRetirada: bool, pagamentoNaLoja: bool, aviso: string}
     */
    private function retirada(ResellerStore $loja): array
    {
        $apenasRetirada = (bool) $loja->pickup_only;
        $pagamentoNaLoja = (bool) $loja->payment_in_store;
        $nome = $this->textoOuNulo($loja->name) ?? 'nossa loja';

        $aviso = $apenasRetirada
            ? 'Seu pedido estará disponível para retirada na loja '.$nome.'.'
            : 'A entrega é combinada diretamente com a equipe da loja '.$nome.'.';

        if ($pagamentoNaLoja) {
            $aviso .= ' O pagamento é realizado no caixa da loja.';
        }

        return [
            'apenasRetirada' => $apenasRetirada,
            'pagamentoNaLoja' => $pagamentoNaLoja,
            'aviso' => $aviso,
        ];
    }

    // ───────────────────────────── favoritos ─────────────────────────────

    /**
     * Quais das peças da tela o visitante já marcou com o coração.
     *
     * Sem token não há consulta: um visitante novo não tem favorito, e sair
     * procurando por `visitor_token` vazio devolveria o gosto de todo mundo que
     * também não tem token. O filtro por loja é o que impede o coração de uma
     * vitrine aparecer na de outra — `favorites` guarda `reseller_store_id`
     * justamente para isso.
     *
     * @param  list<int>  $produtos
     * @return list<int>
     */
    private function favoritos(ResellerStore $loja, array $produtos, ?string $visitante): array
    {
        if ($visitante === null || $visitante === '' || $produtos === []) {
            return [];
        }

        /** @var list<int> $marcados */
        $marcados = Favorite::query()
            ->where('visitor_token', $visitante)
            ->where('reseller_store_id', $loja->getKey())
            ->whereIn('product_id', $produtos)
            ->pluck('product_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        return $marcados;
    }

    // ───────────────────── regra 1: a marca do fornecedor ─────────────────────

    /**
     * Prosa vinda do catálogo da fábrica, sem a marca do fornecedor.
     *
     * A descrição da peça é escrita por quem produz, e ela cita a fábrica —
     * "produzida na fábrica própria da …". Isso é exatamente o vazamento de
     * marca que a regra 1 proíbe, e não é hipótese: está no catálogo semeado.
     *
     * A frase inteira sai, e não só a palavra: apagar o nome deixaria
     * "Produzida na fábrica própria da , com controle de peso", que é pior do
     * que não dizer nada. Sobrou frase, a descrição fica; não sobrou, some.
     */
    private function textoSemFornecedor(?string $texto): ?string
    {
        if ($texto === null || trim($texto) === '') {
            return null;
        }

        $marcas = $this->marcasDoFornecedor();

        if ($marcas === []) {
            return $texto;
        }

        $frases = preg_split('/(?<=[.!?])\s+/u', trim($texto)) ?: [];

        $limpas = array_values(array_filter(
            $frases,
            fn (string $frase): bool => ! $this->citaFornecedor($frase, $marcas),
        ));

        $resultado = trim(implode(' ', $limpas));

        return $resultado === '' ? null : $resultado;
    }

    /**
     * Rótulo curto do catálogo (nome da peça, material, acabamento, alt da
     * foto) sem a marca do fornecedor.
     *
     * Aqui não há frase para descartar — o rótulo é o dado. Sai o termo, e a
     * pontuação e o espaço que sobram são costurados de volta.
     */
    private function rotuloSemFornecedor(string $rotulo): string
    {
        if ($rotulo === '') {
            return '';
        }

        foreach ($this->marcasDoFornecedor() as $marca) {
            $rotulo = (string) preg_replace(
                '/\s*'.preg_quote($marca, '/').'\s*/iu',
                ' ',
                $rotulo,
            );
        }

        $rotulo = (string) preg_replace('/\s{2,}/u', ' ', $rotulo);

        return trim($rotulo, " \t\n\r\0\x0B·|-–—,");
    }

    /**
     * Como o fornecedor se chama, para poder não ser citado.
     *
     * Os nomes saem de `settings` (`company.*`), que é onde a própria fábrica se
     * declara, mais o nome da aplicação e a sigla do grupo — os três jeitos de
     * escrever a mesma marca. Cada termo composto também entra pela primeira
     * palavra, para pegar "Velaro" dentro de "Velaro Alianças Ltda.". Termo com
     * menos de três letras fica de fora: apagaria pedaço de palavra inocente.
     *
     * @return list<string>
     */
    private function marcasDoFornecedor(): array
    {
        $empresa = $this->conteudo->company();

        $candidatos = [
            (string) config('app.name'),
            $empresa['nome'] ?? '',
            $empresa['razao_social'] ?? '',
            'SVD',
        ];

        $marcas = [];

        foreach ($candidatos as $candidato) {
            $candidato = trim($candidato);

            if ($candidato === '') {
                continue;
            }

            $primeira = trim((string) strtok($candidato, ' '));

            foreach ([$candidato, $primeira] as $termo) {
                if (mb_strlen($termo) >= 3) {
                    $marcas[mb_strtolower($termo)] = $termo;
                }
            }
        }

        // Do termo mais longo para o mais curto: "Velaro Alianças Ltda." sai
        // inteiro antes de "Velaro" morder só o começo dele.
        $lista = array_values($marcas);
        usort($lista, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        return $lista;
    }

    /**
     * @param  list<string>  $marcas
     */
    private function citaFornecedor(string $frase, array $marcas): bool
    {
        foreach ($marcas as $marca) {
            if (mb_stripos($frase, $marca) !== false) {
                return true;
            }
        }

        return false;
    }

    // ───────────────────────────── texto ─────────────────────────────

    private function texto(mixed $valor): string
    {
        return is_scalar($valor) ? trim((string) $valor) : '';
    }

    private function textoOuNulo(mixed $valor): ?string
    {
        $texto = $this->texto($valor);

        return $texto === '' ? null : $texto;
    }
}
