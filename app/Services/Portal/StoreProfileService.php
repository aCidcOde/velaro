<?php

/*
[Modulo: app/Services/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 2.6: identidade da vitrine do lojista, publicacao e a previa pintada pelos proprios campos.
*/

namespace App\Services\Portal;

use App\Models\Product;
use App\Models\ResellerStore;
use App\Support\ResellerScope;
use App\Support\ValorPtBr;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A vitrine white label do lojista: **a única fonte de pintura da loja**
 * (regra 1 da tela 2.6). As quatro cores, a logo, o banner e os toggles saem
 * daqui e de mais lugar nenhum — a vitrine em `/loja/{slug}` lê esta linha.
 *
 * `reseller_stores.reseller_id` é UNIQUE: é sempre uma loja por lojista, ou
 * nenhuma. Quem nunca salvou vê o formulário pré-preenchido a partir do próprio
 * cadastro, e a linha só nasce no primeiro `PUT` — abrir a tela não cria loja.
 */
class StoreProfileService
{
    /** Quantos produtos a prévia "Destaques" mostra — os 8 do protótipo. */
    public const PREVIEW_PRODUCTS = 8;

    /**
     * Os cinco toggles da tela, com o texto que o protótipo dá a cada um. A
     * ordem é a da seção 5: os dois primeiros no bloco de identidade, os demais
     * governam como a vitrine se comporta na venda.
     *
     * @var array<string, array{titulo: string, ajuda: string}>
     */
    public const TOGGLES = [
        'own_brand_only' => [
            'titulo' => 'Exibir apenas a marca da minha loja para o cliente final',
            'ajuda' => 'Sua vitrine será exibida somente com a marca da sua loja',
        ],
        'hide_supplier_brand' => [
            'titulo' => 'Ocultar marca do fornecedor',
            'ajuda' => 'Remover qualquer menção à Velaro Alianças',
        ],
        'show_prices' => [
            'titulo' => 'Exibir preços na vitrine',
            'ajuda' => 'Desligado, o cliente consulta o valor com a sua equipe',
        ],
        'pickup_only' => [
            'titulo' => 'Somente retirada na loja',
            'ajuda' => 'A vitrine não oferece entrega: o cliente retira no balcão',
        ],
        'payment_in_store' => [
            'titulo' => 'Pagamento realizado na loja',
            'ajuda' => 'A vitrine não processa pagamento online',
        ],
    ];

    /**
     * Custos da tabela "Exemplo de cálculo" do bloco ②. São valores redondos de
     * demonstração, os mesmos do protótipo — não saem do catálogo.
     *
     * @var list<float>
     */
    public const EXAMPLE_COSTS = [500.0, 1000.0, 2000.0];

    /**
     * Padrões da vitrine ainda não salva. São os mesmos defaults da migration:
     * sem eles o formulário abriria com os quatro seletores de cor vazios, e um
     * `<input type="color">` sem valor não tem cor nenhuma para mostrar.
     *
     * @var array<string, string|bool>
     */
    private const DEFAULTS = [
        'color_primary' => '#800020',
        'color_secondary' => '#B8860B',
        'color_background' => '#FFFFFF',
        'color_text' => '#1A1A1A',
        'own_brand_only' => false,
        'hide_supplier_brand' => false,
        'show_prices' => true,
        'pickup_only' => true,
        'payment_in_store' => true,
        'is_active' => false,
    ];

    /**
     * Os quatro campos de cor, na ordem do bloco "Cores da marca".
     *
     * @var array<string, string>
     */
    public const COLORS = [
        'color_primary' => 'Primária',
        'color_secondary' => 'Secundária',
        'color_background' => 'Fundo',
        'color_text' => 'Texto',
    ];

    /**
     * A loja do lojista — a existente, ou uma ainda não salva já pré-preenchida
     * com o que o cadastro do revendedor sabe. Sem isso o primeiro acesso abriria
     * um formulário totalmente em branco, e o nome da loja e o telefone já são
     * conhecidos desde a aprovação.
     */
    public function loja(ResellerScope $escopo): ResellerStore
    {
        $loja = $escopo->store();

        if ($loja instanceof ResellerStore) {
            return $loja;
        }

        $revendedor = $escopo->reseller;
        $nome = (string) ($revendedor->trade_name ?? $revendedor->legal_name);

        $nova = new ResellerStore;
        $nova->forceFill(self::DEFAULTS);
        $nova->reseller_id = $revendedor->getKey();
        $nova->name = $nome;
        $nova->slug = $this->slugDisponivel($nome, null);
        $nova->phone = $revendedor->phone;
        $nova->whatsapp = $revendedor->getAttribute('whatsapp');
        $nova->email = $revendedor->getAttribute('email');
        $nova->address = $this->enderecoDoCadastro($escopo);

        return $nova;
    }

    /**
     * Grava a identidade da vitrine.
     *
     * Publicar é a mesma escrita com mais dois campos: `is_active` e
     * `published_at`. O protótipo tem dois botões — "Salvar configurações" e
     * "Publicar vitrine" — mas uma rota só (`PUT /portal/loja`), e é o intent que
     * chega no corpo do formulário que separa os dois.
     *
     * @param  array<string, mixed>  $dados
     */
    public function atualizar(ResellerScope $escopo, array $dados, bool $publicar = false): ResellerStore
    {
        $loja = $this->loja($escopo);

        $loja->fill($dados);
        $loja->reseller_id = $escopo->reseller->getKey();

        if ($publicar) {
            $loja->is_active = true;
            // Republicar não reescreve a data da primeira publicação: ela é a
            // marca de quando a loja entrou no ar.
            $loja->published_at ??= Carbon::now();
        }

        $loja->save();

        return $loja;
    }

    /**
     * Slug livre a partir do nome. `reseller_stores.slug` é UNIQUE e é a URL
     * pública da loja (`/loja/{slug}`): colidir com a loja de outro lojista
     * derrubaria a gravação com erro de banco em vez de erro de formulário.
     */
    public function slugDisponivel(string $nome, ?int $ignorar): string
    {
        $base = Str::slug($nome);
        $base = $base === '' ? 'minha-loja' : $base;
        $candidato = $base;
        $sufixo = 2;

        while ($this->slugEmUso($candidato, $ignorar)) {
            $candidato = $base.'-'.$sufixo;
            $sufixo++;
        }

        return $candidato;
    }

    /**
     * As quatro cores no formato que a vitrine consome (`--shop-*`).
     *
     * @return array<string, string>
     */
    public function paleta(ResellerStore $loja): array
    {
        return [
            '--shop-primary' => (string) $loja->color_primary,
            '--shop-secondary' => (string) $loja->color_secondary,
            '--shop-bg' => (string) $loja->color_background,
            // O token da vitrine chama-se `--shop-text` (ver resources/css/velaro/vitrine.css):
            // errar o nome aqui deixaria a loja com o texto do tema padrão.
            '--shop-text' => (string) $loja->color_text,
        ];
    }

    /**
     * Estilo inline da prévia, já montado — a view só imprime.
     */
    public function estiloDaPaleta(ResellerStore $loja): string
    {
        $partes = [];

        foreach ($this->paleta($loja) as $token => $valor) {
            $partes[] = $token.':'.$valor;
        }

        return implode(';', $partes);
    }

    /**
     * A prévia "assim o cliente verá sua vitrine" (regra 2 da tela 2.6).
     *
     * O preço que aparece aqui é o **preço ao consumidor**, resolvido pelas
     * regras do próprio lojista — nunca o custo Velaro. Com `show_prices`
     * desligado nem esse valor sai: a prévia mostra o que a vitrine mostraria.
     *
     * @return list<array{nome: string, material: string, preco: string|null, chip: string}>
     */
    public function previa(ResellerScope $escopo, ResellerStore $loja, ResellerPriceResolver $resolvedor): array
    {
        $produtos = $this->produtosDaPrevia($loja);
        $mostraPreco = (bool) $loja->show_prices;

        return $produtos->map(function (Product $produto) use ($resolvedor, $mostraPreco, $loja): array {
            return [
                'nome' => (string) $produto->name,
                'material' => (string) ($produto->material->name ?? 'Ouro 18k'),
                'preco' => $mostraPreco ? ValorPtBr::moeda($resolvedor->resolve($produto)['price']) : null,
                // O chip do protótipo alterna entre as duas frases: peça pronta
                // o cliente retira, peça sob encomenda ele encomenda no balcão.
                'chip' => $produto->is_made_to_order || ! $loja->pickup_only
                    ? 'Pedido realizado na loja'
                    : 'Retirada na loja',
            ];
        })->values()->all();
    }

    /**
     * A tabela "Exemplo de cálculo com multiplicador 3,6x" do bloco ②.
     *
     * A conta é do resolvedor, não da view: se o lojista trocar para o modelo
     * percentual, o exemplo passa a mostrar a margem — e continua sendo o mesmo
     * cálculo que a vitrine faz.
     *
     * @return list<array{custo: string, fator: string, preco: string}>
     */
    public function exemplosDeCalculo(ResellerPriceResolver $resolvedor, string $fator): array
    {
        $linhas = [];

        foreach (self::EXAMPLE_COSTS as $custo) {
            $linhas[] = [
                'custo' => ValorPtBr::moeda($custo),
                'fator' => $fator,
                'preco' => ValorPtBr::moeda($resolvedor->precoPadrao($custo)),
            ];
        }

        return $linhas;
    }

    /**
     * Destaques da própria loja quando o lojista já escolheu; senão, o começo do
     * catálogo — a prévia nunca fica vazia.
     *
     * @return EloquentCollection<int, Product>
     */
    private function produtosDaPrevia(ResellerStore $loja): EloquentCollection
    {
        if ($loja->exists) {
            /** @var EloquentCollection<int, Product> $destaques */
            $destaques = $loja->products()
                ->with('material')
                ->where('products.is_active', true)
                ->wherePivot('is_featured', true)
                ->orderBy('reseller_store_products.position')
                ->limit(self::PREVIEW_PRODUCTS)
                ->get();

            if ($destaques->isNotEmpty()) {
                return $destaques;
            }
        }

        /** @var EloquentCollection<int, Product> $catalogo */
        $catalogo = Product::query()
            ->with('material')
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(self::PREVIEW_PRODUCTS)
            ->get();

        return $catalogo;
    }

    private function slugEmUso(string $slug, ?int $ignorar): bool
    {
        $consulta = ResellerStore::query()->where('slug', $slug);

        if ($ignorar !== null) {
            $consulta->whereKeyNot($ignorar);
        }

        return $consulta->exists();
    }

    /**
     * Endereço da loja a partir do cadastro do revendedor, na forma em que a
     * tela 2.6 o mostra: `Rua das Alianças, 123 - Centro, São Paulo - SP`.
     */
    private function enderecoDoCadastro(ResellerScope $escopo): ?string
    {
        $revendedor = $escopo->reseller;

        $logradouro = collect([$revendedor->street, $revendedor->street_number])
            ->filter(static fn (?string $parte): bool => $parte !== null && $parte !== '')
            ->implode(', ');

        $cidade = collect([$revendedor->city, $revendedor->state])
            ->filter(static fn (?string $parte): bool => $parte !== null && $parte !== '')
            ->implode(' - ');

        $endereco = collect([$logradouro, $revendedor->district, $cidade])
            ->filter(static fn (string $parte): bool => $parte !== '')
            ->implode(', ');

        return $endereco === '' ? null : $endereco;
    }
}
