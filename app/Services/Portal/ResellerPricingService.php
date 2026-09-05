<?php

/*
[Modulo: app/Services/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 2.7: margens do lojista, tabela de precos sobre o custo Velaro e o resumo por faixa de margem.
*/

namespace App\Services\Portal;

use App\Models\Finish;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\ResellerPriceRule;
use App\Models\ResellerPriceSetting;
use App\Support\ResellerScope;
use App\Support\ValorPtBr;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;

/**
 * A tela em que o lojista **vê quanto paga**.
 *
 * `products.price` é o custo B2B e no portal ele aparece — é a razão de ser
 * desta tela. O que não pode vazar é a margem de um lojista para outro: a regra
 * de preço é segredo comercial dele, e por isso tanto a configuração
 * (`reseller_price_settings`, 1:1 com o revendedor) quanto as exceções
 * (`reseller_price_rules`) só são alcançadas pelo {@see ResellerScope}.
 *
 * O catálogo, ao contrário, é da Velaro e é o mesmo para todo mundo — o que
 * muda de lojista para lojista é o preço resolvido em cima dele.
 */
class ResellerPricingService
{
    /** Abas do card da tabela, na ordem da seção 5 da tela 2.7. */
    public const TAB_PRODUCTS = 'produtos';

    public const TAB_COLLECTIONS = 'colecoes';

    public const TAB_RULES = 'regras';

    /** @var list<string> */
    public const TABS = [self::TAB_PRODUCTS, self::TAB_COLLECTIONS, self::TAB_RULES];

    /**
     * Rótulos das abas, na ordem da seção 5.
     *
     * @var array<string, string>
     */
    public const TAB_LABELS = [
        self::TAB_PRODUCTS => 'Todos os produtos',
        self::TAB_COLLECTIONS => 'Por coleção',
        self::TAB_RULES => 'Regras de margem',
    ];

    /** "5 por página" no rodapé da tabela do protótipo. */
    public const PER_PAGE_DEFAULT = 5;

    /** @var list<int> */
    public const PER_PAGE_OPTIONS = [5, 10, 25, 50];

    /**
     * Rótulos das políticas de arredondamento. A chave é a constante do model —
     * a view nunca escreve `'up_099'` na mão.
     *
     * @var array<string, string>
     */
    public const ROUNDING_LABELS = [
        ResellerPriceSetting::ROUNDING_NONE => 'Sem arredondamento',
        ResellerPriceSetting::ROUNDING_UP_099 => 'Para cima (0,99)',
        ResellerPriceSetting::ROUNDING_UP_090 => 'Para cima (0,90)',
        ResellerPriceSetting::ROUNDING_UP_INTEGER => 'Para cima (inteiro)',
        ResellerPriceSetting::ROUNDING_NEAREST_10 => 'Para cima (múltiplo de 10)',
    ];

    /** @var array<string, string> */
    public const RULE_SCOPE_LABELS = [
        ResellerPriceSetting::RULE_SCOPE_GLOBAL => 'Global',
        ResellerPriceSetting::RULE_SCOPE_COLLECTION => 'Por coleção',
        ResellerPriceSetting::RULE_SCOPE_PRODUCT => 'Por produto',
    ];

    /** @var array<string, string> */
    public const PRICING_MODEL_LABELS = [
        ResellerPriceSetting::PRICING_MODEL_MULTIPLIER => 'Multiplicador',
        ResellerPriceSetting::PRICING_MODEL_PERCENT => 'Percentual',
    ];

    /** @var array<string, string> */
    public const RULE_MODE_LABELS = [
        ResellerPriceRule::MODE_MULTIPLIER => 'Multiplicador',
        ResellerPriceRule::MODE_PERCENT => 'Percentual de margem',
        ResellerPriceRule::MODE_MANUAL => 'Preço manual',
        ResellerPriceRule::MODE_PROMO => 'Preço promocional',
    ];

    /**
     * Faixas do painel "Resumo de margens", com o chip que cada uma usa na
     * coluna Status da tabela.
     *
     * @var array<string, array{rotulo: string, chip: string}>
     */
    public const STATUS_LABELS = [
        ResellerPriceResolver::STATUS_IDEAL => ['rotulo' => 'Margem ideal', 'chip' => 'chip--ok'],
        ResellerPriceResolver::STATUS_LOW => ['rotulo' => 'Margem baixa', 'chip' => 'chip--warn'],
        ResellerPriceResolver::STATUS_CRITICAL => ['rotulo' => 'Margem crítica', 'chip' => 'chip--danger'],
    ];

    /**
     * Padrões da configuração ainda não salva — os mesmos defaults da migration.
     * Sem eles o formulário abriria com margem e multiplicador vazios, e o KPI
     * de margem média dividiria por um multiplicador nulo.
     *
     * @var array<string, string|float|bool>
     */
    private const DEFAULTS = [
        'pricing_model' => ResellerPriceSetting::PRICING_MODEL_MULTIPLIER,
        'multiplier' => 3.60,
        'margin_global' => 50.00,
        'margin_min' => 40.00,
        'margin_ideal' => 50.00,
        'margin_max' => 60.00,
        'rounding' => ResellerPriceSetting::ROUNDING_UP_099,
        'rule_scope' => ResellerPriceSetting::RULE_SCOPE_GLOBAL,
        'apply_to_all' => true,
        'allow_manual_override' => true,
        'allow_promotional_prices' => true,
    ];

    /**
     * Configuração de preço do lojista. É 1:1 com o revendedor, e quem chega na
     * tela sem nunca ter salvo precisa ver os padrões — não um erro. Os valores
     * são os defaults da migration (multiplicador 3,6x, margem global 50%), os
     * mesmos que o protótipo mostra.
     */
    public function configuracao(ResellerScope $escopo): ResellerPriceSetting
    {
        $configuracao = $escopo->reseller->priceSetting()->first();

        if ($configuracao instanceof ResellerPriceSetting) {
            return $configuracao;
        }

        // `make` e não `create`: abrir a tela é leitura, e uma linha nova a cada
        // GET sujaria a base de quem só passou pela tela. A gravação acontece no
        // update, e é lá que a linha nasce.
        $novo = $escopo->reseller->priceSetting()->make();
        $novo->forceFill(self::DEFAULTS);
        $novo->reseller_id = $escopo->reseller->getKey();

        return $novo;
    }

    /**
     * Resolvedor já carregado com as regras do lojista — uma consulta só para a
     * tabela inteira, em vez de uma por produto.
     */
    public function resolvedor(ResellerScope $escopo): ResellerPriceResolver
    {
        /** @var EloquentCollection<int, ResellerPriceRule> $regras */
        $regras = $escopo->priceRules()->where('is_active', true)->get();

        return new ResellerPriceResolver($this->configuracao($escopo), $regras);
    }

    /**
     * Tudo o que a tela 2.7 precisa, já resolvido.
     *
     * @param  array{q: string|null, colecao: string|null, material: string|null, acabamento: string|null, aba: string, por_pagina: int}  $filtros
     * @return array<string, mixed>
     */
    public function montarTela(ResellerScope $escopo, array $filtros): array
    {
        $configuracao = $this->configuracao($escopo);
        $resolvedor = $this->resolvedor($escopo);

        // Os KPIs e o donut descrevem o catálogo inteiro; os filtros só estreitam
        // a tabela. Filtrar os dois faria "margem média atual" mudar de sentido a
        // cada busca.
        $catalogo = $this->catalogo()->get();
        $resumo = $this->resumo($catalogo, $resolvedor);

        $pagina = $this->catalogo($filtros)->paginate(
            $filtros['por_pagina'],
            ['*'],
            'page'
        )->withQueryString();

        return [
            'configuracao' => $configuracao,
            'kpis' => $this->kpis($resumo, $configuracao),
            'resumo' => $resumo,
            'linhas' => $this->linhas($pagina->getCollection(), $resolvedor),
            'produtos' => $pagina,
            'colecoes' => $this->porColecao($catalogo, $resolvedor),
            'regras' => $this->regras($escopo),
            'opcoes' => $this->opcoesDeFiltro($filtros),
            'filtros' => $filtros,

            // Rótulos e constantes que a view precisa nomear sem escrever nenhuma
            // string de enum na mão.
            'arredondamentos' => self::ROUNDING_LABELS,
            'alcances' => self::RULE_SCOPE_LABELS,
            'abas' => self::TAB_LABELS,
            'abaProdutos' => self::TAB_PRODUCTS,
            'abaColecoes' => self::TAB_COLLECTIONS,
            'porPaginaOpcoes' => self::PER_PAGE_OPTIONS,

            // Percentuais já formatados: a legenda do donut usa as faixas do
            // próprio lojista, não os 40% e 20% do protótipo.
            'margemMediaFmt' => ValorPtBr::percentual($resumo['margem_media']),
            'margemMinimaFmt' => ValorPtBr::percentual((float) $configuracao->margin_min, 0),
            'margemCriticaFmt' => ValorPtBr::percentual(ResellerPriceResolver::CRITICAL_MARGIN, 0),
        ];
    }

    /**
     * Grava a configuração de preço e mantém a regra global em sincronia.
     *
     * A regra `scope = global` é a forma consultável do que a tela chama de
     * "modelo de precificação": o resolvedor lê regras, não a configuração, e
     * deixar as duas divergirem faria a vitrine praticar um preço diferente do
     * que esta tela mostra. Por isso as duas gravações são uma só operação.
     *
     * @param  array<string, mixed>  $dados
     */
    public function atualizar(ResellerScope $escopo, array $dados, bool $recalcular = false): ResellerPriceSetting
    {
        $configuracao = $this->configuracao($escopo);

        $configuracao->fill($dados);
        $configuracao->reseller_id = $escopo->reseller->getKey();

        if ($recalcular) {
            $configuracao->recalculated_at = Carbon::now();
        }

        $configuracao->save();

        $this->sincronizarRegraGlobal($escopo, $configuracao);

        return $configuracao;
    }

    /**
     * A regra global espelha o modelo escolhido: multiplicador vira
     * `MODE_MULTIPLIER` com o fator, percentual vira `MODE_PERCENT` com a margem.
     */
    private function sincronizarRegraGlobal(ResellerScope $escopo, ResellerPriceSetting $configuracao): void
    {
        $porMultiplicador = (string) $configuracao->pricing_model === ResellerPriceSetting::PRICING_MODEL_MULTIPLIER;

        $escopo->priceRules()->updateOrCreate(
            [
                'scope' => ResellerPriceRule::SCOPE_GLOBAL,
                'collection_id' => null,
                'product_id' => null,
            ],
            [
                'mode' => $porMultiplicador ? ResellerPriceRule::MODE_MULTIPLIER : ResellerPriceRule::MODE_PERCENT,
                'value' => $porMultiplicador ? (float) $configuracao->multiplier : (float) $configuracao->margin_global,
                'rounding' => (string) $configuracao->rounding,
                'priority' => 0,
                'is_active' => true,
            ],
        );
    }

    /**
     * Catálogo Velaro ativo — o mesmo para todos os lojistas. Só o preço muda.
     *
     * @param  array{q?: string|null, colecao?: string|null, material?: string|null, acabamento?: string|null}  $filtros
     * @return Builder<Product>
     */
    private function catalogo(array $filtros = []): Builder
    {
        $consulta = Product::query()
            ->with(['collection', 'material', 'finish'])
            ->where('is_active', true)
            ->orderBy('name');

        $busca = $filtros['q'] ?? null;

        if (is_string($busca) && $busca !== '') {
            $consulta->where(function (Builder $interna) use ($busca): void {
                $interna->where('name', 'like', '%'.$busca.'%')
                    ->orWhere('sku', 'like', '%'.$busca.'%');
            });
        }

        foreach (['colecao' => 'collection', 'material' => 'material', 'acabamento' => 'finish'] as $filtro => $relacao) {
            $slug = $filtros[$filtro] ?? null;

            if (is_string($slug) && $slug !== '') {
                $consulta->whereHas($relacao, static fn (Builder $interna): Builder => $interna->where('slug', $slug));
            }
        }

        return $consulta;
    }

    /**
     * Linha da tabela de preços, com tudo já calculado e formatado.
     *
     * @param  EloquentCollection<int, Product>  $produtos
     * @return list<array<string, mixed>>
     */
    private function linhas(EloquentCollection $produtos, ResellerPriceResolver $resolvedor): array
    {
        return $produtos->map(function (Product $produto) use ($resolvedor): array {
            $preco = $resolvedor->resolve($produto);
            $faixa = self::STATUS_LABELS[$preco['status']];

            return [
                'id' => (int) $produto->getKey(),
                'nome' => (string) $produto->name,
                'referencia' => 'Ref. '.(string) $produto->sku,
                'colecao' => (string) ($produto->collection->name ?? '—'),
                'custo' => $preco['cost'],
                'custo_fmt' => ValorPtBr::moeda($preco['cost']),
                'margem' => $preco['margin'],
                'margem_fmt' => ValorPtBr::percentual($preco['margin']),
                'markup' => $preco['markup'],
                'markup_fmt' => ValorPtBr::percentual($preco['markup']),
                'preco' => $preco['price'],
                'preco_fmt' => ValorPtBr::moeda($preco['price']),
                'status' => $preco['status'],
                'status_rotulo' => $faixa['rotulo'],
                'status_chip' => $faixa['chip'],
                'origem' => $preco['origin'],
            ];
        })->values()->all();
    }

    /**
     * Contagem por faixa de margem e as médias do catálogo — o donut e a legenda
     * do painel "Resumo de margens".
     *
     * @param  EloquentCollection<int, Product>  $catalogo
     * @return array{total: int, ideal: int, baixa: int, critica: int, abaixo_do_ideal: int, margem_media: float, markup_medio: float, preco_medio: float}
     */
    private function resumo(EloquentCollection $catalogo, ResellerPriceResolver $resolvedor): array
    {
        $faixas = [
            ResellerPriceResolver::STATUS_IDEAL => 0,
            ResellerPriceResolver::STATUS_LOW => 0,
            ResellerPriceResolver::STATUS_CRITICAL => 0,
        ];

        $margens = [];
        $markups = [];
        $precos = [];

        foreach ($catalogo as $produto) {
            $preco = $resolvedor->resolve($produto);

            $faixas[$preco['status']]++;
            $margens[] = $preco['margin'];
            $markups[] = $preco['markup'];
            $precos[] = $preco['price'];
        }

        return [
            'total' => $catalogo->count(),
            'ideal' => $faixas[ResellerPriceResolver::STATUS_IDEAL],
            'baixa' => $faixas[ResellerPriceResolver::STATUS_LOW],
            'critica' => $faixas[ResellerPriceResolver::STATUS_CRITICAL],
            // O KPI "produtos com margem abaixo do ideal" soma as duas faixas que
            // não alcançam a margem mínima do lojista.
            'abaixo_do_ideal' => $faixas[ResellerPriceResolver::STATUS_LOW] + $faixas[ResellerPriceResolver::STATUS_CRITICAL],
            'margem_media' => $this->media($margens),
            'markup_medio' => $this->media($markups),
            'preco_medio' => $this->media($precos),
        ];
    }

    /**
     * Os cinco KPIs do topo da tela.
     *
     * @param  array{total: int, ideal: int, baixa: int, critica: int, abaixo_do_ideal: int, margem_media: float, markup_medio: float, preco_medio: float}  $resumo
     * @return list<array{rotulo: string, valor: string, nota: string, icone: string, tom: string}>
     */
    private function kpis(array $resumo, ResellerPriceSetting $configuracao): array
    {
        $atualizado = $configuracao->recalculated_at ?? $configuracao->updated_at;

        return [
            [
                'rotulo' => 'Margem média atual',
                'valor' => ValorPtBr::percentual($resumo['margem_media']),
                'nota' => 'Sobre o preço de venda',
                'icone' => 'chart',
                'tom' => 'kpi__icon--ok',
            ],
            [
                'rotulo' => 'Markup médio',
                'valor' => ValorPtBr::percentual($resumo['markup_medio']),
                'nota' => 'Sobre o custo',
                'icone' => 'coin',
                'tom' => 'kpi__icon--gold',
            ],
            [
                'rotulo' => 'Produtos com margem abaixo do ideal',
                'valor' => (string) $resumo['abaixo_do_ideal'],
                'nota' => 'Ajuste recomendado',
                'icone' => 'info',
                'tom' => 'kpi__icon--warn',
            ],
            [
                'rotulo' => 'Preço médio de venda',
                'valor' => ValorPtBr::moeda($resumo['preco_medio']),
                'nota' => 'Por unidade',
                'icone' => 'tag',
                'tom' => 'kpi__icon--info',
            ],
            [
                'rotulo' => 'Atualizado em',
                'valor' => $atualizado instanceof Carbon ? $atualizado->format('d/m/Y H:i') : '—',
                'nota' => 'Última atualização',
                'icone' => 'clock',
                'tom' => 'kpi__icon--violet',
            ],
        ];
    }

    /**
     * Aba "Por coleção": a mesma conta, agrupada.
     *
     * @param  EloquentCollection<int, Product>  $catalogo
     * @return list<array<string, mixed>>
     */
    private function porColecao(EloquentCollection $catalogo, ResellerPriceResolver $resolvedor): array
    {
        $grupos = [];

        foreach ($catalogo as $produto) {
            $chave = (string) ($produto->collection->name ?? 'Sem coleção');
            $preco = $resolvedor->resolve($produto);

            $grupos[$chave] ??= ['custos' => [], 'margens' => [], 'precos' => []];
            $grupos[$chave]['custos'][] = $preco['cost'];
            $grupos[$chave]['margens'][] = $preco['margin'];
            $grupos[$chave]['precos'][] = $preco['price'];
        }

        ksort($grupos);

        $linhas = [];

        foreach ($grupos as $nome => $valores) {
            $linhas[] = [
                'colecao' => $nome,
                'produtos' => count($valores['custos']),
                'custo_medio_fmt' => ValorPtBr::moeda($this->media($valores['custos'])),
                'margem_media' => $this->media($valores['margens']),
                'margem_media_fmt' => ValorPtBr::percentual($this->media($valores['margens'])),
                'preco_medio_fmt' => ValorPtBr::moeda($this->media($valores['precos'])),
            ];
        }

        return $linhas;
    }

    /**
     * Aba "Regras de margem": as exceções cadastradas para este lojista — e só
     * para ele.
     *
     * @return list<array<string, mixed>>
     */
    private function regras(ResellerScope $escopo): array
    {
        /** @var EloquentCollection<int, ResellerPriceRule> $regras */
        $regras = $escopo->priceRules()
            ->with(['collection', 'product'])
            ->orderByDesc('priority')
            ->orderByDesc('id')
            ->get();

        return $regras->map(function (ResellerPriceRule $regra): array {
            $modo = (string) $regra->mode;
            $valor = (float) $regra->value;

            return [
                'escopo' => self::RULE_SCOPE_LABELS[(string) $regra->scope] ?? (string) $regra->scope,
                'alvo' => match ((string) $regra->scope) {
                    ResellerPriceRule::SCOPE_COLLECTION => (string) ($regra->collection->name ?? '—'),
                    ResellerPriceRule::SCOPE_PRODUCT => (string) ($regra->product->name ?? '—'),
                    default => 'Todo o catálogo',
                },
                'modo' => self::RULE_MODE_LABELS[$modo] ?? $modo,
                'valor' => match ($modo) {
                    ResellerPriceRule::MODE_MULTIPLIER => ValorPtBr::multiplicador($valor),
                    ResellerPriceRule::MODE_PERCENT => ValorPtBr::percentual($valor),
                    default => ValorPtBr::moeda($valor),
                },
                'prioridade' => (int) $regra->priority,
                'ativa' => (bool) $regra->is_active,
            ];
        })->values()->all();
    }

    /**
     * Opções dos três selects da barra de filtros, com o selecionado marcado.
     *
     * @param  array{colecao: string|null, material: string|null, acabamento: string|null}  $filtros
     * @return array<string, list<array{valor: string, rotulo: string, selecionado: bool}>>
     */
    private function opcoesDeFiltro(array $filtros): array
    {
        $colecoes = ProductCollection::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('name')
            ->get(['slug', 'name']);

        return [
            'colecoes' => $colecoes->map(static fn (ProductCollection $colecao): array => [
                'valor' => (string) $colecao->slug,
                'rotulo' => (string) $colecao->name,
                'selecionado' => $filtros['colecao'] === $colecao->slug,
            ])->values()->all(),
            'materiais' => $this->materiais($filtros['material']),
            'acabamentos' => $this->acabamentos($filtros['acabamento']),
        ];
    }

    /**
     * @return list<array{valor: string, rotulo: string, selecionado: bool}>
     */
    private function materiais(?string $selecionado): array
    {
        return Material::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('name')
            ->get(['slug', 'name'])
            ->map(static fn (Material $linha): array => [
                'valor' => (string) $linha->slug,
                'rotulo' => (string) $linha->name,
                'selecionado' => $selecionado === $linha->slug,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{valor: string, rotulo: string, selecionado: bool}>
     */
    private function acabamentos(?string $selecionado): array
    {
        return Finish::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('name')
            ->get(['slug', 'name'])
            ->map(static fn (Finish $linha): array => [
                'valor' => (string) $linha->slug,
                'rotulo' => (string) $linha->name,
                'selecionado' => $selecionado === $linha->slug,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<float>  $valores
     */
    private function media(array $valores): float
    {
        if ($valores === []) {
            return 0.0;
        }

        return round(array_sum($valores) / count($valores), 2);
    }
}
