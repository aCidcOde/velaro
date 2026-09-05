<?php

/*
[Modulo: database/seeders]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Semeia o conteudo que o site publico Velaro le: configuracoes, taxonomia, cofre padrao e catalogo demo.
*/

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Finish;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\StockItem;
use App\Models\StockLocation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Conteúdo mínimo do Ambiente 1 (site público). Sem estas linhas a home, o catálogo
 * e o rodapé renderizam em branco: `settings` alimenta telefone/e-mail/horário e o
 * texto institucional da 1.2, `collections` alimenta a vitrine de coleções da 1.1 e
 * a taxonomia alimenta os filtros da 1.3.
 *
 * Idempotente por chave natural — `settings.key`, o slug de cada taxonomia,
 * `stock_locations.code`, `products.sku`, o par (produto, aro) da variante,
 * o par (produto, posição) da imagem e o par (variante, cofre) do saldo.
 * Rodar duas vezes atualiza as mesmas linhas e não cria nenhuma nova.
 *
 * `products.price` é custo B2B: entra aqui porque o Portal e o Master precisam dele,
 * e nunca é serializado nas rotas do site (regra 1 do escopo 1.3).
 */
class VelaroSeeder extends Seeder
{
    /**
     * Tabelas conferidas no resumo impresso ao fim da execução.
     *
     * @var list<string>
     */
    private const TABELAS_CONFERIDAS = [
        'settings',
        'collections',
        'categories',
        'materials',
        'finishes',
        'stock_locations',
        'products',
        'product_variants',
        'product_images',
        'stock_items',
    ];

    public function run(): void
    {
        $this->semearConfiguracoes();

        $colecoes = $this->semearColecoes();
        $categorias = $this->semearCategorias();
        $materiais = $this->semearMateriais();
        $acabamentos = $this->semearAcabamentos();
        $cofre = $this->semearCofrePadrao();

        $this->semearCatalogo($colecoes, $categorias, $materiais, $acabamentos, $cofre);

        $this->imprimirContagens();
    }

    /**
     * Configurações lidas pelo site público: dados da empresa, atendimento do rodapé,
     * texto institucional da 1.2 e os parâmetros de gravação.
     *
     * Fonte dos textos: docs/telas/1-2-site-sobre.md §5 e docs/mockups/10-site-sobre.html.
     */
    private function semearConfiguracoes(): void
    {
        foreach ($this->configuracoes() as [$grupo, $chave, $valor, $tipo]) {
            $atributos = Setting::factory()->publicSetting()->raw([
                'group' => $grupo,
                'key' => $chave,
                'value' => $valor,
                'type' => $tipo,
            ]);

            Setting::updateOrCreate(['key' => $chave], $atributos);
        }
    }

    /**
     * @return list<array{0: string, 1: string, 2: string, 3: string}>
     */
    private function configuracoes(): array
    {
        return [
            // ── Empresa ────────────────────────────────────────────────────────
            // Razão social, CNPJ e sede vêm da 17-site-privacidade.html §1, que é o
            // texto público da própria Velaro sobre a controladora dos dados.
            ['company', 'company.nome', 'Velaro Alianças', 'string'],
            ['company', 'company.razao_social', 'Velaro Alianças Ltda.', 'string'],
            ['company', 'company.cnpj', '45.123.456/0001-09', 'string'],
            ['company', 'company.endereco', 'Ribeirão Preto/SP', 'string'],

            // ── Atendimento (rodapé de todas as telas do site) ─────────────────
            ['contact', 'contact.telefone', '+55 (16) 99487-7800', 'string'],
            // Célula própria na barra de canais da 1.8; hoje é o mesmo número do
            // comercial, mas a chave existe para poder divergir sem tocar na tela.
            ['contact', 'contact.whatsapp', '+55 (16) 99487-7800', 'string'],
            ['contact', 'contact.email', 'vendas@velaro.com.br', 'string'],
            ['contact', 'contact.horario', 'Segunda a sexta, das 8h às 18h', 'string'],

            // ── Institucional (tela 1.2 · Sobre nós) ──────────────────────────
            ['about', 'about.hero_eyebrow', 'Quem é a Velaro', 'string'],
            ['about', 'about.hero_titulo', 'A excelência por trás da Velaro.', 'string'],
            ['about', 'about.hero_texto', 'A Velaro Alianças é uma marca especializada na fabricação e distribuição de alianças e joias de alta qualidade para lojistas em todo o Brasil. Unimos tradição, tecnologia e design para entregar produtos com acabamento impecável, prontos para valorizar sua vitrine e impulsionar suas vendas.', 'text'],

            ['about', 'about.historia_eyebrow', 'Nossa história', 'string'],
            ['about', 'about.historia_titulo', 'Feita para lojistas. Feita para durar.', 'string'],
            ['about', 'about.historia', "Nascemos com o propósito de oferecer alianças que unem beleza, resistência e significado. Com fábrica própria e controle de ponta a ponta, garantimos qualidade superior, prazos confiáveis e um atendimento próximo, pensado especialmente para o lojista.\n\nMais do que vender alianças, construímos parcerias de longo prazo com quem entende o valor de um produto que representa histórias.", 'text'],

            ['about', 'about.fabrica_propria', 'Produção 100% própria com tecnologia e mão de obra especializada.', 'text'],

            ['about', 'about.diferenciais', $this->json([
                ['titulo' => 'Fábrica própria', 'texto' => 'Produção 100% própria com tecnologia e mão de obra especializada.'],
                ['titulo' => 'Qualidade e acabamento superior', 'texto' => 'Matérias-primas selecionadas e acabamento impecável em cada detalhe.'],
                ['titulo' => 'Atendimento consultivo', 'texto' => 'Equipe especializada para entender seu negócio e oferecer as melhores soluções.'],
                ['titulo' => 'Entrega para todo o Brasil', 'texto' => 'Logística ágil e segura para atender lojistas de todas as regiões do país.'],
            ]), 'json'],

            ['about', 'about.negocio_eyebrow', 'Pensado para o seu negócio', 'string'],
            ['about', 'about.negocio_titulo', 'Pensado para abastecer vitrines com qualidade, consistência e confiança.', 'string'],
            ['about', 'about.negocio_texto', 'Cada detalhe da nossa operação é guiado por um compromisso: oferecer alianças que encantam seus clientes e fortalecem o seu negócio.', 'text'],

            ['about', 'about.numeros_titulo', 'Números que reforçam nossa essência', 'string'],
            ['about', 'about.numeros', $this->json([
                ['titulo' => 'Produção com padrão premium', 'texto' => 'Processos rigorosos e controle de qualidade em todas as etapas.'],
                ['titulo' => 'Atendimento nacional', 'texto' => 'Lojistas atendidos em todo o Brasil com agilidade e atendimento próximo.'],
                ['titulo' => 'Coleções para diferentes perfis de loja', 'texto' => 'Modelos que acompanham tendências e diferentes públicos.'],
                ['titulo' => 'Parceria focada em revenda', 'texto' => 'Condições exclusivas e suporte contínuo para impulsionar seus resultados.'],
            ]), 'json'],

            ['about', 'about.cta_titulo', 'Vamos crescer juntos?', 'string'],
            ['about', 'about.cta_texto', 'Faça seu cadastramento como lojista e tenha acesso às condições exclusivas da Velaro.', 'text'],

            // ── Gravação ──────────────────────────────────────────────────────
            // 20 caracteres e R$ 35,00 por peça, como no 16-site-produto.html e no
            // resumo do carrinho da 2.10.
            ['gravacao', 'gravacao.max_chars', '20', 'integer'],
            ['gravacao', 'gravacao.preco', '35.00', 'decimal'],
        ];
    }

    /**
     * As cinco coleções que a home (01-site-publico.html) exibe como vitrine.
     * A capa aponta para um SVG que existe em public/images/aliancas.
     *
     * @return array<string, ProductCollection>
     */
    private function semearColecoes(): array
    {
        $definicoes = [
            ['Clássica', 'Tradição que nunca sai de moda.', 'classica'],
            ['Diamond', 'Brilho que eterniza seus melhores momentos.', 'diamond'],
            ['Premium', 'Acabamentos sofisticados para ocasiões especiais.', 'premium'],
            ['Urbana', 'Estilo moderno para todos os dias.', 'urbana'],
            ['Personalizadas', 'Do seu jeito, com significado único.', 'personaliz'],
        ];

        $colecoes = [];

        foreach ($definicoes as $posicao => [$nome, $descricao, $arte]) {
            $atributos = ProductCollection::factory()->named($nome)->raw([
                'description' => $descricao,
                'cover_path' => $this->arte($arte),
                'position' => $posicao + 1,
            ]);

            $colecoes[$nome] = ProductCollection::updateOrCreate(
                ['slug' => $atributos['slug']],
                $atributos,
            );
        }

        return $colecoes;
    }

    /**
     * @return array<string, Category>
     */
    private function semearCategorias(): array
    {
        $categorias = [];

        foreach (['Alianças Tradicionais', 'Solitários', 'Acessórios'] as $posicao => $nome) {
            $atributos = Category::factory()->named($nome)->raw(['position' => $posicao + 1]);

            $categorias[$nome] = Category::updateOrCreate(
                ['slug' => $atributos['slug']],
                $atributos,
            );
        }

        return $categorias;
    }

    /**
     * @return array<string, Material>
     */
    private function semearMateriais(): array
    {
        $materiais = [];

        $nomes = ['Prata 950', 'Ouro Amarelo 18k', 'Ouro Rosé 18k', 'Ouro Branco 18k', 'Aço'];

        foreach ($nomes as $posicao => $nome) {
            $atributos = Material::factory()->named($nome)->raw(['position' => $posicao + 1]);

            $materiais[$nome] = Material::updateOrCreate(
                ['slug' => $atributos['slug']],
                $atributos,
            );
        }

        return $materiais;
    }

    /**
     * @return array<string, Finish>
     */
    private function semearAcabamentos(): array
    {
        $acabamentos = [];

        $nomes = ['Polida', 'Fosca', 'Diamantada', 'Cravejada', 'Texturizada', 'PVD Preto e Dourado'];

        foreach ($nomes as $posicao => $nome) {
            $atributos = Finish::factory()->named($nome)->raw(['position' => $posicao + 1]);

            $acabamentos[$nome] = Finish::updateOrCreate(
                ['slug' => $atributos['slug']],
                $atributos,
            );
        }

        return $acabamentos;
    }

    /**
     * Cofre padrão da matriz — destino de entrada quando o local não é informado.
     */
    private function semearCofrePadrao(): StockLocation
    {
        $codigo = 'MTZ-COFRE-A1';

        $atributos = StockLocation::factory()->defaultLocation()->raw([
            'code' => $codigo,
            'name' => 'Matriz - Cofre A1',
            'description' => 'Cofre A1 da matriz — peças acabadas prontas para expedição.',
        ]);

        return StockLocation::updateOrCreate(['code' => $codigo], $atributos);
    }

    /**
     * Os doze modelos transcritos em docs/telas/1-3-site-catalogo.md §5, com a
     * taxonomia completa, os aros, a foto de capa e o saldo em cofre.
     *
     * @param  array<string, ProductCollection>  $colecoes
     * @param  array<string, Category>  $categorias
     * @param  array<string, Material>  $materiais
     * @param  array<string, Finish>  $acabamentos
     */
    private function semearCatalogo(
        array $colecoes,
        array $categorias,
        array $materiais,
        array $acabamentos,
        StockLocation $cofre,
    ): void {
        foreach ($this->catalogo() as $indice => $modelo) {
            $produto = $this->semearProduto($modelo, $colecoes, $categorias, $materiais, $acabamentos);

            $this->semearCapa($produto, $modelo);
            $this->semearAros($produto, $indice, $cofre);
        }
    }

    /**
     * @param  array<string, mixed>  $modelo
     * @param  array<string, ProductCollection>  $colecoes
     * @param  array<string, Category>  $categorias
     * @param  array<string, Material>  $materiais
     * @param  array<string, Finish>  $acabamentos
     */
    private function semearProduto(
        array $modelo,
        array $colecoes,
        array $categorias,
        array $materiais,
        array $acabamentos,
    ): Product {
        $sku = (string) $modelo['sku'];
        $nome = (string) $modelo['nome'];
        $material = (string) $modelo['material'];
        $acabamento = (string) $modelo['acabamento'];
        $largura = (float) $modelo['largura_mm'];
        $formato = (string) $modelo['formato'];

        $atributos = Product::factory()->velaroCatalog()->raw([
            // O catálogo é da própria fábrica: não tem dono no `users`. A coluna
            // virou nulável na migração da taxonomia Velaro exatamente para isto.
            'user_id' => null,
            'name' => $nome,
            'slug' => Str::slug($nome),
            'sku' => $sku,
            'description' => $modelo['descricao'] ?? $this->descricaoPadrao($formato, $material, $acabamento, $largura),
            'collection_id' => ($colecoes[(string) $modelo['colecao']] ?? null)?->getKey(),
            'category_id' => ($categorias[(string) $modelo['categoria']] ?? null)?->getKey(),
            'material_id' => ($materiais[$material] ?? null)?->getKey(),
            'finish_id' => ($acabamentos[$acabamento] ?? null)?->getKey(),
            'width_mm' => $largura,
            'shape' => $formato,
            'allows_engraving' => true,
            'engraving_max_chars' => 20,
            'delivery_days' => 7,
            'is_made_to_order' => (bool) ($modelo['sob_encomenda'] ?? false),
            // Custo B2B cobrado do lojista. Nunca serializado nas rotas do site.
            'price' => $modelo['custo'],
            'is_active' => true,
            'meta' => [
                'peso_aproximado_g' => $modelo['peso_g'],
                'garantia_meses' => 12,
                'origem' => 'Fabricação própria Velaro',
                'aros_disponiveis' => '8 ao 34 — inclusive meios-aros',
            ],
        ]);

        return Product::updateOrCreate(['sku' => $sku], $atributos);
    }

    /**
     * Foto de capa do produto. Enquanto não há fotografia real, o arquivo é o mesmo
     * placeholder vetorial dos mockups, servido de public/images/aliancas.
     *
     * @param  array<string, mixed>  $modelo
     */
    private function semearCapa(Product $produto, array $modelo): void
    {
        $alt = sprintf(
            '%s — %s | %s · %smm',
            $modelo['nome'],
            $modelo['material'],
            $modelo['acabamento'],
            $this->largura((float) $modelo['largura_mm']),
        );

        $atributos = ProductImage::factory()->forProduct($produto)->primary()->raw([
            'path' => $this->arte((string) $modelo['arte']),
            'alt' => $alt,
        ]);

        ProductImage::updateOrCreate(
            ['product_id' => $produto->getKey(), 'position' => 0],
            $atributos,
        );
    }

    /**
     * De três a cinco aros por modelo, cada um com o próprio SKU e o saldo no cofre.
     * O ciclo de três grades mantém a contagem estável entre execuções.
     */
    private function semearAros(Product $produto, int $indice, StockLocation $cofre): void
    {
        $grades = [
            [14, 16, 18, 20, 22],
            [16, 18, 20, 22],
            [16, 18, 20],
        ];

        foreach ($grades[$indice % 3] as $posicao => $aro) {
            $variante = $this->semearVariante($produto, $aro);

            // Saldo determinístico: fartura nos aros centrais, ponta curta nos extremos.
            $saldo = 24 + (($indice * 7 + $posicao * 11) % 60);

            $atributos = StockItem::factory()->forVariant($variante)->atLocation($cofre)->raw([
                'on_hand' => $saldo,
                'reserved' => 0,
                'available' => $saldo,
                'minimum' => 8,
                'restock_point' => 24,
            ]);

            StockItem::updateOrCreate(
                [
                    'product_variant_id' => $variante->getKey(),
                    'stock_location_id' => $cofre->getKey(),
                ],
                $atributos,
            );
        }
    }

    private function semearVariante(Product $produto, int $aro): ProductVariant
    {
        $atributos = ProductVariant::factory()->forProduct($produto)->withRingSize($aro)->raw([
            'sku' => $produto->sku.'-A'.$aro,
        ]);

        return ProductVariant::updateOrCreate(
            [
                'product_id' => $produto->getKey(),
                'ring_size' => (string) $aro,
            ],
            $atributos,
        );
    }

    /**
     * Grade do protótipo: SKU, nome, coleção, material, acabamento e largura vêm
     * literalmente de docs/telas/1-3-site-catalogo.md §5.
     *
     * `arte` é o nome do SVG em public/images/aliancas; `custo` é custo B2B.
     *
     * @return list<array<string, mixed>>
     */
    private function catalogo(): array
    {
        return [
            [
                'sku' => 'VL-DM-01',
                'nome' => 'Diamond',
                'colecao' => 'Diamond',
                'categoria' => 'Alianças Tradicionais',
                'material' => 'Prata 950',
                'acabamento' => 'Diamantada',
                'largura_mm' => 5.0,
                'formato' => 'Reta',
                'arte' => 'diamond',
                'peso_g' => 4.2,
                'custo' => 17.90,
                'descricao' => 'Aliança de perfil reto em prata 950 com superfície diamantada, brilho uniforme e acabamento polido nas bordas. Produzida na nossa fábrica, com controle de peso e de aro peça a peça.',
            ],
            [
                'sku' => 'VL-PR-02',
                'nome' => 'Premium Rosé',
                'colecao' => 'Premium',
                'categoria' => 'Alianças Tradicionais',
                'material' => 'Ouro Rosé 18k',
                'acabamento' => 'Fosca',
                'largura_mm' => 4.0,
                'formato' => 'Anatômica',
                'arte' => 'rose',
                'peso_g' => 3.6,
                'custo' => 42.00,
            ],
            [
                'sku' => 'VL-CL-03',
                'nome' => 'Clássica',
                'colecao' => 'Clássica',
                'categoria' => 'Alianças Tradicionais',
                'material' => 'Ouro Amarelo 18k',
                'acabamento' => 'Polida',
                'largura_mm' => 4.0,
                'formato' => 'Reta',
                'arte' => 'classica',
                'peso_g' => 3.4,
                'custo' => 39.50,
            ],
            [
                'sku' => 'VL-UB-04',
                'nome' => 'Urbana',
                'colecao' => 'Urbana',
                'categoria' => 'Alianças Tradicionais',
                'material' => 'Prata 950',
                'acabamento' => 'Diamantada',
                'largura_mm' => 5.0,
                'formato' => 'Anatômica',
                'arte' => 'urbana',
                'peso_g' => 4.4,
                'custo' => 18.50,
            ],
            [
                'sku' => 'VL-UB-05',
                'nome' => 'Urbana Black',
                'colecao' => 'Urbana',
                'categoria' => 'Alianças Tradicionais',
                'material' => 'Aço',
                'acabamento' => 'PVD Preto e Dourado',
                'largura_mm' => 6.0,
                'formato' => 'Reta',
                'arte' => 'black',
                'peso_g' => 5.1,
                'custo' => 13.00,
            ],
            [
                'sku' => 'VL-PS-06',
                'nome' => 'Personalizada',
                'colecao' => 'Personalizadas',
                'categoria' => 'Alianças Tradicionais',
                'material' => 'Ouro Amarelo 18k',
                'acabamento' => 'Texturizada',
                'largura_mm' => 5.0,
                'formato' => 'Conforto',
                'arte' => 'personaliz',
                'peso_g' => 4.8,
                'custo' => 47.00,
                'sob_encomenda' => true,
            ],
            [
                'sku' => 'VL-FS-07',
                'nome' => 'Essence',
                'colecao' => 'Clássica',
                'categoria' => 'Alianças Tradicionais',
                'material' => 'Prata 950',
                'acabamento' => 'Fosca',
                'largura_mm' => 4.0,
                'formato' => 'Reta',
                'arte' => 'fosca',
                'peso_g' => 3.2,
                'custo' => 15.00,
            ],
            [
                'sku' => 'VL-PR-08',
                'nome' => 'Premium Cravejada',
                'colecao' => 'Premium',
                'categoria' => 'Alianças Tradicionais',
                'material' => 'Ouro Rosé 18k',
                'acabamento' => 'Cravejada',
                'largura_mm' => 3.0,
                'formato' => 'Reta',
                'arte' => 'cravejada',
                'peso_g' => 2.8,
                'custo' => 48.90,
                'sob_encomenda' => true,
            ],
            [
                'sku' => 'VL-DM-09',
                'nome' => 'Diamond Heart',
                'colecao' => 'Diamond',
                'categoria' => 'Alianças Tradicionais',
                'material' => 'Prata 950',
                'acabamento' => 'Diamantada',
                'largura_mm' => 5.0,
                'formato' => 'Anatômica',
                'arte' => 'diamantada',
                'peso_g' => 4.3,
                'custo' => 19.90,
            ],
            [
                'sku' => 'VL-LI-10',
                'nome' => 'Line',
                'colecao' => 'Clássica',
                'categoria' => 'Alianças Tradicionais',
                'material' => 'Ouro Amarelo 18k',
                'acabamento' => 'Fosca',
                'largura_mm' => 4.0,
                'formato' => 'Reta',
                'arte' => 'ouro',
                'peso_g' => 3.5,
                'custo' => 38.00,
            ],
            [
                'sku' => 'VL-FS-11',
                'nome' => 'Essence Rosé',
                'colecao' => 'Clássica',
                'categoria' => 'Alianças Tradicionais',
                'material' => 'Ouro Rosé 18k',
                'acabamento' => 'Fosca',
                'largura_mm' => 4.0,
                'formato' => 'Anatômica',
                'arte' => 'rose',
                'peso_g' => 3.7,
                'custo' => 41.50,
            ],
            [
                'sku' => 'VL-DM-12',
                'nome' => 'Diamond Lux',
                'colecao' => 'Diamond',
                'categoria' => 'Alianças Tradicionais',
                'material' => 'Prata 950',
                'acabamento' => 'Cravejada',
                'largura_mm' => 4.0,
                'formato' => 'Reta',
                'arte' => 'premium',
                'peso_g' => 3.9,
                'custo' => 22.90,
            ],
        ];
    }

    private function descricaoPadrao(string $formato, string $material, string $acabamento, float $largura): string
    {
        return sprintf(
            'Aliança de perfil %s em %s com acabamento %s e %smm de largura. Produzida na fábrica própria da Velaro, com controle de peso e de aro peça a peça.',
            mb_strtolower($formato),
            $material,
            mb_strtolower($acabamento),
            $this->largura($largura),
        );
    }

    /**
     * Largura sem casa decimal inútil: 4.00 vira "4", 4.50 vira "4,5".
     */
    private function largura(float $largura): string
    {
        return rtrim(rtrim(number_format($largura, 1, ',', ''), '0'), ',');
    }

    private function arte(string $nome): string
    {
        return 'images/aliancas/'.$nome.'.svg';
    }

    /**
     * @param  array<int, array<string, string>>  $valor
     */
    private function json(array $valor): string
    {
        return (string) json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function imprimirContagens(): void
    {
        $comando = $this->command;

        if ($comando === null) {
            return;
        }

        $linhas = array_map(
            static fn (string $tabela): array => [$tabela, (string) DB::table($tabela)->count()],
            self::TABELAS_CONFERIDAS,
        );

        $comando->table(['Tabela', 'Linhas'], $linhas);
    }
}
