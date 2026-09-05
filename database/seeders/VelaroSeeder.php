<?php

/*
[Modulo: database/seeders]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Semeia o conteudo do site publico Velaro e os dois lojistas demo do Portal, com carteira, pedidos, lotes e chamados.
*/

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerConsent;
use App\Models\Finish;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderBatch;
use App\Models\OrderItem;
use App\Models\OrderItemEngraving;
use App\Models\OrderStatusEvent;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Reseller;
use App\Models\ResellerPriceRule;
use App\Models\ResellerPriceSetting;
use App\Models\ResellerStore;
use App\Models\Setting;
use App\Models\StockItem;
use App\Models\StockLocation;
use App\Models\SupportMessage;
use App\Models\SupportStatusEvent;
use App\Models\SupportTag;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\OrderWorkflowStatusService;
use App\Support\ResellerScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
 *
 * A segunda metade do arquivo semeia o Ambiente 2 (Portal do Lojista): dois
 * revendedores aprovados com login, vitrine, margens, carteira, pedidos, lotes e
 * chamados próprios. As chaves naturais lá são o CNPJ do revendedor, o e-mail do
 * usuário, o `reseller_id` da vitrine e das margens, o par (revendedor, CPF) do
 * cliente, `orders.public_number`, `order_batches.code`, o par (série, número) da
 * nota e `support_tickets.code` — nenhuma delas depende da hora da execução.
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
        'resellers',
        'users',
        'reseller_stores',
        'reseller_price_settings',
        'reseller_price_rules',
        'customers',
        'customer_consents',
        'order_batches',
        'orders',
        'order_items',
        'order_item_engravings',
        'order_status_events',
        'payments',
        'invoices',
        'invoice_items',
        'support_tags',
        'support_tickets',
        'support_ticket_tag',
        'support_messages',
        'support_status_events',
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

        // Ambiente 2 (Portal do Lojista). Depende do catálogo acima: os itens dos
        // pedidos demo apontam para produtos e aros que existem de verdade.
        $this->semearPortalDemo($colecoes);

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

    // ───────────────────────── PORTAL DO LOJISTA (ambiente 2) ─────────────────────────

    /**
     * Preço da gravação interna, em reais. É o mesmo número que a configuração
     * `gravacao.preco` publica para o site — aqui ele entra na conta do pedido.
     */
    private const PRECO_GRAVACAO = 35.00;

    /**
     * Semeia os dois lojistas do ambiente 2.
     *
     * Antes disto `users.reseller_id` era nulo em toda a base e não havia com quem
     * entrar em `/portal`: o middleware `reseller` exige usuário vinculado a um
     * revendedor **aprovado**. O primeiro lojista é a Tomazelli Alianças, a loja
     * que os protótipos usam do começo ao fim.
     *
     * O segundo lojista não é enfeite. Ele tem carteira, pedidos, lote, chamado e
     * regra de preço próprios, e é contra ele que os testes provam que o escopo de
     * {@see ResellerScope} não vaza: sem um vizinho na base, "não vejo
     * o dado do outro" é uma afirmação que nenhum teste consegue derrubar.
     *
     * Os dois passam exatamente pelo mesmo caminho de código, com dados diferentes
     * — é isso que torna a comparação entre eles honesta.
     *
     * @param  array<string, ProductCollection>  $colecoes
     */
    private function semearPortalDemo(array $colecoes): void
    {
        // Quem aprovou o cadastro e quem atende o suporte é o Perfil Master. Nulo é
        // aceitável: `approved_by` e `assignee_id` são nuláveis e o VelaroSeeder pode
        // rodar sozinho, sem o admin do DatabaseSeeder.
        $master = User::query()->where('is_admin', true)->orderBy('id')->first();

        $etiquetas = $this->semearEtiquetasSuporte();

        foreach ($this->lojistas() as $lojista) {
            $revendedor = $this->semearRevendedor($lojista, $master);
            $usuario = $this->semearUsuarioLojista($revendedor, $lojista);

            $this->semearVitrine($revendedor, $lojista);
            $this->semearPrecificacao($revendedor, $lojista, $colecoes);

            $clientes = $this->semearCarteira($revendedor, $lojista);
            $lotes = $this->semearLotes($revendedor, $lojista);
            $pedidos = $this->semearPedidos($revendedor, $usuario, $clientes, $lotes, $lojista);

            $this->fecharLotes($lotes, $pedidos, $lojista, $master);
            $this->semearChamados($revendedor, $usuario, $clientes, $pedidos, $etiquetas, $lojista, $master);
        }
    }

    /**
     * Revendedor aprovado, com protocolo e código de revenda preenchidos — sem os
     * dois o cadastro não passa pela régua da tela 1.6 nem pelo cabeçalho do portal.
     *
     * @param  array<string, mixed>  $lojista
     */
    private function semearRevendedor(array $lojista, ?User $master): Reseller
    {
        /** @var array<string, string> $cadastro */
        $cadastro = $lojista['cadastro'];

        $factory = Reseller::factory()->approved();

        if ($cadastro['registration_type'] === Reseller::REGISTRATION_TYPE_MANUAL) {
            $factory = $factory->manualRegistration();
        }

        $atributos = $factory->raw([
            'protocol' => $cadastro['protocol'],
            'code' => $cadastro['code'],
            'legal_name' => $cadastro['legal_name'],
            'trade_name' => $cadastro['trade_name'],
            'cnpj' => $cadastro['cnpj'],
            'state_registration' => $cadastro['state_registration'],
            'contact_name' => $cadastro['contact_name'],
            'contact_cpf' => $cadastro['contact_cpf'],
            'email' => $cadastro['email'],
            'phone' => $cadastro['phone'],
            'whatsapp' => $cadastro['whatsapp'],
            'postal_code' => $cadastro['postal_code'],
            'street' => $cadastro['street'],
            'street_number' => $cadastro['street_number'],
            'address_complement' => null,
            'district' => $cadastro['district'],
            'city' => $cadastro['city'],
            'state' => $cadastro['state'],
            'registration_type' => $cadastro['registration_type'],
            // Data fixa: o state `approved()` sorteia a aprovação nos últimos 180
            // dias, e um seed que muda de conteúdo a cada execução não é seed.
            'approved_at' => $cadastro['approved_at'],
            // Sem este override o state criaria um admin novo a cada execução —
            // `User::factory()->admin()` é resolvido na expansão dos atributos.
            'approved_by' => $master?->getKey(),
        ]);

        return Reseller::updateOrCreate(['cnpj' => $cadastro['cnpj']], $atributos);
    }

    /**
     * O login do lojista. `email_verified_at` é obrigatório: o grupo `portal` passa
     * por `verified` antes de `reseller`.
     *
     * @param  array<string, mixed>  $lojista
     */
    private function semearUsuarioLojista(Reseller $revendedor, array $lojista): User
    {
        /** @var array<string, string> $acesso */
        $acesso = $lojista['acesso'];
        /** @var array<string, string> $cadastro */
        $cadastro = $lojista['cadastro'];
        $senha = $this->senhaDoLojista();

        $atributos = User::factory()->withoutTwoFactor()->forReseller($revendedor)->raw([
            'name' => $acesso['name'],
            'email' => $acesso['email'],
            'phone' => $acesso['phone'],
            'document' => $acesso['document'],
            // O acesso nasce com a aprovação do cadastro, e não na hora do seed:
            // `now()` aqui faria a linha mudar entre duas execuções.
            'email_verified_at' => $cadastro['approved_at'],
            'is_admin' => false,
            'is_agent' => false,
            'is_blocked' => false,
            'remember_token' => null,
        ]);

        unset($atributos['password']);

        $usuario = User::firstOrNew(['email' => $acesso['email']]);
        $usuario->forceFill($atributos);

        // O hash do bcrypt é salgado: regravá-lo a cada execução deixaria a linha
        // suja para sempre e o seed nunca seria idêntico entre rodadas. A senha só
        // é reescrita quando o hash guardado não é mais o da senha do seed.
        if (! $usuario->exists || ! Hash::check($senha, (string) $usuario->password)) {
            $usuario->forceFill(['password' => Hash::make($senha)]);
        }

        $usuario->save();

        return $usuario;
    }

    /**
     * Senha dos lojistas demo. Vem do ambiente porque o seed roda em máquina de
     * desenvolvimento e em homologação, e o fallback existe para o clone recém-feito
     * conseguir entrar sem configurar nada antes.
     */
    private function senhaDoLojista(): string
    {
        $senha = env('RESELLER_SEED_PASSWORD');

        return is_string($senha) && $senha !== '' ? $senha : 'lojista-velaro';
    }

    /**
     * Vitrine white label publicada. Nome, slogan, contato, endereço e as quatro
     * cores vêm da tela 2.6 (34-portal-loja.html) — são a única fonte da pintura da
     * loja, e sem elas a vitrine renderiza sem identidade nenhuma.
     *
     * @param  array<string, mixed>  $lojista
     */
    private function semearVitrine(Reseller $revendedor, array $lojista): ResellerStore
    {
        /** @var array<string, mixed> $loja */
        $loja = $lojista['loja'];

        $atributos = ResellerStore::factory()->published()->raw([
            'reseller_id' => $revendedor->getKey(),
            'name' => $loja['name'],
            'slogan' => $loja['slogan'],
            'logo_path' => null,
            'banner_path' => null,
            'slug' => $loja['slug'],
            'domain' => $loja['domain'],
            'phone' => $loja['phone'],
            'whatsapp' => $loja['whatsapp'],
            'email' => $loja['email'],
            'address' => $loja['address'],
            'color_primary' => $loja['color_primary'],
            'color_secondary' => $loja['color_secondary'],
            'color_background' => '#FFFFFF',
            'color_text' => '#1A1A1A',
            'own_brand_only' => $loja['own_brand_only'],
            'hide_supplier_brand' => $loja['hide_supplier_brand'],
            'show_prices' => true,
            // A vitrine não processa pagamento: o cliente final retira e paga na
            // loja (regra 3 da tela 2.6).
            'pickup_only' => true,
            'payment_in_store' => true,
            'published_at' => $loja['published_at'],
        ]);

        return ResellerStore::updateOrCreate(['reseller_id' => $revendedor->getKey()], $atributos);
    }

    /**
     * Margem padrão do lojista e as exceções por escopo.
     *
     * A regra global existe para todo lojista; a exceção por coleção só para quem
     * a declara. Duas regras de donos diferentes sobre a mesma coleção é
     * exatamente o cenário que o escopo precisa manter separado — o preço que um
     * lojista pratica não pode ser lido pelo outro.
     *
     * @param  array<string, mixed>  $lojista
     * @param  array<string, ProductCollection>  $colecoes
     */
    private function semearPrecificacao(Reseller $revendedor, array $lojista, array $colecoes): void
    {
        /** @var array<string, mixed> $precos */
        $precos = $lojista['precos'];
        $multiplicador = (float) $precos['multiplier'];

        $configuracao = ResellerPriceSetting::factory()->raw([
            'reseller_id' => $revendedor->getKey(),
            'pricing_model' => ResellerPriceSetting::PRICING_MODEL_MULTIPLIER,
            'multiplier' => $multiplicador,
            'margin_global' => $precos['margin_global'],
            'margin_min' => $precos['margin_min'],
            'margin_ideal' => $precos['margin_ideal'],
            'margin_max' => $precos['margin_max'],
            'rounding' => ResellerPriceSetting::ROUNDING_UP_099,
            'rule_scope' => ResellerPriceSetting::RULE_SCOPE_GLOBAL,
            'apply_to_all' => true,
            'allow_manual_override' => true,
            'allow_promotional_prices' => true,
            'recalculated_at' => null,
        ]);

        ResellerPriceSetting::updateOrCreate(['reseller_id' => $revendedor->getKey()], $configuracao);

        $global = ResellerPriceRule::factory()->raw([
            'reseller_id' => $revendedor->getKey(),
            'scope' => ResellerPriceRule::SCOPE_GLOBAL,
            'collection_id' => null,
            'product_id' => null,
            'mode' => ResellerPriceRule::MODE_MULTIPLIER,
            'value' => $multiplicador,
            'priority' => 0,
            'is_active' => true,
        ]);

        ResellerPriceRule::updateOrCreate(
            [
                'reseller_id' => $revendedor->getKey(),
                'scope' => ResellerPriceRule::SCOPE_GLOBAL,
                'collection_id' => null,
                'product_id' => null,
            ],
            $global,
        );

        $colecao = $colecoes[(string) ($precos['colecao_excecao'] ?? '')] ?? null;

        if (! $colecao instanceof ProductCollection) {
            return;
        }

        $excecao = ResellerPriceRule::factory()->forCollection($colecao)->raw([
            'reseller_id' => $revendedor->getKey(),
            'mode' => ResellerPriceRule::MODE_MULTIPLIER,
            'value' => $precos['multiplier_excecao'],
            'is_active' => true,
        ]);

        ResellerPriceRule::updateOrCreate(
            [
                'reseller_id' => $revendedor->getKey(),
                'scope' => ResellerPriceRule::SCOPE_COLLECTION,
                'collection_id' => $colecao->getKey(),
                'product_id' => null,
            ],
            $excecao,
        );
    }

    /**
     * Carteira de consumidores finais do lojista, com o consentimento LGPD que a
     * tela 2.3 exige: data de casamento e de namoro só alimentam campanha com
     * aceite de marketing válido, então cada cliente nasce com os dois registros
     * (marketing e transacional) e o aceite é revogável por linha.
     *
     * O consumidor final não tem login — `customers.user_id` fica nulo.
     *
     * @param  array<string, mixed>  $lojista
     * @return array<int, Customer>
     */
    private function semearCarteira(Reseller $revendedor, array $lojista): array
    {
        $carteira = [];

        /** @var list<array<string, mixed>> $clientes */
        $clientes = $lojista['clientes'];

        foreach ($clientes as $indice => $dados) {
            $atributos = Customer::factory()->forReseller($revendedor)->raw([
                'user_id' => null,
                'name' => $dados['name'],
                'person_type' => Customer::PERSON_TYPE_INDIVIDUAL,
                'company_name' => null,
                'email' => $dados['email'],
                'phone' => $dados['phone'],
                'document' => $dados['document'],
                'postal_code' => $dados['postal_code'],
                'address' => $dados['address'],
                'city' => $dados['city'],
                'state' => $dados['state'],
                'birth_date' => $dados['birth_date'],
                'wedding_date' => $dados['wedding_date'] ?? null,
                'relationship_date' => $dados['relationship_date'] ?? null,
                'notes' => $dados['notes'] ?? null,
                'meta' => null,
            ]);

            $cliente = Customer::updateOrCreate(
                ['reseller_id' => $revendedor->getKey(), 'document' => $dados['document']],
                $atributos,
            );

            $this->datar($cliente, (string) $dados['cadastrado_em']);
            $this->semearConsentimentos($cliente, $dados);

            $carteira[$indice] = $cliente;
        }

        return $carteira;
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    private function semearConsentimentos(Customer $cliente, array $dados): void
    {
        $marketing = (bool) ($dados['aceita_marketing'] ?? true);

        $tipos = [
            CustomerConsent::TYPE_TRANSACTIONAL => true,
            CustomerConsent::TYPE_MARKETING => $marketing,
        ];

        foreach ($tipos as $tipo => $concedido) {
            $factory = $tipo === CustomerConsent::TYPE_MARKETING
                ? CustomerConsent::factory()->marketing()
                : CustomerConsent::factory()->transactional();

            $atributos = $factory->raw([
                'customer_id' => $cliente->getKey(),
                'granted' => $concedido,
                'granted_at' => $dados['cadastrado_em'],
                // Aceite revogado guarda a data da revogação — é o histórico que
                // a regra 2 da tela 2.3 pede, e não um booleano no cliente.
                'revoked_at' => $concedido ? null : $dados['marketing_revogado_em'],
                'channel' => null,
            ]);

            CustomerConsent::updateOrCreate(
                ['customer_id' => $cliente->getKey(), 'type' => $tipo],
                $atributos,
            );
        }
    }

    /**
     * Lotes semanais de faturamento do lojista. O lote é a unidade de cobrança:
     * a Velaro fatura o lojista por lote, não por pedido avulso.
     *
     * @param  array<string, mixed>  $lojista
     * @return array<string, OrderBatch>
     */
    private function semearLotes(Reseller $revendedor, array $lojista): array
    {
        $lotes = [];

        /** @var list<array<string, mixed>> $definicoes */
        $definicoes = $lojista['lotes'];

        foreach ($definicoes as $definicao) {
            $pago = (bool) $definicao['pago'];
            $factory = $pago ? OrderBatch::factory()->paid() : OrderBatch::factory()->open();

            $atributos = $factory->raw([
                'code' => $definicao['code'],
                'reseller_id' => $revendedor->getKey(),
                'cut_date' => $definicao['cut_date'],
                'due_date' => $definicao['due_date'],
                // O total sai da soma dos pedidos do lote, depois que eles existem.
                'total_amount' => 0,
                'paid_at' => $pago ? $definicao['paid_at'] : null,
            ]);

            $lotes[(string) $definicao['code']] = OrderBatch::updateOrCreate(
                ['code' => $definicao['code']],
                $atributos,
            );
        }

        return $lotes;
    }

    /**
     * Pedidos do lojista ao longo do ciclo operacional inteiro: registrado, em
     * produção, em transporte, pronto para retirada e retirado.
     *
     * `operational_status` e `payment_status` são independentes (decisão 1.2), e a
     * regra do negócio amarra os dois pela ponta certa: produção só é liberada
     * depois que o lote está quitado — por isso todo pedido que já saiu de
     * "registrado" está num lote pago.
     *
     * Os valores não são transcritos do protótipo: eles são calculados a partir do
     * custo B2B que o catálogo semeado tem de verdade, para a identidade
     * `total = subtotal + gravação + frete - desconto` fechar em cada linha.
     *
     * @param  array<int, Customer>  $clientes
     * @param  array<string, OrderBatch>  $lotes
     * @param  array<string, mixed>  $lojista
     * @return array<string, Order>
     */
    private function semearPedidos(
        Reseller $revendedor,
        User $usuario,
        array $clientes,
        array $lotes,
        array $lojista,
    ): array {
        $pedidos = [];

        /** @var list<array<string, mixed>> $definicoes */
        $definicoes = $lojista['pedidos'];

        foreach ($definicoes as $definicao) {
            $cliente = $clientes[(int) $definicao['cliente']] ?? null;
            $lote = $lotes[(string) $definicao['lote']] ?? null;

            if (! $cliente instanceof Customer || ! $lote instanceof OrderBatch) {
                continue;
            }

            $pedido = $this->semearPedido($revendedor, $usuario, $cliente, $lote, $definicao);

            $pedidos[(string) $definicao['numero']] = $pedido;
        }

        return $pedidos;
    }

    /**
     * @param  array<string, mixed>  $definicao
     */
    private function semearPedido(
        Reseller $revendedor,
        User $usuario,
        Customer $cliente,
        OrderBatch $lote,
        array $definicao,
    ): Order {
        $numero = (string) $definicao['numero'];
        $operacional = (string) $definicao['operacional'];
        $pagamento = (string) $definicao['pagamento'];
        $retirado = $operacional === Order::OPERATIONAL_STATUS_PICKED_UP;

        $atributos = Order::factory()->forReseller($revendedor)->raw([
            'public_number' => $numero,
            'user_id' => $usuario->getKey(),
            'customer_id' => $cliente->getKey(),
            'batch_id' => $lote->getKey(),
            'shipment_id' => null,
            'reference' => $definicao['referencia'],
            'status' => $this->espelhoDoScaffold($operacional, $pagamento),
            'operational_status' => $operacional,
            'payment_status' => $pagamento,
            // Zerados aqui e recalculados a partir dos itens logo abaixo.
            'total_amount' => 0,
            'subtotal_amount' => 0,
            'engraving_amount' => 0,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'currency' => 'BRL',
            'expected_at' => $definicao['previsao'],
            'arrived_at' => $definicao['chegou_em'] ?? null,
            'picked_up_at' => $retirado ? $definicao['retirado_em'] : null,
            'picked_up_by_name' => $retirado ? $cliente->name : null,
            'picked_up_by_document' => $retirado ? $cliente->document : null,
            'picked_up_by_customer_id' => $retirado ? $cliente->getKey() : null,
            'notes' => $definicao['observacao'] ?? null,
            'meta' => null,
        ]);

        $pedido = Order::updateOrCreate(['public_number' => $numero], $atributos);

        /** @var list<array<string, mixed>> $itens */
        $itens = $definicao['itens'];
        $valores = $this->semearItens($pedido, $itens);

        $this->fixarValores($pedido, [
            'subtotal_amount' => $valores['subtotal'],
            'engraving_amount' => $valores['gravacao'],
            'shipping_amount' => 0.0,
            'discount_amount' => 0.0,
            'total_amount' => round($valores['subtotal'] + $valores['gravacao'], 2),
        ]);

        // Depois dos valores: `datar()` é o que fixa `updated_at`, e o total só é
        // conhecido quando os itens já existem.
        $this->datar($pedido, (string) $definicao['feito_em']);

        $this->semearLinhaDoTempo($pedido, (string) $definicao['feito_em']);

        return $pedido;
    }

    /**
     * Itens do pedido com o aro escolhido e, em parte deles, a gravação interna.
     *
     * `unit_price` é o custo B2B do catálogo congelado no momento da escolha — é o
     * que o lojista paga à Velaro, e é o número que a coluna "Valor (custo Velaro)"
     * das telas 2.4 e 2.5 mostra.
     *
     * @param  list<array<string, mixed>>  $itens
     * @return array{subtotal: float, gravacao: float}
     */
    private function semearItens(Order $pedido, array $itens): array
    {
        $subtotal = 0.0;
        $gravacao = 0.0;

        foreach ($itens as $item) {
            $produto = Product::query()->where('sku', $item['sku'])->first();

            if (! $produto instanceof Product) {
                continue;
            }

            $variante = ProductVariant::query()
                ->where('product_id', $produto->getKey())
                ->where('ring_size', (string) $item['aro'])
                ->first();

            if (! $variante instanceof ProductVariant) {
                continue;
            }

            $quantidade = (int) $item['quantidade'];
            $unitario = round((float) $produto->price, 2);
            $total = round($unitario * $quantidade, 2);

            $atributos = OrderItem::factory()->withVariant($variante)->raw([
                'order_id' => $pedido->getKey(),
                'product_id' => $produto->getKey(),
                'product_variant_id' => $variante->getKey(),
                'quantity' => $quantidade,
                'unit_price' => $unitario,
                'total_price' => $total,
                'meta' => null,
            ]);

            $linha = OrderItem::updateOrCreate(
                ['order_id' => $pedido->getKey(), 'product_variant_id' => $variante->getKey()],
                $atributos,
            );

            $subtotal += $total;
            $gravacao += $this->semearGravacao($linha, $item);
        }

        return ['subtotal' => round($subtotal, 2), 'gravacao' => round($gravacao, 2)];
    }

    /**
     * Gravação do item — cobrada e discriminada à parte, uma linha por item, sempre
     * presente (desligada quando o item não tem gravação) para a tela poder dizer
     * "Solicitada: Não" sem inferir nada da ausência do registro.
     *
     * @param  array<string, mixed>  $item
     */
    private function semearGravacao(OrderItem $linha, array $item): float
    {
        $texto = $item['gravacao'] ?? null;

        if (! is_string($texto) || $texto === '') {
            $atributos = OrderItemEngraving::factory()->disabled()->raw([
                'order_item_id' => $linha->getKey(),
            ]);

            OrderItemEngraving::updateOrCreate(['order_item_id' => $linha->getKey()], $atributos);

            return 0.0;
        }

        $preco = round(self::PRECO_GRAVACAO * (int) $linha->quantity, 2);

        $atributos = OrderItemEngraving::factory()->raw([
            'order_item_id' => $linha->getKey(),
            'enabled' => true,
            'text' => $texto,
            'date' => $item['gravacao_data'] ?? null,
            'chars' => mb_strlen($texto),
            'price' => $preco,
        ]);

        OrderItemEngraving::updateOrCreate(['order_item_id' => $linha->getKey()], $atributos);

        return $preco;
    }

    /**
     * Linha do tempo operacional do pedido: todos os degraus percorridos até o
     * status atual. Um pedido "retirado" sem histórico de "registrado" não está no
     * ciclo — está só com um rótulo.
     */
    private function semearLinhaDoTempo(Order $pedido, string $feitoEm): void
    {
        $cadeia = [
            Order::OPERATIONAL_STATUS_REGISTERED,
            Order::OPERATIONAL_STATUS_PAYMENT_CONFIRMED,
            Order::OPERATIONAL_STATUS_IN_PRODUCTION,
            Order::OPERATIONAL_STATUS_PRODUCTION_COMPLETED,
            Order::OPERATIONAL_STATUS_IN_TRANSIT,
            Order::OPERATIONAL_STATUS_READY_FOR_PICKUP,
            Order::OPERATIONAL_STATUS_PICKED_UP,
        ];

        $atual = (int) array_search($pedido->operational_status, $cadeia, true);
        $momento = Carbon::parse($feitoEm);

        for ($degrau = 0; $degrau <= $atual; $degrau++) {
            $factory = $degrau === 0
                ? OrderStatusEvent::factory()->opening()
                : OrderStatusEvent::factory();

            $atributos = $factory->raw([
                'order_id' => $pedido->getKey(),
                'scope' => OrderStatusEvent::SCOPE_OPERATIONAL,
                'from_status' => $degrau === 0 ? null : $cadeia[$degrau - 1],
                'to_status' => $cadeia[$degrau],
                'actor_id' => null,
                'note' => null,
            ]);

            $evento = OrderStatusEvent::updateOrCreate(
                ['order_id' => $pedido->getKey(), 'to_status' => $cadeia[$degrau]],
                $atributos,
            );

            // Um degrau por dia a partir da data do pedido: a timeline precisa de
            // ordem, e `created_at` é o que a tela 2.5 usa para desenhá-la.
            $this->datar($evento, $momento->copy()->addDays($degrau)->toDateTimeString());
        }
    }

    /**
     * Espelho `orders.status`, o campo do scaffold.
     *
     * A autoridade do módulo Velaro é o par `operational_status` / `payment_status`;
     * este campo existe só por compatibilidade com OrderWorkflowStatusService, cujo
     * vocabulário é uma lista sem constantes nomeadas
     * ({@see OrderWorkflowStatusService::STATUSES}) — daí o slug
     * literal aqui, e só aqui.
     */
    private function espelhoDoScaffold(string $operacional, string $pagamento): string
    {
        if ($operacional === Order::OPERATIONAL_STATUS_PICKED_UP) {
            return 'completed';
        }

        $emAndamento = [
            Order::OPERATIONAL_STATUS_IN_PRODUCTION,
            Order::OPERATIONAL_STATUS_PRODUCTION_COMPLETED,
            Order::OPERATIONAL_STATUS_IN_TRANSIT,
            Order::OPERATIONAL_STATUS_READY_FOR_PICKUP,
        ];

        if (in_array($operacional, $emAndamento, true)) {
            return 'in_progress';
        }

        return $pagamento === Order::PAYMENT_STATUS_PAID ? 'paid' : 'awaiting_payment';
    }

    /**
     * Fecha a conta de cada lote: total pela soma dos pedidos, a cobrança à Velaro
     * e, no lote quitado, a NF-e com o rateio por pedido.
     *
     * A nota só existe no lote pago porque é assim que o faturamento anda: a Velaro
     * emite a NF-e da venda B2B depois da compensação. É também o que resolve a
     * coluna "NF-e" da tela 2.4 — quem baixa nota é o pedido cujo lote já fechou.
     *
     * @param  array<string, OrderBatch>  $lotes
     * @param  array<string, Order>  $pedidos
     * @param  array<string, mixed>  $lojista
     */
    private function fecharLotes(array $lotes, array $pedidos, array $lojista, ?User $master): void
    {
        /** @var list<array<string, mixed>> $definicoes */
        $definicoes = $lojista['lotes'];

        foreach ($definicoes as $definicao) {
            $codigo = (string) $definicao['code'];
            $lote = $lotes[$codigo] ?? null;

            if (! $lote instanceof OrderBatch) {
                continue;
            }

            $doLote = array_filter(
                $pedidos,
                static fn (Order $pedido): bool => $pedido->batch_id === $lote->getKey(),
            );

            $total = round(array_sum(array_map(
                static fn (Order $pedido): float => (float) $pedido->total_amount,
                $doLote,
            )), 2);

            $this->fixarValores($lote, ['total_amount' => $total]);

            // O lote nasce no corte da semana, não na hora do seed.
            $pago = (bool) $definicao['pago'];
            $this->datar(
                $lote,
                $definicao['cut_date'].' 18:00:00',
                $pago ? (string) $definicao['paid_at'] : $definicao['cut_date'].' 18:00:00',
            );

            $this->semearCobranca($lote, $definicao, $total, $master);

            if (! $pago) {
                continue;
            }

            $this->semearNota($lote, $definicao, $doLote, $total, $master);
        }
    }

    /**
     * @param  array<string, mixed>  $definicao
     */
    private function semearCobranca(OrderBatch $lote, array $definicao, float $total, ?User $master): void
    {
        $pago = (bool) $definicao['pago'];
        $factory = $pago ? Payment::factory()->paid() : Payment::factory()->pending();

        $atributos = $factory->raw([
            'batch_id' => $lote->getKey(),
            'method' => $definicao['meio'],
            'amount' => $total,
            'due_date' => $definicao['due_date'],
            'paid_at' => $pago ? $definicao['paid_at'] : null,
            'external_id' => $definicao['identificador_externo'],
            'receipt_path' => null,
            // Sem o override o state `paid()` criaria um admin novo a cada execução.
            'reconciled_by' => $pago ? $master?->getKey() : null,
        ]);

        Payment::updateOrCreate(
            ['batch_id' => $lote->getKey(), 'method' => $definicao['meio']],
            $atributos,
        );
    }

    /**
     * @param  array<string, mixed>  $definicao
     * @param  array<string, Order>  $doLote
     */
    private function semearNota(OrderBatch $lote, array $definicao, array $doLote, float $total, ?User $master): void
    {
        /** @var array<string, string> $nota */
        $nota = $definicao['nota'];

        $atributos = Invoice::factory()->raw([
            'batch_id' => $lote->getKey(),
            'series' => $nota['series'],
            'number' => $nota['number'],
            'amount' => $total,
            'status' => Invoice::STATUS_AUTHORIZED,
            'issued_at' => $nota['issued_at'],
            'pdf_path' => 'notas/'.$nota['series'].'-'.$nota['number'].'.pdf',
            'xml_path' => 'notas/'.$nota['series'].'-'.$nota['number'].'.xml',
            'provider' => $nota['provider'],
            'issued_by' => $master?->getKey(),
        ]);

        $documento = Invoice::updateOrCreate(
            ['series' => $nota['series'], 'number' => $nota['number']],
            $atributos,
        );

        foreach ($doLote as $pedido) {
            $item = InvoiceItem::factory()->raw([
                'invoice_id' => $documento->getKey(),
                'order_id' => $pedido->getKey(),
                'amount' => round((float) $pedido->total_amount, 2),
            ]);

            InvoiceItem::updateOrCreate(
                ['invoice_id' => $documento->getKey(), 'order_id' => $pedido->getKey()],
                $item,
            );
        }
    }

    /**
     * Vocabulário de etiquetas do suporte. É compartilhado entre os lojistas de
     * propósito: a etiqueta é da Velaro, não do lojista — o que não pode cruzar é o
     * chamado, e esse é escopado por `reseller_id`.
     *
     * @return array<string, SupportTag>
     */
    private function semearEtiquetasSuporte(): array
    {
        $etiquetas = [];

        foreach (['Troca', 'Tamanho', 'Aliança', 'Ouro 18K', 'Financeiro', 'Vitrine'] as $nome) {
            $slug = Str::slug($nome);

            $atributos = SupportTag::factory()->raw(['name' => $nome, 'slug' => $slug]);

            $etiquetas[$nome] = SupportTag::updateOrCreate(['slug' => $slug], $atributos);
        }

        return $etiquetas;
    }

    /**
     * Chamados do lojista, em estados diferentes da fila, com a conversa e as
     * etiquetas.
     *
     * A conversa é sempre Velaro ↔ revendedor: o cliente final aparece só como
     * pessoa vinculada ao pedido. E cada chamado carrega uma observação interna,
     * que é justamente o que a tela do portal nunca pode mostrar — semear uma aqui
     * é o que dá ao teste algo real para provar que `is_internal_note` não vaza.
     *
     * @param  array<int, Customer>  $clientes
     * @param  array<string, Order>  $pedidos
     * @param  array<string, SupportTag>  $etiquetas
     * @param  array<string, mixed>  $lojista
     */
    private function semearChamados(
        Reseller $revendedor,
        User $usuario,
        array $clientes,
        array $pedidos,
        array $etiquetas,
        array $lojista,
        ?User $master,
    ): void {
        /** @var list<array<string, mixed>> $definicoes */
        $definicoes = $lojista['chamados'];

        foreach ($definicoes as $definicao) {
            $chamado = $this->semearChamado($revendedor, $clientes, $pedidos, $definicao, $master);

            /** @var list<string> $nomes */
            $nomes = $definicao['etiquetas'];

            $vinculos = array_values(array_filter(array_map(
                static fn (string $nome): ?int => ($etiquetas[$nome] ?? null)?->getKey(),
                $nomes,
            )));

            // `sync` para a lista de etiquetas ser a mesma nas duas execuções.
            $chamado->tags()->sync($vinculos);

            $this->semearConversa($chamado, $usuario, $definicao, $master);
            $this->semearMarcosDoChamado($chamado, $definicao);
        }
    }

    /**
     * @param  array<int, Customer>  $clientes
     * @param  array<string, Order>  $pedidos
     * @param  array<string, mixed>  $definicao
     */
    private function semearChamado(
        Reseller $revendedor,
        array $clientes,
        array $pedidos,
        array $definicao,
        ?User $master,
    ): SupportTicket {
        $status = (string) $definicao['status'];
        $resolvido = $status === SupportTicket::STATUS_RESOLVED;
        $pedido = $pedidos[(string) ($definicao['pedido'] ?? '')] ?? null;
        $cliente = $clientes[(int) ($definicao['cliente'] ?? -1)] ?? null;

        $atributos = SupportTicket::factory()->raw([
            'code' => $definicao['code'],
            'reseller_id' => $revendedor->getKey(),
            'order_id' => $pedido?->getKey(),
            'customer_id' => $cliente?->getKey(),
            'subject' => $definicao['subject'],
            'category' => $definicao['category'],
            'priority' => $definicao['priority'],
            'status' => $status,
            // Sem o override os states `inProgress()`/`resolved()` criariam um
            // usuário novo a cada execução.
            'assignee_id' => $master?->getKey(),
            'first_response_at' => $definicao['primeira_resposta_em'],
            'resolved_at' => $resolvido ? $definicao['resolvido_em'] : null,
            'closed_at' => null,
        ]);

        $chamado = SupportTicket::updateOrCreate(['code' => $definicao['code']], $atributos);

        $this->datar($chamado, (string) $definicao['aberto_em'], (string) $definicao['atualizado_em']);

        return $chamado;
    }

    /**
     * @param  array<string, mixed>  $definicao
     */
    private function semearConversa(SupportTicket $chamado, User $usuario, array $definicao, ?User $master): void
    {
        /** @var list<array<string, mixed>> $mensagens */
        $mensagens = $definicao['conversa'];

        foreach ($mensagens as $mensagem) {
            $daVelaro = $mensagem['autor'] !== SupportMessage::AUTHOR_ROLE_RESELLER;
            $interna = (bool) ($mensagem['interna'] ?? false);

            $factory = match (true) {
                $interna => SupportMessage::factory()->internalNote(),
                $daVelaro => SupportMessage::factory()->fromVelaro(),
                default => SupportMessage::factory(),
            };

            $atributos = $factory->raw([
                'ticket_id' => $chamado->getKey(),
                'author_id' => $daVelaro ? $master?->getKey() : $usuario->getKey(),
                'author_role' => $daVelaro ? SupportMessage::AUTHOR_ROLE_VELARO : SupportMessage::AUTHOR_ROLE_RESELLER,
                'body' => $mensagem['texto'],
                'is_internal_note' => $interna,
            ]);

            // O texto é a identidade da mensagem dentro do chamado: a tabela não
            // tem coluna de ordem, e repetir a fala seria uma mensagem nova.
            $linha = SupportMessage::updateOrCreate(
                ['ticket_id' => $chamado->getKey(), 'body' => $mensagem['texto']],
                $atributos,
            );

            $this->datar($linha, (string) $mensagem['em']);
        }
    }

    /**
     * @param  array<string, mixed>  $definicao
     */
    private function semearMarcosDoChamado(SupportTicket $chamado, array $definicao): void
    {
        $abertura = SupportStatusEvent::factory()->opening()->raw([
            'ticket_id' => $chamado->getKey(),
            'actor_id' => null,
            'channel' => null,
        ]);

        $evento = SupportStatusEvent::updateOrCreate(
            ['ticket_id' => $chamado->getKey(), 'to_status' => SupportTicket::STATUS_OPEN],
            $abertura,
        );

        $this->datar($evento, (string) $definicao['aberto_em']);

        $status = (string) $definicao['status'];

        if ($status === SupportTicket::STATUS_OPEN) {
            return;
        }

        $transicao = SupportStatusEvent::factory()->raw([
            'ticket_id' => $chamado->getKey(),
            'from_status' => SupportTicket::STATUS_OPEN,
            'to_status' => $status,
            'actor_id' => null,
            'channel' => null,
            'note' => $definicao['nota_transicao'],
        ]);

        $mudanca = SupportStatusEvent::updateOrCreate(
            ['ticket_id' => $chamado->getKey(), 'to_status' => $status],
            $transicao,
        );

        $this->datar($mudanca, (string) $definicao['atualizado_em']);
    }

    /**
     * Fixa `created_at`/`updated_at` sem passar pelo ciclo de timestamps do
     * Eloquent: `created_at` não é `fillable` e `save()` reescreveria `updated_at`
     * com a hora da execução — as duas rodadas do seed deixariam de produzir
     * exatamente a mesma linha.
     */
    /**
     * Grava colunas de dinheiro só quando o número muda de fato.
     *
     * `decimal:2` guarda string no banco e float na memória; um `save()` cego
     * marcaria a linha como suja em toda execução e reescreveria `updated_at` — e
     * duas rodadas do seed deixariam de produzir exatamente a mesma linha.
     *
     * @param  array<string, float>  $valores
     */
    private function fixarValores(Model $modelo, array $valores): void
    {
        $mudou = false;

        foreach ($valores as $coluna => $valor) {
            if (abs((float) $modelo->getAttribute($coluna) - $valor) >= 0.005) {
                $mudou = true;
            }
        }

        if (! $mudou) {
            return;
        }

        $modelo->forceFill($valores)->save();
    }

    private function datar(Model $modelo, string $criadoEm, ?string $atualizadoEm = null): void
    {
        $criado = Carbon::parse($criadoEm);
        $atualizado = Carbon::parse($atualizadoEm ?? $criadoEm);

        DB::table($modelo->getTable())
            ->where($modelo->getKeyName(), $modelo->getKey())
            ->update(['created_at' => $criado, 'updated_at' => $atualizado]);

        $modelo->setAttribute('created_at', $criado);
        $modelo->setAttribute('updated_at', $atualizado);
        $modelo->syncOriginal();
    }

    /**
     * Os dois lojistas do seed, na mesma forma de dado.
     *
     * A **Tomazelli Alianças** é a loja que os protótipos do Portal usam do começo
     * ao fim: nome, slogan, contato, endereço, cores, multiplicador 3,6x, protocolo
     * VEL-2026-0148 e código VEL-02412 saem literalmente de docs/mockups. Os nomes
     * da carteira e o calendário de maio/2026 vêm das tabelas de 33-portal-pedidos,
     * 32-portal-financeiro e 36-portal-suporte.
     *
     * A **Aliança & Cia** (ALC-0042, também do protótipo do Master) é a vizinha de
     * base. Ela existe para o isolamento ser verificável: tem carteira, pedidos,
     * lote, chamado e regra de preço próprios, e nada disso pode aparecer no portal
     * da Tomazelli — nem o contrário.
     *
     * As datas são absolutas, e não relativas a `now()`, por duas razões: o seed
     * precisa produzir a mesma linha em duas execuções, e o calendário de maio/2026
     * é o mesmo que a régua de aceite das telas 2.4 e 2.5 cita.
     *
     * @return list<array<string, mixed>>
     */
    private function lojistas(): array
    {
        return [
            [
                'cadastro' => [
                    'protocol' => 'VEL-2026-0148',
                    'code' => 'VEL-02412',
                    'legal_name' => 'Tomazelli Alianças Ltda.',
                    'trade_name' => 'Tomazelli Alianças',
                    'cnpj' => '12.345.678/0001-90',
                    'state_registration' => '110.042.987.114',
                    'contact_name' => 'Lucas Tomazelli',
                    'contact_cpf' => '317.204.918-42',
                    'email' => 'contato@tomazellialiancas.com.br',
                    'phone' => '(17) 99123-4567',
                    'whatsapp' => '(17) 99123-4567',
                    'postal_code' => '15015-100',
                    'street' => 'Rua Bernardino de Campos',
                    'street_number' => '1420',
                    'district' => 'Centro',
                    'city' => 'São José do Rio Preto',
                    'state' => 'SP',
                    'registration_type' => Reseller::REGISTRATION_TYPE_AUTOMATIC,
                    'approved_at' => '2026-01-15 09:20:00',
                ],
                // Quem entra no portal é o operador da loja; `contact_name` acima é o
                // responsável pelo cadastro. São duas pessoas na mesma joalheria, como
                // no protótipo (o chamado 36-portal-suporte é assinado por João Ferreira).
                'acesso' => [
                    'name' => 'João Ferreira',
                    'email' => 'lojista@velaro.test',
                    'phone' => '(17) 98765-4321',
                    'document' => '284.517.630-11',
                ],
                'loja' => [
                    'name' => 'Tomazelli Alianças',
                    'slogan' => 'Símbolo de amor. Promessa para a vida toda.',
                    'slug' => 'tomazelli-aliancas',
                    'domain' => 'tomazellialiancas.com.br',
                    'phone' => '(11) 98888-2020',
                    'whatsapp' => '(11) 98888-2020',
                    'email' => 'contato@tomazellialiancas.com.br',
                    'address' => 'Rua das Alianças, 123 - Centro, São Paulo - SP',
                    'color_primary' => '#800020',
                    'color_secondary' => '#B8860B',
                    'own_brand_only' => true,
                    'hide_supplier_brand' => true,
                    'published_at' => '2026-02-02 11:40:00',
                ],
                'precos' => [
                    'multiplier' => 3.60,
                    'margin_global' => 50.00,
                    'margin_min' => 40.00,
                    'margin_ideal' => 50.00,
                    'margin_max' => 60.00,
                    'colecao_excecao' => 'Premium',
                    'multiplier_excecao' => 4.20,
                ],
                'clientes' => [
                    ['name' => 'Maria Silva', 'document' => '123.456.789-00', 'phone' => '(11) 98765-4321', 'email' => 'maria.silva@email.com', 'postal_code' => '01310-100', 'address' => 'Avenida Paulista, 1578', 'city' => 'São Paulo', 'state' => 'SP', 'birth_date' => '1994-03-18', 'wedding_date' => '2026-08-22', 'relationship_date' => '2021-02-14', 'cadastrado_em' => '2026-04-02 09:12:00', 'notes' => 'Prefere contato por WhatsApp no fim da tarde.'],
                    ['name' => 'João Santos', 'document' => '987.654.321-00', 'phone' => '(11) 97654-3210', 'email' => 'joao.santos@email.com', 'postal_code' => '04538-133', 'address' => 'Rua Funchal, 418', 'city' => 'São Paulo', 'state' => 'SP', 'birth_date' => '1990-11-05', 'wedding_date' => '2026-09-12', 'relationship_date' => '2019-06-30', 'cadastrado_em' => '2026-04-08 15:41:00'],
                    ['name' => 'Ana Paula Costa', 'document' => '456.789.123-00', 'phone' => '(16) 99612-8877', 'email' => 'ana.costa@email.com', 'postal_code' => '14020-670', 'address' => 'Rua São José, 902', 'city' => 'Ribeirão Preto', 'state' => 'SP', 'birth_date' => '1988-07-24', 'wedding_date' => '2026-07-04', 'relationship_date' => '2017-12-25', 'cadastrado_em' => '2026-04-14 10:05:00'],
                    ['name' => 'Carlos Oliveira', 'document' => '321.654.987-00', 'phone' => '(17) 99411-2255', 'email' => 'carlos.oliveira@email.com', 'postal_code' => '15015-200', 'address' => 'Rua Voluntários de São Paulo, 3120', 'city' => 'São José do Rio Preto', 'state' => 'SP', 'birth_date' => '1985-01-30', 'wedding_date' => '2026-05-16', 'relationship_date' => '2015-04-09', 'cadastrado_em' => '2026-03-21 14:33:00'],
                    ['name' => 'Juliana Lima', 'document' => '159.753.486-00', 'phone' => '(11) 96543-1122', 'email' => 'juliana.lima@email.com', 'postal_code' => '09080-510', 'address' => 'Avenida Industrial, 780', 'city' => 'Santo André', 'state' => 'SP', 'birth_date' => '1996-09-09', 'relationship_date' => '2023-01-20', 'cadastrado_em' => '2026-04-22 11:18:00'],
                    ['name' => 'Fernanda Souza', 'document' => '753.159.264-00', 'phone' => '(19) 99820-4477', 'email' => 'fernanda.souza@email.com', 'postal_code' => '13015-904', 'address' => 'Rua Barão de Jaguara, 1481', 'city' => 'Campinas', 'state' => 'SP', 'birth_date' => '1992-12-02', 'wedding_date' => '2026-06-06', 'relationship_date' => '2020-10-11', 'cadastrado_em' => '2026-03-11 16:47:00'],
                    // Consentimento de marketing revogado: a data de casamento existe,
                    // mas a tela 2.3 não pode usá-la em campanha (regra 1 da LGPD).
                    ['name' => 'Rafael Ferreira', 'document' => '852.963.741-00', 'phone' => '(11) 95511-8899', 'email' => 'rafael.ferreira@email.com', 'postal_code' => '02011-000', 'address' => 'Rua Voluntários da Pátria, 2244', 'city' => 'São Paulo', 'state' => 'SP', 'birth_date' => '1987-05-14', 'wedding_date' => '2026-11-28', 'cadastrado_em' => '2026-02-19 08:55:00', 'aceita_marketing' => false, 'marketing_revogado_em' => '2026-04-30 19:02:00'],
                    ['name' => 'Lucas Almeida', 'document' => '741.852.963-00', 'phone' => '(16) 99188-3344', 'email' => 'lucas.almeida@email.com', 'postal_code' => '14801-320', 'address' => 'Avenida Portugal, 615', 'city' => 'Araraquara', 'state' => 'SP', 'birth_date' => '1998-08-27', 'relationship_date' => '2024-03-15', 'cadastrado_em' => '2026-05-04 17:26:00'],
                ],
                'lotes' => [
                    [
                        'code' => 'LOTE-2026-W23-VEL02412',
                        'cut_date' => '2026-05-14',
                        'due_date' => '2026-05-21',
                        'pago' => true,
                        'paid_at' => '2026-05-20 10:42:00',
                        'meio' => Payment::METHOD_PIX,
                        'identificador_externo' => 'E2E1234567890202605201042',
                        'nota' => [
                            'series' => '1',
                            'number' => '000.024.156',
                            'issued_at' => '2026-05-20 14:05:00',
                            'provider' => 'SEFAZ-SP',
                        ],
                    ],
                    [
                        'code' => 'LOTE-2026-W24-VEL02412',
                        'cut_date' => '2026-05-21',
                        'due_date' => '2026-05-28',
                        'pago' => false,
                        'paid_at' => null,
                        'meio' => Payment::METHOD_BOLETO,
                        'identificador_externo' => '00190000090123456789012345678',
                    ],
                ],
                'pedidos' => [
                    [
                        'numero' => 'ORD012548', 'referencia' => 'PC-2026-114', 'cliente' => 0,
                        'lote' => 'LOTE-2026-W24-VEL02412',
                        'operacional' => Order::OPERATIONAL_STATUS_REGISTERED,
                        'pagamento' => Order::PAYMENT_STATUS_PENDING,
                        'feito_em' => '2026-05-16 10:32:00', 'previsao' => '2026-05-23',
                        'itens' => [
                            ['sku' => 'VL-CL-03', 'aro' => 18, 'quantidade' => 2, 'gravacao' => 'M & J 16.05.2026', 'gravacao_data' => '2026-08-22'],
                            ['sku' => 'VL-DM-01', 'aro' => 18, 'quantidade' => 1],
                        ],
                    ],
                    [
                        'numero' => 'ORD012547', 'referencia' => 'PC-2026-115', 'cliente' => 1,
                        'lote' => 'LOTE-2026-W23-VEL02412',
                        'operacional' => Order::OPERATIONAL_STATUS_IN_PRODUCTION,
                        'pagamento' => Order::PAYMENT_STATUS_PAID,
                        'feito_em' => '2026-05-15 14:18:00', 'previsao' => '2026-05-22',
                        'itens' => [
                            ['sku' => 'VL-DM-09', 'aro' => 18, 'quantidade' => 2],
                        ],
                    ],
                    [
                        'numero' => 'ORD012546', 'referencia' => 'PC-2026-116', 'cliente' => 2,
                        'lote' => 'LOTE-2026-W23-VEL02412',
                        'operacional' => Order::OPERATIONAL_STATUS_IN_TRANSIT,
                        'pagamento' => Order::PAYMENT_STATUS_PAID,
                        'feito_em' => '2026-05-15 09:45:00', 'previsao' => '2026-05-21',
                        'itens' => [
                            ['sku' => 'VL-PR-02', 'aro' => 16, 'quantidade' => 2],
                            ['sku' => 'VL-FS-07', 'aro' => 18, 'quantidade' => 1],
                        ],
                    ],
                    [
                        'numero' => 'ORD012545', 'referencia' => 'PC-2026-117', 'cliente' => 3,
                        'lote' => 'LOTE-2026-W23-VEL02412',
                        'operacional' => Order::OPERATIONAL_STATUS_PICKED_UP,
                        'pagamento' => Order::PAYMENT_STATUS_PAID,
                        'feito_em' => '2026-05-14 16:20:00', 'previsao' => '2026-05-16',
                        'chegou_em' => '2026-05-16 09:10:00', 'retirado_em' => '2026-05-16 15:48:00',
                        'itens' => [
                            ['sku' => 'VL-UB-04', 'aro' => 20, 'quantidade' => 2, 'gravacao' => 'C & L 14.05.2026', 'gravacao_data' => '2026-05-16'],
                        ],
                    ],
                    [
                        'numero' => 'ORD012544', 'referencia' => 'PC-2026-118', 'cliente' => 4,
                        'lote' => 'LOTE-2026-W24-VEL02412',
                        'operacional' => Order::OPERATIONAL_STATUS_REGISTERED,
                        'pagamento' => Order::PAYMENT_STATUS_AWAITING_CLEARANCE,
                        'feito_em' => '2026-05-13 11:05:00', 'previsao' => '2026-05-20',
                        'observacao' => 'Cliente pediu para avisar por WhatsApp quando chegar.',
                        'itens' => [
                            ['sku' => 'VL-LI-10', 'aro' => 16, 'quantidade' => 1],
                        ],
                    ],
                    [
                        'numero' => 'ORD012549', 'referencia' => 'PC-2026-119', 'cliente' => 5,
                        'lote' => 'LOTE-2026-W23-VEL02412',
                        'operacional' => Order::OPERATIONAL_STATUS_READY_FOR_PICKUP,
                        'pagamento' => Order::PAYMENT_STATUS_PAID,
                        'feito_em' => '2026-05-11 17:22:00', 'previsao' => '2026-05-19',
                        'chegou_em' => '2026-05-19 08:15:00',
                        'itens' => [
                            ['sku' => 'VL-PR-08', 'aro' => 18, 'quantidade' => 2, 'gravacao' => 'F & G 06.06.2026', 'gravacao_data' => '2026-06-06'],
                            ['sku' => 'VL-DM-12', 'aro' => 18, 'quantidade' => 1],
                        ],
                    ],
                ],
                'chamados' => [
                    [
                        'code' => 'SUP-2026-0821',
                        'subject' => 'Dúvida sobre prazos de entrega',
                        'category' => 'Pedidos',
                        'priority' => SupportTicket::PRIORITY_MEDIUM,
                        'status' => SupportTicket::STATUS_IN_PROGRESS,
                        'pedido' => 'ORD012548', 'cliente' => 0,
                        'aberto_em' => '2026-05-16 10:32:00',
                        'primeira_resposta_em' => '2026-05-16 11:12:00',
                        'atualizado_em' => '2026-05-16 14:05:00',
                        'resolvido_em' => null,
                        'nota_transicao' => 'Chamado assumido pela equipe de atendimento.',
                        'etiquetas' => ['Aliança', 'Tamanho'],
                        'conversa' => [
                            ['autor' => SupportMessage::AUTHOR_ROLE_RESELLER, 'em' => '2026-05-16 10:32:00', 'texto' => 'Boa tarde! Gostaria de saber o prazo de entrega do pedido ORD012548 — a cliente casa em agosto e quer confirmar a data.'],
                            ['autor' => SupportMessage::AUTHOR_ROLE_VELARO, 'em' => '2026-05-16 11:12:00', 'texto' => 'Olá, João! O pedido entra em produção assim que o lote 2026-W24 for quitado. A partir daí são 7 dias úteis até a loja.'],
                            ['autor' => SupportMessage::AUTHOR_ROLE_VELARO, 'interna' => true, 'em' => '2026-05-16 14:05:00', 'texto' => 'Conferir com a produção se o aro 18 do modelo Clássica está em estoque antes de confirmar o prazo.'],
                        ],
                    ],
                    [
                        'code' => 'SUP-2026-0820',
                        'subject' => 'Alteração de endereço de cobrança',
                        'category' => 'Financeiro',
                        'priority' => SupportTicket::PRIORITY_HIGH,
                        'status' => SupportTicket::STATUS_AWAITING_CUSTOMER,
                        'pedido' => null, 'cliente' => null,
                        'aberto_em' => '2026-05-15 14:18:00',
                        'primeira_resposta_em' => '2026-05-15 15:02:00',
                        'atualizado_em' => '2026-05-15 16:40:00',
                        'resolvido_em' => null,
                        'nota_transicao' => 'Aguardando o comprovante de endereço do revendedor.',
                        'etiquetas' => ['Financeiro'],
                        'conversa' => [
                            ['autor' => SupportMessage::AUTHOR_ROLE_RESELLER, 'em' => '2026-05-15 14:18:00', 'texto' => 'Precisamos atualizar o endereço de cobrança da empresa antes do vencimento do lote 2026-W24.'],
                            ['autor' => SupportMessage::AUTHOR_ROLE_VELARO, 'em' => '2026-05-15 15:02:00', 'texto' => 'Claro. Envie um comprovante de endereço em nome do CNPJ que atualizamos o cadastro ainda hoje.'],
                        ],
                    ],
                    [
                        'code' => 'SUP-2026-0818',
                        'subject' => 'Dúvida sobre personalização',
                        'category' => 'Personalização da loja',
                        'priority' => SupportTicket::PRIORITY_LOW,
                        'status' => SupportTicket::STATUS_ANSWERED,
                        'pedido' => null, 'cliente' => null,
                        'aberto_em' => '2026-05-13 16:20:00',
                        'primeira_resposta_em' => '2026-05-13 16:58:00',
                        'atualizado_em' => '2026-05-13 17:35:00',
                        'resolvido_em' => null,
                        'nota_transicao' => 'Orientação enviada ao revendedor.',
                        'etiquetas' => ['Vitrine'],
                        'conversa' => [
                            ['autor' => SupportMessage::AUTHOR_ROLE_RESELLER, 'em' => '2026-05-13 16:20:00', 'texto' => 'Como configuro o banner principal da vitrine? A imagem que subi ficou cortada no topo.'],
                            ['autor' => SupportMessage::AUTHOR_ROLE_VELARO, 'em' => '2026-05-13 16:58:00', 'texto' => 'O banner é recortado para 1920x600px. Suba a arte já nessa proporção que ela entra inteira.'],
                            ['autor' => SupportMessage::AUTHOR_ROLE_VELARO, 'interna' => true, 'em' => '2026-05-13 17:35:00', 'texto' => 'Revendedor já abriu chamado parecido no mês passado; avaliar um aviso de proporção na própria tela.'],
                        ],
                    ],
                ],
            ],

            // ── O vizinho de base ────────────────────────────────────────────────
            [
                'cadastro' => [
                    'protocol' => 'VEL-2026-0207',
                    'code' => 'ALC-0042',
                    'legal_name' => 'Aliança & Cia Comércio de Joias Ltda.',
                    'trade_name' => 'Aliança & Cia',
                    'cnpj' => '24.876.310/0001-44',
                    'state_registration' => '905.318.774.220',
                    'contact_name' => 'Renata Bittencourt',
                    'contact_cpf' => '640.128.375-09',
                    'email' => 'contato@aliancaecia.com.br',
                    'phone' => '(41) 3322-8140',
                    'whatsapp' => '(41) 99814-2207',
                    'postal_code' => '80020-320',
                    'street' => 'Rua Marechal Deodoro',
                    'street_number' => '507',
                    'district' => 'Centro',
                    'city' => 'Curitiba',
                    'state' => 'PR',
                    'registration_type' => Reseller::REGISTRATION_TYPE_MANUAL,
                    'approved_at' => '2026-02-27 16:05:00',
                ],
                'acesso' => [
                    'name' => 'Renata Bittencourt',
                    'email' => 'lojista2@velaro.test',
                    'phone' => '(41) 99814-2207',
                    'document' => '640.128.375-09',
                ],
                'loja' => [
                    'name' => 'Aliança & Cia',
                    'slogan' => 'O par certo para o seu sim.',
                    'slug' => 'alianca-e-cia',
                    // Sem domínio próprio: a rota padrão da vitrine é /loja/{slug}.
                    'domain' => null,
                    'phone' => '(41) 3322-8140',
                    'whatsapp' => '(41) 99814-2207',
                    'email' => 'contato@aliancaecia.com.br',
                    'address' => 'Rua Marechal Deodoro, 507 - Centro, Curitiba - PR',
                    'color_primary' => '#1F3A5F',
                    'color_secondary' => '#C9A227',
                    'own_brand_only' => false,
                    'hide_supplier_brand' => false,
                    'published_at' => '2026-03-09 10:22:00',
                ],
                'precos' => [
                    'multiplier' => 3.20,
                    'margin_global' => 45.00,
                    'margin_min' => 35.00,
                    'margin_ideal' => 45.00,
                    'margin_max' => 55.00,
                    'colecao_excecao' => null,
                    'multiplier_excecao' => null,
                ],
                'clientes' => [
                    ['name' => 'Beatriz Nogueira', 'document' => '208.417.665-32', 'phone' => '(41) 99655-2130', 'email' => 'beatriz.nogueira@email.com', 'postal_code' => '80730-000', 'address' => 'Avenida Sete de Setembro, 4214', 'city' => 'Curitiba', 'state' => 'PR', 'birth_date' => '1993-04-11', 'wedding_date' => '2026-10-17', 'relationship_date' => '2022-05-28', 'cadastrado_em' => '2026-04-05 10:40:00'],
                    ['name' => 'Marcelo Camargo', 'document' => '417.208.556-71', 'phone' => '(41) 98420-7711', 'email' => 'marcelo.camargo@email.com', 'postal_code' => '82530-200', 'address' => 'Rua Nossa Senhora da Luz, 918', 'city' => 'Curitiba', 'state' => 'PR', 'birth_date' => '1989-10-03', 'relationship_date' => '2021-09-04', 'cadastrado_em' => '2026-04-19 09:14:00'],
                    ['name' => 'Patrícia Rezende', 'document' => '556.071.284-18', 'phone' => '(47) 99133-6688', 'email' => 'patricia.rezende@email.com', 'postal_code' => '89201-100', 'address' => 'Rua XV de Novembro, 1102', 'city' => 'Joinville', 'state' => 'SC', 'birth_date' => '1991-06-22', 'wedding_date' => '2026-12-05', 'cadastrado_em' => '2026-05-02 15:58:00'],
                ],
                'lotes' => [
                    [
                        'code' => 'LOTE-2026-W24-ALC0042',
                        'cut_date' => '2026-05-21',
                        'due_date' => '2026-05-28',
                        'pago' => false,
                        'paid_at' => null,
                        'meio' => Payment::METHOD_BANK_TRANSFER,
                        'identificador_externo' => 'TED-2026-0521-000418',
                    ],
                ],
                'pedidos' => [
                    [
                        'numero' => 'ORD013101', 'referencia' => 'ALC-2026-041', 'cliente' => 0,
                        'lote' => 'LOTE-2026-W24-ALC0042',
                        'operacional' => Order::OPERATIONAL_STATUS_REGISTERED,
                        'pagamento' => Order::PAYMENT_STATUS_PENDING,
                        'feito_em' => '2026-05-18 09:15:00', 'previsao' => '2026-05-25',
                        'itens' => [
                            ['sku' => 'VL-FS-07', 'aro' => 18, 'quantidade' => 2],
                        ],
                    ],
                    [
                        'numero' => 'ORD013102', 'referencia' => 'ALC-2026-042', 'cliente' => 1,
                        'lote' => 'LOTE-2026-W24-ALC0042',
                        'operacional' => Order::OPERATIONAL_STATUS_REGISTERED,
                        'pagamento' => Order::PAYMENT_STATUS_AWAITING_CLEARANCE,
                        'feito_em' => '2026-05-19 15:40:00', 'previsao' => '2026-05-26',
                        'itens' => [
                            ['sku' => 'VL-CL-03', 'aro' => 20, 'quantidade' => 2, 'gravacao' => 'B & M 19.05.2026', 'gravacao_data' => '2026-10-17'],
                        ],
                    ],
                ],
                'chamados' => [
                    [
                        'code' => 'SUP-2026-0817',
                        'subject' => 'Pedido não chegou na data prevista',
                        'category' => 'Pedidos',
                        'priority' => SupportTicket::PRIORITY_HIGH,
                        'status' => SupportTicket::STATUS_OPEN,
                        'pedido' => 'ORD013101', 'cliente' => 0,
                        'aberto_em' => '2026-05-25 08:44:00',
                        'primeira_resposta_em' => null,
                        'atualizado_em' => '2026-05-25 08:44:00',
                        'resolvido_em' => null,
                        'nota_transicao' => null,
                        'etiquetas' => ['Aliança'],
                        'conversa' => [
                            ['autor' => SupportMessage::AUTHOR_ROLE_RESELLER, 'em' => '2026-05-25 08:44:00', 'texto' => 'O pedido ORD013101 estava previsto para ontem e ainda não chegou. A cliente já veio à loja duas vezes.'],
                        ],
                    ],
                ],
            ],
        ];
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
