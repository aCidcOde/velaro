<?php

/*
[Modulo: app/Services/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Monta a tela 2.2: grade com o custo B2B, disponibilidade lida do cofre, ficha do drawer e exportacao do recorte.
*/

namespace App\Services\Portal;

use App\Http\Requests\Portal\CatalogoFiltroRequest as Filtro;
use App\Models\Finish;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\StockItem;
use App\Services\Portal\Concerns\FormataDados;
use App\Support\ResellerScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Catálogo do lojista — a **única tela do portal que mostra `products.price`**.
 *
 * Esse número é o custo B2B: o que a Velaro cobra da loja pela peça. Ele aparece
 * aqui de propósito (regra 1 da tela 2.2), e é a razão de o catálogo público
 * projetar o produto sem a coluna. O que não pode vazar é para o *lado de fora*:
 * a vitrine white label mostra o preço que o lojista definiu, nunca o custo.
 *
 * O catálogo em si **não é escopado por revendedor** — é a grade da fábrica, a
 * mesma para todo lojista aprovado. Não existe `reseller_id` em `products`, e
 * por isso nenhuma query deste service passa pelo {@see ResellerScope}:
 * o que o escopo protege é o dado do lojista (pedido, cliente, lote, chamado,
 * regra de preço), e nada disso é lido aqui. O acesso ao catálogo já foi decidido
 * pelo middleware `reseller`, um degrau antes.
 *
 * Estoque é **somente leitura** (regra 2): `stock_items` pertence à operação da
 * Velaro e o portal apenas consulta o saldo para dizer se a peça sai do cofre ou
 * entra em produção.
 */
class CatalogoRevendedorService
{
    use FormataDados;

    /** Grade 5×2 do protótipo: dez modelos por página. */
    private const POR_PAGINA = 10;

    /**
     * Janela do selo "NOVO", em dias sobre `products.created_at`. É a mesma
     * coluna que a ordenação "Lançamento" usa, como a régua de aceite manda.
     */
    private const DIAS_DE_LANCAMENTO = 45;

    /**
     * Dados da tela.
     *
     * @param  array{q: string|null, colecao: string|null, material: string|null, acabamento: string|null, largura: float|null, disponibilidade: string|null, ordenar: string, ver: string|null}  $filtros
     * @return array<string, mixed>
     */
    public function montarIndice(array $filtros): array
    {
        $produtos = $this->listar($filtros);

        return [
            'filtros' => $filtros,
            'indicadores' => $this->indicadores(),
            'opcoesDeFiltro' => $this->opcoesDeFiltro($filtros),
            'produtos' => $produtos,
            'cartoes' => $this->cartoes($produtos->items()),
            'ficha' => $this->ficha($filtros['ver']),
            'temFiltroAtivo' => $this->temFiltroAtivo($filtros),
            // "Exportar catálogo" leva o recorte da tela; `ver` e `page` ficam de
            // fora porque o arquivo é a seleção inteira, não a página aberta.
            'urlExportar' => route('portal.catalogo', $this->parametros($filtros) + ['exportar' => 'csv']),
            'urlLimpar' => route('portal.catalogo'),
        ];
    }

    /**
     * Filtros ativos no formato da query string, para remontar a URL da tela.
     *
     * @param  array{q: string|null, colecao: string|null, material: string|null, acabamento: string|null, largura: float|null, disponibilidade: string|null, ordenar: string, ver: string|null}  $filtros
     * @return array<string, string>
     */
    private function parametros(array $filtros): array
    {
        $parametros = [];

        foreach (['q', 'colecao', 'material', 'acabamento', 'disponibilidade'] as $campo) {
            $valor = $filtros[$campo];

            if (is_string($valor) && $valor !== '') {
                $parametros[$campo] = $valor;
            }
        }

        if ($filtros['largura'] !== null) {
            $parametros['largura'] = $this->numeroUrl($filtros['largura']);
        }

        if ($filtros['ordenar'] !== Filtro::ORDEM_LANCAMENTO) {
            $parametros['ordenar'] = $filtros['ordenar'];
        }

        return $parametros;
    }

    /**
     * "Exportar catálogo": o mesmo recorte da tela, em CSV.
     *
     * O arquivo carrega o custo B2B — é planilha de compra do lojista, e é para
     * isso que ele existe. Vai com BOM porque o destino é o Excel em pt-BR.
     *
     * @param  array{q: string|null, colecao: string|null, material: string|null, acabamento: string|null, largura: float|null, disponibilidade: string|null, ordenar: string, ver: string|null}  $filtros
     */
    public function exportar(array $filtros): StreamedResponse
    {
        $consulta = $this->ordenar($this->filtrar($this->catalogo(), $filtros), $filtros['ordenar']);
        $arquivo = 'catalogo-velaro-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($consulta): void {
            $saida = fopen('php://output', 'wb');

            if ($saida === false) {
                return;
            }

            fwrite($saida, "\u{FEFF}");
            fputcsv($saida, ['SKU', 'Produto', 'Coleção', 'Material', 'Acabamento', 'Largura', 'Custo Velaro (R$)', 'Disponibilidade', 'Saldo em cofre', 'Prazo (dias úteis)'], ';');

            // `chunk` mantém o consumo de memória constante: o catálogo cresce
            // com a fábrica, e uma exportação não pode carregar tudo de uma vez.
            $consulta->chunk(200, function (EloquentCollection $lote) use ($saida): void {
                foreach ($lote as $produto) {
                    $disponibilidade = $this->disponibilidade($produto);
                    $prazo = $produto->getAttribute('delivery_days');

                    fputcsv($saida, [
                        $this->texto($produto->getAttribute('sku')),
                        $this->texto($produto->getAttribute('name')),
                        $this->texto($produto->collection?->name),
                        $this->texto($produto->material?->name),
                        $this->texto($produto->finish?->name),
                        $this->largura($produto) ?? '',
                        number_format($this->custo($produto), 2, ',', ''),
                        $disponibilidade['rotulo'],
                        (string) $this->saldo($produto),
                        is_numeric($prazo) ? (string) (int) $prazo : '',
                    ], ';');
                }
            });

            fclose($saida);
        }, $arquivo, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Os quatro KPIs do topo. "Em estoque" e "Sob encomenda" não somam
     * obrigatoriamente o total: a peça de pronta-entrega que zerou no cofre não
     * está em nenhum dos dois, e é justamente esse o terceiro estado que a tela
     * precisa deixar visível.
     *
     * @return list<array{icone: string, variante: string, rotulo: string, valor: int, nota: string, url: string|null}>
     */
    public function indicadores(): array
    {
        $colecoes = ProductCollection::query()
            ->where('is_active', true)
            ->whereHas('products', fn (Builder $produto): Builder => $this->visiveis($produto))
            ->count();

        return [
            [
                'icone' => 'book',
                'variante' => 'kpi__icon--gold',
                'rotulo' => 'Total de produtos',
                'valor' => $this->catalogo()->count(),
                'nota' => 'Ver catálogo →',
                'url' => route('portal.catalogo'),
            ],
            [
                'icone' => 'box',
                'variante' => 'kpi__icon--ok',
                'rotulo' => 'Em estoque',
                'valor' => $this->comDisponibilidade($this->catalogo(), Filtro::DISPONIBILIDADE_ESTOQUE)->count(),
                'nota' => 'Produtos disponíveis',
                'url' => route('portal.catalogo', ['disponibilidade' => Filtro::DISPONIBILIDADE_ESTOQUE]),
            ],
            [
                'icone' => 'clock',
                'variante' => 'kpi__icon--info',
                'rotulo' => 'Sob encomenda',
                'valor' => $this->comDisponibilidade($this->catalogo(), Filtro::DISPONIBILIDADE_ENCOMENDA)->count(),
                'nota' => 'Produtos sob pedido',
                'url' => route('portal.catalogo', ['disponibilidade' => Filtro::DISPONIBILIDADE_ENCOMENDA]),
            ],
            [
                'icone' => 'diamond',
                'variante' => 'kpi__icon--violet',
                'rotulo' => 'Coleções ativas',
                'valor' => $colecoes,
                'nota' => 'Ver coleções →',
                'url' => route('portal.catalogo'),
            ],
        ];
    }

    /**
     * Ficha do painel lateral (`?ver=SKU`).
     *
     * SKU desconhecido devolve `null` e a tela simplesmente não abre o painel:
     * o drawer é estado de interface na query string, não um recurso com rota
     * própria, e um link velho não deve derrubar a grade inteira.
     *
     * @return array<string, mixed>|null
     */
    public function ficha(?string $sku): ?array
    {
        if ($sku === null) {
            return null;
        }

        $produto = $this->catalogo()->where('sku', $sku)->first();

        if (! $produto instanceof Product) {
            return null;
        }

        // Só o painel lateral precisa da grade de aros: a listagem se contenta
        // com o saldo somado da subconsulta.
        $produto->load('variants.stockItems');

        $imagens = $this->imagens($produto);
        $prazo = $produto->getAttribute('delivery_days');
        $disponibilidade = $this->disponibilidade($produto);

        return [
            'nome' => $this->texto($produto->getAttribute('name')),
            'sku' => $this->texto($produto->getAttribute('sku')),
            'custo' => $this->dinheiro($this->custo($produto)),
            'disponibilidade' => $disponibilidade,
            'capa' => $this->arte($imagens->first(), $produto),
            'miniaturas' => $imagens->map(fn (ProductImage $imagem): array => $this->arte($imagem, $produto))->all(),
            'ficha' => array_values(array_filter([
                ['icone' => 'diamond', 'rotulo' => 'Material', 'valor' => $this->texto($produto->material?->name)],
                ['icone' => 'ring', 'rotulo' => 'Largura', 'valor' => $this->largura($produto) ?? ''],
                ['icone' => 'sparkle', 'rotulo' => 'Acabamento', 'valor' => $this->texto($produto->finish?->name)],
                ['icone' => 'clock', 'rotulo' => 'Prazo de entrega', 'valor' => is_numeric($prazo) ? sprintf('Até %d dias úteis', (int) $prazo) : ''],
                ['icone' => 'box', 'rotulo' => 'Disponibilidade', 'valor' => $disponibilidade['rotulo']],
            ], static fn (array $linha): bool => $linha['valor'] !== '')),
            'aros' => $this->aros($produto),
            // O item entra no pedido pela tela 2.5, que é onde o pedido é
            // montado; daqui vai só o SKU escolhido.
            'urlPedido' => route('portal.pedidos.index', ['produto' => $this->texto($produto->getAttribute('sku'))]),
        ];
    }

    /**
     * Disponibilidade por aro — a leitura do cofre que o lojista precisa antes
     * de prometer prazo ao cliente. Só variante ativa entra.
     *
     * @return list<array{aro: string, saldo: int, disponivel: bool}>
     */
    public function aros(Product $produto): array
    {
        return $produto->variants
            ->where('is_active', true)
            ->sortBy(static fn (ProductVariant $variante): int => (int) $variante->getAttribute('ring_size'))
            ->map(static function (ProductVariant $variante): array {
                $saldo = (int) $variante->stockItems->sum('available');

                return [
                    'aro' => (string) $variante->getAttribute('ring_size'),
                    'saldo' => $saldo,
                    'disponivel' => $saldo > 0,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Card `.prod` da grade, já resolvido.
     *
     * @return array{sku: string, nome: string, especificacao: string, custo: string, novo: bool, disponibilidade: array{chave: string, rotulo: string, chip: string}, imagem: array{src: string, alt: string}|null, urlFicha: string, urlPedido: string}
     */
    public function cartao(Product $produto): array
    {
        $sku = $this->texto($produto->getAttribute('sku'));

        return [
            'sku' => $sku,
            'nome' => $this->texto($produto->getAttribute('name')),
            'especificacao' => $this->especificacao($produto),
            'custo' => $this->dinheiro($this->custo($produto)),
            'novo' => $this->ehLancamento($produto),
            'disponibilidade' => $this->disponibilidade($produto),
            'imagem' => $this->arte($this->imagens($produto)->first(), $produto),
            'urlFicha' => route('portal.catalogo', ['ver' => $sku]),
            'urlPedido' => route('portal.pedidos.index', ['produto' => $sku]),
        ];
    }

    /**
     * @param  array<int, Product>  $produtos
     * @return list<array<string, mixed>>
     */
    private function cartoes(array $produtos): array
    {
        return array_values(array_map(fn (Product $produto): array => $this->cartao($produto), $produtos));
    }

    /**
     * @param  array{q: string|null, colecao: string|null, material: string|null, acabamento: string|null, largura: float|null, disponibilidade: string|null, ordenar: string, ver: string|null}  $filtros
     * @return LengthAwarePaginator<int, Product>
     */
    private function listar(array $filtros): LengthAwarePaginator
    {
        return $this->ordenar($this->filtrar($this->catalogo(), $filtros), $filtros['ordenar'])
            ->paginate(self::POR_PAGINA)
            ->withQueryString();
    }

    /**
     * @param  Builder<Product>  $consulta
     * @param  array{q: string|null, colecao: string|null, material: string|null, acabamento: string|null, largura: float|null, disponibilidade: string|null, ordenar: string, ver: string|null}  $filtros
     * @return Builder<Product>
     */
    private function filtrar(Builder $consulta, array $filtros): Builder
    {
        if ($filtros['q'] !== null) {
            $termo = '%'.str_replace(['%', '_'], ['\%', '\_'], $filtros['q']).'%';

            $consulta->where(function (Builder $busca) use ($termo): void {
                $busca->where('name', 'like', $termo)
                    ->orWhere('sku', 'like', $termo)
                    ->orWhere('description', 'like', $termo)
                    // "código ou referência" do placeholder é o SKU do aro, que
                    // é o que vem impresso na etiqueta da peça.
                    ->orWhereHas('variants', static fn (Builder $variante): Builder => $variante->where('sku', 'like', $termo))
                    ->orWhereHas('collection', static fn (Builder $colecao): Builder => $colecao->where('name', 'like', $termo));
            });
        }

        foreach (['colecao' => 'collection', 'material' => 'material', 'acabamento' => 'finish'] as $filtro => $relacao) {
            $slug = $filtros[$filtro];

            if ($slug !== null) {
                $consulta->whereHas($relacao, static fn (Builder $vinculo): Builder => $vinculo->where('slug', $slug));
            }
        }

        if ($filtros['largura'] !== null) {
            $consulta->where('width_mm', $filtros['largura']);
        }

        if ($filtros['disponibilidade'] !== null) {
            $this->comDisponibilidade($consulta, $filtros['disponibilidade']);
        }

        return $consulta;
    }

    /**
     * O mesmo predicado serve ao filtro e ao KPI — assim o número do topo e o
     * tamanho da grade nunca discordam.
     *
     * @param  Builder<Product>  $consulta
     * @return Builder<Product>
     */
    private function comDisponibilidade(Builder $consulta, string $disponibilidade): Builder
    {
        return match ($disponibilidade) {
            Filtro::DISPONIBILIDADE_ENCOMENDA => $consulta->where('is_made_to_order', true),
            Filtro::DISPONIBILIDADE_ESTOQUE => $consulta
                ->where('is_made_to_order', false)
                ->whereHas('variants', fn (Builder $variante): Builder => $this->comSaldo($variante)),
            Filtro::DISPONIBILIDADE_ESGOTADO => $consulta
                ->where('is_made_to_order', false)
                ->whereDoesntHave('variants', fn (Builder $variante): Builder => $this->comSaldo($variante)),
            default => $consulta,
        };
    }

    /**
     * Aro ativo com peça no cofre.
     *
     * O genérico existe porque este predicado é usado nos dois lados: sobre
     * `ProductVariant::query()` e dentro do `whereHas('variants', …)`, onde o
     * Eloquent entrega um `Builder<Model>` sem o parâmetro resolvido.
     *
     * @template TVariante of Model
     *
     * @param  Builder<TVariante>  $variante
     * @return Builder<TVariante>
     */
    private function comSaldo(Builder $variante): Builder
    {
        return $variante->where('is_active', true)
            ->whereHas('stockItems', static fn (Builder $saldo): Builder => $saldo->where('available', '>', 0));
    }

    /**
     * @param  Builder<Product>  $consulta
     * @return Builder<Product>
     */
    private function ordenar(Builder $consulta, string $ordem): Builder
    {
        return match ($ordem) {
            Filtro::ORDEM_NOME => $consulta->orderBy('name')->orderBy('id'),
            Filtro::ORDEM_CUSTO_ASC => $consulta->orderBy('price')->orderBy('id'),
            Filtro::ORDEM_CUSTO_DESC => $consulta->orderByDesc('price')->orderBy('id'),
            // Lançamento: o mais novo primeiro. O `id` desempata o catálogo
            // semeado de uma vez, em que todo produto nasce no mesmo segundo.
            default => $consulta->orderByDesc('created_at')->orderByDesc('id'),
        };
    }

    /**
     * Listas dos `select` da barra. Só entra opção que tem produto visível, para
     * que nenhuma delas leve a uma grade vazia.
     *
     * @param  array{q: string|null, colecao: string|null, material: string|null, acabamento: string|null, largura: float|null, disponibilidade: string|null, ordenar: string, ver: string|null}  $filtros
     * @return array<string, list<array{valor: string, rotulo: string, selecionado: bool}>>
     */
    private function opcoesDeFiltro(array $filtros): array
    {
        $colecoes = ProductCollection::query()
            ->where('is_active', true)
            ->whereHas('products', fn (Builder $produto): Builder => $this->visiveis($produto))
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        $materiais = Material::query()
            ->whereHas('products', fn (Builder $produto): Builder => $this->visiveis($produto))
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        $acabamentos = Finish::query()
            ->whereHas('products', fn (Builder $produto): Builder => $this->visiveis($produto))
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        $larguras = array_map(
            fn (float $mm): array => [
                'valor' => $this->numeroUrl($mm),
                'rotulo' => $this->numero($mm).'mm',
                'selecionado' => $filtros['largura'] !== null && abs($filtros['largura'] - $mm) < 0.0001,
            ],
            $this->larguras(),
        );

        return [
            'colecoes' => $colecoes->map(static fn (ProductCollection $colecao): array => [
                'valor' => (string) $colecao->slug,
                'rotulo' => (string) $colecao->name,
                'selecionado' => $colecao->slug === $filtros['colecao'],
            ])->all(),
            'materiais' => $materiais->map(static fn (Material $material): array => [
                'valor' => (string) $material->slug,
                'rotulo' => (string) $material->name,
                'selecionado' => $material->slug === $filtros['material'],
            ])->all(),
            'acabamentos' => $acabamentos->map(static fn (Finish $acabamento): array => [
                'valor' => (string) $acabamento->slug,
                'rotulo' => (string) $acabamento->name,
                'selecionado' => $acabamento->slug === $filtros['acabamento'],
            ])->all(),
            'larguras' => $larguras,
            'disponibilidades' => array_map(
                fn (string $chave): array => [
                    'valor' => $chave,
                    'rotulo' => $this->rotuloDeDisponibilidade($chave),
                    'selecionado' => $chave === $filtros['disponibilidade'],
                ],
                Filtro::DISPONIBILIDADES,
            ),
            'ordens' => array_map(
                static fn (string $chave): array => [
                    'valor' => $chave,
                    'rotulo' => match ($chave) {
                        Filtro::ORDEM_NOME => 'Nome (A–Z)',
                        Filtro::ORDEM_CUSTO_ASC => 'Menor custo',
                        Filtro::ORDEM_CUSTO_DESC => 'Maior custo',
                        default => 'Lançamento',
                    },
                    'selecionado' => $chave === $filtros['ordenar'],
                ],
                Filtro::ORDENS,
            ),
        ];
    }

    /**
     * Estado de disponibilidade da peça, com o chip que a tela usa.
     *
     * @return array{chave: string, rotulo: string, chip: string}
     */
    private function disponibilidade(Product $produto): array
    {
        if ((bool) $produto->getAttribute('is_made_to_order')) {
            $chave = Filtro::DISPONIBILIDADE_ENCOMENDA;
        } elseif ($this->saldo($produto) > 0) {
            $chave = Filtro::DISPONIBILIDADE_ESTOQUE;
        } else {
            $chave = Filtro::DISPONIBILIDADE_ESGOTADO;
        }

        return [
            'chave' => $chave,
            'rotulo' => $this->rotuloDeDisponibilidade($chave),
            'chip' => match ($chave) {
                Filtro::DISPONIBILIDADE_ESTOQUE => 'chip--ok',
                Filtro::DISPONIBILIDADE_ENCOMENDA => 'chip--info',
                default => 'chip--warn',
            },
        ];
    }

    private function rotuloDeDisponibilidade(string $chave): string
    {
        return match ($chave) {
            Filtro::DISPONIBILIDADE_ESTOQUE => 'Em estoque',
            Filtro::DISPONIBILIDADE_ENCOMENDA => 'Sob encomenda',
            default => 'Sem saldo em cofre',
        };
    }

    /**
     * Saldo somado dos aros ativos. Vem do `withSum` da projeção, e não de uma
     * query por card: a grade tem dez peças e cada uma tem até cinco aros.
     */
    private function saldo(Product $produto): int
    {
        $saldo = $produto->getAttribute('saldo_em_cofre');

        return is_numeric($saldo) ? (int) $saldo : 0;
    }

    private function custo(Product $produto): float
    {
        $preco = $produto->getAttribute('price');

        return is_numeric($preco) ? (float) $preco : 0.0;
    }

    /**
     * Peça lançada há pouco. A janela é fixa e medida sobre `created_at`, a
     * mesma coluna da ordenação "Lançamento" — num catálogo semeado de uma vez
     * todas as peças aparecem como novas, e isso é o comportamento correto: elas
     * são novas.
     */
    private function ehLancamento(Product $produto): bool
    {
        $criadoEm = $produto->getAttribute('created_at');

        return $criadoEm !== null && $criadoEm->greaterThanOrEqualTo(now()->subDays(self::DIAS_DE_LANCAMENTO));
    }

    /**
     * Segunda linha do card: `Ouro 18k | Polido · 4mm`.
     */
    private function especificacao(Product $produto): string
    {
        $largura = $this->largura($produto);

        $ficha = collect([$produto->material?->name, $produto->finish?->name])
            ->filter(static fn (?string $parte): bool => $parte !== null && $parte !== '')
            ->implode(' | ');

        return collect([$ficha, $largura])
            ->filter(static fn (?string $parte): bool => $parte !== null && $parte !== '')
            ->implode(' · ');
    }

    /**
     * Galeria ordenada: capa primeiro, depois a posição, com o `id` só para
     * desempatar. Um `sort` encadeado não serviria — o segundo reordenaria tudo.
     *
     * @return EloquentCollection<int, ProductImage>
     */
    private function imagens(Product $produto): EloquentCollection
    {
        /** @var EloquentCollection<int, ProductImage> $imagens */
        $imagens = $produto->images
            ->sortBy([
                ['is_primary', 'desc'],
                ['position', 'asc'],
                ['id', 'asc'],
            ])
            ->values();

        return $imagens;
    }

    /**
     * @return array{src: string, alt: string}|null
     */
    private function arte(?ProductImage $imagem, Product $produto): ?array
    {
        if (! $imagem instanceof ProductImage) {
            return null;
        }

        $alt = $this->texto($imagem->getAttribute('alt'));

        return [
            'src' => asset($this->texto($imagem->getAttribute('path'))),
            'alt' => $alt !== '' ? $alt : $this->texto($produto->getAttribute('name')),
        ];
    }

    /**
     * Projeção do catálogo, com o saldo do cofre somado em uma passada só.
     *
     * `products.price` **entra** aqui — é o custo B2B, e é o motivo de a tela
     * existir. A projeção pública do site é a que precisa deixá-lo de fora.
     *
     * @return Builder<Product>
     */
    private function catalogo(): Builder
    {
        // O saldo entra como subconsulta escalar, e não como `withSum` sobre a
        // relação: o saldo mora dois níveis abaixo (produto → aro → cofre), e
        // agregado aninhado não existe no Eloquent. Assim a grade inteira sai em
        // uma query, sem carregar os aros de cada peça só para somar.
        $saldo = StockItem::query()
            ->selectRaw('COALESCE(SUM(stock_items.available), 0)')
            ->join('product_variants', 'product_variants.id', '=', 'stock_items.product_variant_id')
            ->whereColumn('product_variants.product_id', 'products.id')
            ->where('product_variants.is_active', true);

        return $this->visiveis(Product::query())
            ->select('products.*')
            ->addSelect(['saldo_em_cofre' => $saldo])
            ->with(['collection', 'material', 'finish', 'images']);
    }

    /**
     * O catálogo do lojista é o catálogo da fábrica: peça ativa e dentro de uma
     * coleção. Diferente da grade pública, aqui não se exige `slug` — o portal
     * abre a ficha pelo SKU, no drawer, e não por rota de detalhe.
     *
     * Genérico pela mesma razão de {@see comSaldo()}: o predicado também roda
     * dentro do `whereHas('products', …)` das coleções, dos materiais e dos
     * acabamentos, onde o builder chega sem o model no tipo.
     *
     * @template TProduto of Model
     *
     * @param  Builder<TProduto>  $consulta
     * @return Builder<TProduto>
     */
    private function visiveis(Builder $consulta): Builder
    {
        return $consulta->where('is_active', true)->whereNotNull('collection_id');
    }

    /**
     * @param  array{q: string|null, colecao: string|null, material: string|null, acabamento: string|null, largura: float|null, disponibilidade: string|null, ordenar: string, ver: string|null}  $filtros
     */
    private function temFiltroAtivo(array $filtros): bool
    {
        foreach (['q', 'colecao', 'material', 'acabamento', 'largura', 'disponibilidade'] as $campo) {
            if ($filtros[$campo] !== null) {
                return true;
            }
        }

        return $filtros['ordenar'] !== Filtro::ORDEM_LANCAMENTO;
    }

    /**
     * @return list<float>
     */
    private function larguras(): array
    {
        return $this->visiveis(Product::query())
            ->whereNotNull('width_mm')
            ->distinct()
            ->orderBy('width_mm')
            ->pluck('width_mm')
            ->map(static fn (mixed $mm): float => (float) $mm)
            ->values()
            ->all();
    }

    private function largura(Product $produto): ?string
    {
        $mm = $produto->getAttribute('width_mm');

        return is_numeric($mm) ? $this->numero((float) $mm).'mm' : null;
    }

    /** Número pt-BR sem zero à direita: 5.00 vira `5`, 4.50 vira `4,5`. */
    private function numero(float $valor): string
    {
        return rtrim(rtrim(number_format($valor, 2, ',', ''), '0'), ',');
    }

    /** Mesma poda, com ponto decimal — é o valor que vai e volta na query string. */
    private function numeroUrl(float $valor): string
    {
        return rtrim(rtrim(number_format($valor, 2, '.', ''), '0'), '.');
    }
}
