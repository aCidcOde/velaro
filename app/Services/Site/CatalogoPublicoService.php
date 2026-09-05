<?php

/*
[Modulo: app/Services/Site]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Monta o catalogo publico da tela 1.3 (lista e detalhe) sem jamais tocar em products.price, que e custo B2B.
*/

namespace App\Services\Site;

use App\Models\Finish;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class CatalogoPublicoService
{
    /**
     * Regra 1 do escopo 1.3 (Anexo I §3.3): a rota publica nunca serializa
     * `products.price` — ele e o custo B2B cobrado do lojista. O bloqueio nao e
     * cosmetico na view: a coluna simplesmente nao entra no SELECT, entao nao ha
     * atributo para vazar em `toArray()`, em `@json` ou num dump acidental.
     * `user_id` fica de fora pelo mesmo motivo de higiene.
     *
     * @var list<string>
     */
    private const COLUNAS_PUBLICAS = [
        'id',
        'name',
        'slug',
        'sku',
        'description',
        'collection_id',
        'category_id',
        'material_id',
        'finish_id',
        'width_mm',
        'shape',
        'allows_engraving',
        'engraving_max_chars',
        'delivery_days',
        'is_made_to_order',
        'is_active',
        'meta',
    ];

    /** Grade 6x2 do prototipo: doze modelos por pagina. */
    private const POR_PAGINA = 12;

    /** Quantos modelos relacionados o detalhe mostra (quatro cards no prototipo). */
    private const RELACIONADOS = 4;

    /**
     * Dados da lista (`/catalogo` e `/catalogo/{colecao}`).
     *
     * @param  array{q: string|null, colecao: string|null, material: string|null, acabamento: string|null, largura: float|null, formato: string|null}  $filtros
     * @return array<string, mixed>
     */
    public function montarIndice(array $filtros, ?string $colecaoDaRota = null): array
    {
        // O segmento de rota manda: `/catalogo/diamond` e a URL canonica da colecao.
        if ($colecaoDaRota !== null && $colecaoDaRota !== '') {
            $filtros['colecao'] = $colecaoDaRota;
        }

        $colecaoAtual = null;

        if ($filtros['colecao'] !== null) {
            $consulta = ProductCollection::query()
                ->where('is_active', true)
                ->where('slug', $filtros['colecao']);

            // Colecao inexistente no caminho e 404, nao lista vazia.
            $colecaoAtual = $colecaoDaRota !== null && $colecaoDaRota !== ''
                ? $consulta->firstOrFail()
                : $consulta->first();
        }

        $produtos = $this->listar($filtros);

        return [
            'filtros' => $filtros,
            'colecaoAtual' => $colecaoAtual,
            'produtos' => $produtos,
            'cartoes' => $this->cartoes($produtos->items()),
            'opcoesDeFiltro' => $this->opcoesDeFiltro($filtros),
        ];
    }

    /**
     * Dados do detalhe (`/produto/{slug}`).
     *
     * @return array<string, mixed>
     */
    public function montarProduto(Product $produto): array
    {
        // O binding de rota traz o model inteiro, `price` incluso. Aqui ele e
        // relido pela projecao publica: o que chega na view nao tem preco e nao
        // tem dono. Produto inativo ou fora do catalogo da fabrica vira 404.
        $publico = $this->catalogo()->whereKey($produto->getKey())->firstOrFail();

        $imagens = $this->imagens($publico);

        return [
            'produto' => $publico,
            'trilha' => $this->trilha($publico),
            'referencia' => $this->referencia($publico),
            'capa' => $imagens->first(),
            'imagens' => $imagens,
            'ficha' => $this->fichaTecnica($publico),
            'opcoes' => $this->opcoesDeFabricacao($publico),
            'relacionados' => $this->cartoes($this->relacionados($publico)->all()),
        ];
    }

    /**
     * Parametros da URL canonica da lista, usados no redirect que transforma
     * `?colecao=diamond` em `/catalogo/diamond`.
     *
     * @param  array{q: string|null, colecao: string|null, material: string|null, acabamento: string|null, largura: float|null, formato: string|null}  $filtros
     * @return array<string, string>
     */
    public function parametrosDeRota(array $filtros): array
    {
        $parametros = [];

        foreach (['colecao', 'q', 'material', 'acabamento', 'formato'] as $campo) {
            $valor = $filtros[$campo] ?? null;

            if (is_string($valor) && $valor !== '') {
                $parametros[$campo] = $valor;
            }
        }

        if ($filtros['largura'] !== null) {
            $parametros['largura'] = $this->numeroUrl($filtros['largura']);
        }

        return $parametros;
    }

    /**
     * Largura formatada como o prototipo escreve: `5mm`, `4,5mm`.
     */
    public function largura(Product $produto): ?string
    {
        $mm = $produto->getAttribute('width_mm');

        return is_numeric($mm) ? $this->numero((float) $mm).'mm' : null;
    }

    /**
     * Linha de especificacao do card: `Prata 950 | Diamantada`.
     */
    public function especificacao(Product $produto): string
    {
        return collect([$produto->material?->name, $produto->finish?->name])
            ->filter(static fn (?string $parte): bool => $parte !== null && $parte !== '')
            ->implode(' | ');
    }

    /**
     * Capa da peca: a imagem marcada como primaria, senao a primeira da ordem.
     *
     * A ordenacao e uma so, com tres criterios — encadear `sortByDesc` com
     * `sortBy` nao funciona: o segundo `sort` reordena tudo e a posicao passa a
     * mandar na primaria. O `id` no fim so garante ordem estavel entre empates.
     * A tira de miniaturas usa esta mesma lista, entao o primeiro `.pdpthumb`
     * (`is-on`) e sempre a imagem que esta no `.pdpgal__main`.
     *
     * @return EloquentCollection<int, ProductImage>
     */
    public function imagens(Product $produto): EloquentCollection
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
     * Migalha do hero: `Catálogo › Alianças Tradicionais › Diamond`.
     */
    private function trilha(Product $produto): string
    {
        return collect(['Catálogo', $produto->category?->name, $produto->name])
            ->map(fn (mixed $parte): string => $this->texto($parte))
            ->filter(static fn (string $parte): bool => $parte !== '')
            ->implode(' › ');
    }

    /**
     * Subtitulo do hero: `Ref. VL-DM-01 · Prata 950 · Diamantada · 5mm`.
     */
    private function referencia(Product $produto): string
    {
        return collect([
            'Ref. '.$this->texto($produto->sku),
            $produto->material?->name,
            $produto->finish?->name,
            $this->largura($produto),
        ])
            ->map(fn (mixed $parte): string => $this->texto($parte))
            ->filter(static fn (string $parte): bool => $parte !== '' && $parte !== 'Ref.')
            ->implode(' · ');
    }

    /**
     * Card `.prod` da grade, ja resolvido: a view nao precisa fazer conta nem
     * conhecer o model. Nao ha campo de preco aqui, por construcao.
     *
     * @return array{nome: string, sku: string, slug: string, especificacao: string, largura: string|null, imagem: array{src: string, alt: string}|null}
     */
    public function cartao(Product $produto): array
    {
        $capa = $this->imagens($produto)->first();

        return [
            'nome' => $this->texto($produto->name),
            'sku' => $this->texto($produto->sku),
            'slug' => $this->texto($produto->slug),
            'especificacao' => $this->especificacao($produto),
            'largura' => $this->largura($produto),
            'imagem' => $capa instanceof ProductImage ? [
                'src' => asset($this->texto($capa->path)),
                'alt' => $this->texto($capa->alt) !== '' ? $this->texto($capa->alt) : $this->texto($produto->name),
            ] : null,
        ];
    }

    /**
     * @param  array<int, Product>  $produtos
     * @return list<array{nome: string, sku: string, slug: string, especificacao: string, largura: string|null, imagem: array{src: string, alt: string}|null}>
     */
    private function cartoes(array $produtos): array
    {
        return array_values(array_map(fn (Product $produto): array => $this->cartao($produto), $produtos));
    }

    /**
     * @param  array{q: string|null, colecao: string|null, material: string|null, acabamento: string|null, largura: float|null, formato: string|null}  $filtros
     * @return LengthAwarePaginator<int, Product>
     */
    private function listar(array $filtros): LengthAwarePaginator
    {
        $consulta = $this->catalogo();

        if ($filtros['q'] !== null) {
            $termo = '%'.str_replace(['%', '_'], ['\%', '\_'], $filtros['q']).'%';

            $consulta->where(function ($busca) use ($termo): void {
                $busca->where('name', 'like', $termo)
                    ->orWhere('sku', 'like', $termo)
                    ->orWhere('description', 'like', $termo)
                    ->orWhereHas('collection', static fn ($colecao) => $colecao->where('name', 'like', $termo))
                    ->orWhereHas('material', static fn ($material) => $material->where('name', 'like', $termo))
                    ->orWhereHas('finish', static fn ($acabamento) => $acabamento->where('name', 'like', $termo));
            });
        }

        foreach (['colecao' => 'collection', 'material' => 'material', 'acabamento' => 'finish'] as $filtro => $relacao) {
            $slug = $filtros[$filtro];

            if ($slug !== null) {
                $consulta->whereHas($relacao, static fn ($vinculo) => $vinculo->where('slug', $slug));
            }
        }

        if ($filtros['largura'] !== null) {
            $consulta->where('width_mm', $filtros['largura']);
        }

        if ($filtros['formato'] !== null) {
            $consulta->where('shape', $filtros['formato']);
        }

        // `products` nao tem coluna de ordem; a ordem de cadastro e a ordem do
        // catalogo (§5 lista de VL-DM-01 ate VL-DM-12 nessa sequencia).
        return $consulta->orderBy('id')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();
    }

    /**
     * Listas da barra de filtros, ja no formato do `<option>`: valor, rotulo e
     * marcacao de selecionado. So entra o que tem produto visivel, para que
     * nenhuma opcao do `select` leve a uma grade vazia.
     *
     * @param  array{q: string|null, colecao: string|null, material: string|null, acabamento: string|null, largura: float|null, formato: string|null}  $filtros
     * @return array<string, list<array{valor: string, rotulo: string, selecionado: bool}>>
     */
    private function opcoesDeFiltro(array $filtros): array
    {
        $colecoes = ProductCollection::query()
            ->where('is_active', true)
            ->whereHas('products', static function ($produto): void {
                $produto->where('is_active', true)->whereNotNull('collection_id')->whereNotNull('slug');
            })
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

        $formatos = array_map(
            static fn (string $formato): array => [
                'valor' => $formato,
                'rotulo' => $formato,
                'selecionado' => $formato === $filtros['formato'],
            ],
            $this->formatos(),
        );

        return [
            'colecoes' => $colecoes->map(static fn (ProductCollection $colecao): array => [
                'valor' => (string) $colecao->slug,
                'rotulo' => (string) $colecao->name,
                'selecionado' => $colecao->slug === $filtros['colecao'],
            ])->all(),
            'materiais' => $this->materiaisUsados()
                ->map(static fn (Material $material): array => [
                    'valor' => (string) $material->slug,
                    'rotulo' => (string) $material->name,
                    'selecionado' => $material->slug === $filtros['material'],
                ])->all(),
            'acabamentos' => $this->acabamentosUsados()
                ->map(static fn (Finish $acabamento): array => [
                    'valor' => (string) $acabamento->slug,
                    'rotulo' => (string) $acabamento->name,
                    'selecionado' => $acabamento->slug === $filtros['acabamento'],
                ])->all(),
            'larguras' => $larguras,
            'formatos' => $formatos,
        ];
    }

    /**
     * Ficha tecnica do detalhe, na ordem e com os icones do prototipo.
     *
     * @return list<array{icone: string, rotulo: string, valor: string}>
     */
    private function fichaTecnica(Product $produto): array
    {
        $meta = $produto->getAttribute('meta');
        $meta = is_array($meta) ? $meta : [];
        $largura = $this->largura($produto);
        $prazo = $produto->getAttribute('delivery_days');
        $limiteGravacao = $produto->getAttribute('engraving_max_chars');
        $peso = $meta['peso_aproximado_g'] ?? null;
        $garantia = $meta['garantia_meses'] ?? null;

        $linhas = [
            ['icone' => 'tag', 'rotulo' => 'Referência', 'valor' => $this->texto($produto->getAttribute('sku'))],
            ['icone' => 'diamond', 'rotulo' => 'Coleção', 'valor' => $this->texto($produto->collection?->name)],
            ['icone' => 'book', 'rotulo' => 'Categoria', 'valor' => $this->texto($produto->category?->name)],
            ['icone' => 'ring', 'rotulo' => 'Aros disponíveis', 'valor' => $this->texto($meta['aros_disponiveis'] ?? $this->faixaDeAros($produto))],
            ['icone' => 'edit', 'rotulo' => 'Gravação', 'valor' => $produto->getAttribute('allows_engraving') && is_numeric($limiteGravacao)
                ? sprintf('Interna · até %d caracteres + data', (int) $limiteGravacao)
                : ''],
            // O peso do prototipo e medido no aro 18, o aro de referencia da fabrica.
            ['icone' => 'box', 'rotulo' => 'Peso aproximado', 'valor' => is_numeric($peso) && $largura !== null
                ? sprintf('%s g por peça (%s, aro 18)', $this->numero((float) $peso), $largura)
                : ''],
            ['icone' => 'clock', 'rotulo' => 'Prazo de produção', 'valor' => is_numeric($prazo)
                ? sprintf('Até %d dias úteis', (int) $prazo)
                : ''],
            ['icone' => 'shield', 'rotulo' => 'Garantia', 'valor' => is_numeric($garantia)
                ? sprintf('%d meses contra defeito de fabricação', (int) $garantia)
                : ''],
            ['icone' => 'factory', 'rotulo' => 'Origem', 'valor' => $this->texto($meta['origem'] ?? null)],
        ];

        return array_values(array_filter($linhas, static fn (array $linha): bool => $linha['valor'] !== ''));
    }

    /**
     * Cartao "Opções de fabricação": a grade da fabrica com o que esta peca usa
     * marcado. A ultima secao e a disponibilidade por aro, vinda das variantes
     * ativas do proprio produto.
     *
     * @return list<array{rotulo: string, nota: string|null, itens: list<array{texto: string, ativo: bool}>}>
     */
    private function opcoesDeFabricacao(Product $produto): array
    {
        $larguraAtual = $this->largura($produto);

        $larguras = array_map(
            fn (float $mm): array => ['texto' => $this->numero($mm).'mm', 'ativo' => $this->numero($mm).'mm' === $larguraAtual],
            $this->larguras(),
        );

        $formatos = array_map(
            static fn (string $formato): array => ['texto' => $formato, 'ativo' => $formato === $produto->getAttribute('shape')],
            $this->formatos(),
        );

        $metais = $this->materiaisUsados()
            ->map(static fn (Material $material): array => [
                'texto' => (string) $material->name,
                'ativo' => $material->getKey() === $produto->getAttribute('material_id'),
            ])
            ->all();

        $acabamentos = $this->acabamentosUsados()
            ->map(static fn (Finish $acabamento): array => [
                'texto' => (string) $acabamento->name,
                'ativo' => $acabamento->getKey() === $produto->getAttribute('finish_id'),
            ])
            ->all();

        $aros = $produto->variants
            ->where('is_active', true)
            ->sortBy(static fn ($variante): int => (int) $variante->getAttribute('ring_size'))
            ->map(static fn ($variante): array => ['texto' => 'Aro '.$variante->getAttribute('ring_size'), 'ativo' => false])
            ->values()
            ->all();

        $secoes = [
            ['rotulo' => 'Larguras disponíveis', 'nota' => null, 'itens' => $larguras],
            ['rotulo' => 'Feitios', 'nota' => null, 'itens' => $formatos],
            ['rotulo' => 'Metais', 'nota' => null, 'itens' => $metais],
            ['rotulo' => 'Acabamentos', 'nota' => 'Combinações fora da grade são produzidas sob encomenda para lojistas aprovados.', 'itens' => $acabamentos],
            ['rotulo' => 'Aros disponíveis', 'nota' => 'Aros fora da grade são produzidos sob encomenda.', 'itens' => $aros],
        ];

        return array_values(array_filter($secoes, static fn (array $secao): bool => $secao['itens'] !== []));
    }

    /**
     * Quem escolhe este modelo tambem leva. A afinidade vai afrouxando ate
     * encher os quatro cards do prototipo: mesma colecao, depois mesmo metal,
     * depois mesma categoria e, por fim, o resto do catalogo. Sem o degrau
     * final a secao aparecia com um card so — colecao pequena (Urbana tem dois
     * modelos) e metal exclusivo (so a Urbana Black usa aco) esgotam os dois
     * primeiros criterios.
     *
     * @return EloquentCollection<int, Product>
     */
    private function relacionados(Product $produto): EloquentCollection
    {
        /** @var EloquentCollection<int, Product> $escolhidos */
        $escolhidos = new EloquentCollection;

        /** @var list<callable(Builder<Product>): Builder<Product>> $afinidades */
        $afinidades = [
            fn (Builder $consulta): Builder => $consulta->where('collection_id', $produto->getAttribute('collection_id')),
            fn (Builder $consulta): Builder => $consulta->where('material_id', $produto->getAttribute('material_id')),
            fn (Builder $consulta): Builder => $consulta->where('category_id', $produto->getAttribute('category_id')),
            static fn (Builder $consulta): Builder => $consulta,
        ];

        foreach ($afinidades as $afinidade) {
            $faltam = self::RELACIONADOS - $escolhidos->count();

            if ($faltam <= 0) {
                break;
            }

            $lote = $afinidade($this->catalogo())
                ->whereKeyNot($produto->getKey())
                ->whereKeyNot($escolhidos->modelKeys())
                ->orderBy('id')
                ->limit($faltam)
                ->get();

            /** @var EloquentCollection<int, Product> $escolhidos */
            $escolhidos = $escolhidos->concat($lote);
        }

        return $escolhidos;
    }

    /**
     * Projecao publica com as relacoes que a grade e a ficha usam.
     *
     * @return Builder<Product>
     */
    private function catalogo(): Builder
    {
        return $this->visiveis()
            ->select(self::COLUNAS_PUBLICAS)
            ->with(['collection', 'category', 'material', 'finish', 'images', 'variants']);
    }

    /**
     * O catalogo publico e o catalogo da fabrica: peca ativa, dentro de uma
     * colecao e com slug — sem slug nao ha rota de detalhe para linkar.
     *
     * @return Builder<Product>
     */
    private function visiveis(): Builder
    {
        return Product::query()
            ->where('is_active', true)
            ->whereNotNull('collection_id')
            ->whereNotNull('slug');
    }

    /**
     * Materiais com ao menos um produto visivel — assim o filtro nunca oferece
     * uma opcao que devolve grade vazia.
     *
     * @return EloquentCollection<int, Material>
     */
    private function materiaisUsados(): EloquentCollection
    {
        return Material::query()
            ->whereHas('products', static function ($produto): void {
                $produto->where('is_active', true)->whereNotNull('collection_id')->whereNotNull('slug');
            })
            ->orderBy('position')
            ->orderBy('name')
            ->get();
    }

    /**
     * Acabamentos com ao menos um produto visivel.
     *
     * @return EloquentCollection<int, Finish>
     */
    private function acabamentosUsados(): EloquentCollection
    {
        return Finish::query()
            ->whereHas('products', static function ($produto): void {
                $produto->where('is_active', true)->whereNotNull('collection_id')->whereNotNull('slug');
            })
            ->orderBy('position')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return list<float>
     */
    private function larguras(): array
    {
        return $this->visiveis()
            ->whereNotNull('width_mm')
            ->distinct()
            ->orderBy('width_mm')
            ->pluck('width_mm')
            ->map(static fn (mixed $mm): float => (float) $mm)
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function formatos(): array
    {
        return $this->visiveis()
            ->whereNotNull('shape')
            ->distinct()
            ->orderBy('shape')
            ->pluck('shape')
            ->map(static fn (mixed $formato): string => (string) $formato)
            ->values()
            ->all();
    }

    /**
     * Faixa de aros derivada das variantes, quando `meta` nao traz a frase pronta.
     */
    private function faixaDeAros(Product $produto): ?string
    {
        $aros = $produto->variants
            ->where('is_active', true)
            ->map(static fn ($variante): int => (int) $variante->getAttribute('ring_size'))
            ->filter(static fn (int $aro): bool => $aro > 0)
            ->sort()
            ->values();

        if ($aros->isEmpty()) {
            return null;
        }

        return sprintf('Aro %d ao %d', (int) $aros->first(), (int) $aros->last());
    }

    /**
     * Numero no padrao pt-BR e sem zero a direita: 5.00 vira `5`, 4.50 vira `4,5`.
     */
    private function numero(float $valor): string
    {
        return rtrim(rtrim(number_format($valor, 2, ',', ''), '0'), ',');
    }

    /**
     * Mesma poda de zeros, mas com ponto decimal: e o valor que vai e volta pela
     * query string, entao precisa passar pela regra `numeric` do Form Request.
     */
    private function numeroUrl(float $valor): string
    {
        return rtrim(rtrim(number_format($valor, 2, '.', ''), '0'), '.');
    }

    private function texto(mixed $valor): string
    {
        return is_scalar($valor) ? trim((string) $valor) : '';
    }
}
