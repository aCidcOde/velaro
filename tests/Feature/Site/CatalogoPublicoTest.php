<?php

/*
[Modulo: tests/Feature/Site]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Cobre a tela 1.3: grade filtravel, detalhe do modelo e o bloqueio de products.price na rota publica.
*/

namespace Tests\Feature\Site;

use App\Models\Category;
use App\Models\Finish;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CatalogoPublicoTest extends TestCase
{
    use RefreshDatabase;

    /** Custo B2B improvavel de aparecer por acaso no HTML. */
    private const CUSTO_B2B = 1234.56;

    protected function setUp(): void
    {
        parent::setUp();

        // As rotas do Velaro so entram no bootstrap quando os quatro ambientes
        // estiverem prontos; ate la o teste carrega o arquivo por conta propria.
        if (! Route::has('site.catalogo')) {
            Route::middleware('web')->group(base_path('routes/velaro.php'));

            // Rotas registradas depois do boot so entram na busca por nome
            // quando o lookup e refeito — e o que o RouteServiceProvider faz.
            Route::getRoutes()->refreshNameLookups();
        }
    }

    public function test_catalogo_lista_os_modelos_ativos_da_fabrica(): void
    {
        $this->montarCatalogo();

        $resposta = $this->get(route('site.catalogo'));

        $resposta->assertOk();
        $resposta->assertSee('CATÁLOGO VELARO');
        $resposta->assertSee('VL-DM-01');
        $resposta->assertSee('Prata 950 | Diamantada', false);
        $resposta->assertSee('5mm | Acabamento polido', false);
    }

    public function test_catalogo_ignora_produto_inativo_e_produto_sem_colecao(): void
    {
        $this->montarCatalogo();

        Product::factory()->velaroCatalog()->create([
            'name' => 'Rascunho', 'slug' => 'rascunho', 'sku' => 'VL-XX-99', 'is_active' => false,
        ]);
        Product::factory()->create(['name' => 'Item do scaffold', 'slug' => 'item-scaffold', 'sku' => 'SKU-0001']);

        $resposta = $this->get(route('site.catalogo'));

        $resposta->assertOk();
        $resposta->assertDontSee('VL-XX-99');
        $resposta->assertDontSee('SKU-0001');
    }

    public function test_a_lista_publica_nunca_serializa_o_custo_b2b(): void
    {
        $this->montarCatalogo();

        $resposta = $this->get(route('site.catalogo'));

        $resposta->assertOk();
        $this->assertSemPrecoNoHtml($resposta->getContent());

        foreach ($resposta->viewData('produtos')->items() as $produto) {
            $this->assertArrayNotHasKey('price', $produto->getAttributes());
        }

        foreach ($resposta->viewData('cartoes') as $cartao) {
            $this->assertArrayNotHasKey('price', $cartao);
            $this->assertArrayNotHasKey('preco', $cartao);
        }
    }

    public function test_o_detalhe_publico_nunca_serializa_o_custo_b2b(): void
    {
        $produto = $this->montarCatalogo();

        $resposta = $this->get(route('site.produto', $produto->slug));

        $resposta->assertOk();
        $this->assertSemPrecoNoHtml($resposta->getContent());
        $this->assertArrayNotHasKey('price', $resposta->viewData('produto')->getAttributes());
        $resposta->assertSee('Condição comercial liberada após a aprovação do cadastro.', false);
    }

    public function test_o_detalhe_mostra_ficha_tecnica_e_disponibilidade_por_aro(): void
    {
        $produto = $this->montarCatalogo();

        $resposta = $this->get(route('site.produto', $produto->slug));

        $resposta->assertOk();
        $resposta->assertSee('Ref. VL-DM-01 · Prata 950 · Diamantada · 5mm', false);
        $resposta->assertSee('Ficha técnica', false);
        $resposta->assertSee('Até 7 dias úteis', false);
        $resposta->assertSee('4,2 g por peça (5mm, aro 18)', false);
        $resposta->assertSee('Aros disponíveis', false);
        $resposta->assertSee('Aro 16');
        $resposta->assertSee('Aro 18');
        $resposta->assertSee('Quero ser revendedor');
    }

    public function test_o_detalhe_de_produto_inativo_devolve_404(): void
    {
        $produto = $this->montarCatalogo();
        $produto->update(['is_active' => false]);

        $this->get(route('site.produto', $produto->slug))->assertNotFound();
    }

    public function test_a_colecao_filtra_a_grade_pela_rota(): void
    {
        $this->montarCatalogo();
        $outra = ProductCollection::factory()->create(['name' => 'Urbana', 'slug' => 'urbana', 'is_active' => true, 'position' => 2]);
        Product::factory()->velaroCatalog()->create([
            'name' => 'Urbana', 'slug' => 'urbana', 'sku' => 'VL-UB-04', 'collection_id' => $outra->getKey(),
        ]);

        $this->get(route('site.catalogo', 'diamond'))
            ->assertOk()
            ->assertSee('VL-DM-01')
            ->assertDontSee('VL-UB-04');
    }

    public function test_colecao_inexistente_devolve_404(): void
    {
        $this->montarCatalogo();

        $this->get(route('site.catalogo', 'nao-existe'))->assertNotFound();
    }

    public function test_a_colecao_vinda_do_select_volta_para_a_url_canonica(): void
    {
        $this->montarCatalogo();

        $this->get(route('site.catalogo').'?colecao=diamond&material=prata-950')
            ->assertRedirect(route('site.catalogo', ['colecao' => 'diamond', 'material' => 'prata-950']));

        $this->get(route('site.catalogo').'?colecao=')
            ->assertRedirect(route('site.catalogo'));
    }

    public function test_os_filtros_de_material_acabamento_largura_e_formato_estreitam_a_grade(): void
    {
        $this->montarCatalogo();
        $ouro = Material::factory()->create(['name' => 'Ouro Amarelo 18k', 'slug' => 'ouro-amarelo-18k', 'position' => 2]);
        $polida = Finish::factory()->create(['name' => 'Polida', 'slug' => 'polida', 'position' => 2]);
        Product::factory()->velaroCatalog()->create([
            'name' => 'Clássica', 'slug' => 'classica', 'sku' => 'VL-CL-03',
            'collection_id' => ProductCollection::query()->value('id'),
            'material_id' => $ouro->getKey(), 'finish_id' => $polida->getKey(),
            'width_mm' => 4, 'shape' => 'Anatômica',
        ]);

        $this->get(route('site.catalogo', ['material' => 'prata-950']))->assertOk()->assertSee('VL-DM-01')->assertDontSee('VL-CL-03');
        $this->get(route('site.catalogo', ['acabamento' => 'polida']))->assertOk()->assertSee('VL-CL-03')->assertDontSee('VL-DM-01');
        $this->get(route('site.catalogo', ['largura' => '5']))->assertOk()->assertSee('VL-DM-01')->assertDontSee('VL-CL-03');
        $this->get(route('site.catalogo', ['formato' => 'Anatômica']))->assertOk()->assertSee('VL-CL-03')->assertDontSee('VL-DM-01');
    }

    public function test_a_busca_procura_por_nome_sku_material_e_acabamento(): void
    {
        $this->montarCatalogo();

        $this->get(route('site.catalogo', ['q' => 'VL-DM']))->assertOk()->assertSee('VL-DM-01');
        $this->get(route('site.catalogo', ['q' => 'Prata']))->assertOk()->assertSee('VL-DM-01');
        $this->get(route('site.catalogo', ['q' => 'esmeralda']))
            ->assertOk()
            ->assertDontSee('VL-DM-01')
            ->assertSee('Nenhum modelo encontrado com esses filtros.', false);
    }

    public function test_a_grade_pagina_de_doze_em_doze_como_o_prototipo(): void
    {
        $produto = $this->montarCatalogo();

        // Treze modelos: a grade 6x2 do prototipo enche a primeira pagina e sobra um.
        Product::factory()->velaroCatalog()->count(12)->sequence(fn ($sequencia): array => [
            'sku' => sprintf('VL-PG-%02d', $sequencia->index + 1),
            'slug' => 'pg-'.($sequencia->index + 1),
            'collection_id' => $produto->getAttribute('collection_id'),
        ])->create();

        $primeira = $this->get(route('site.catalogo'));
        $primeira->assertOk();
        $primeira->assertSee('Exibindo 1 a 12 de 13 modelos', false);
        $this->assertSame(12, substr_count((string) $primeira->getContent(), 'class="prod"'));

        $segunda = $this->get(route('site.catalogo', ['page' => 2]));
        $segunda->assertOk();
        $segunda->assertSee('VL-PG-12');
        $this->assertSame(1, substr_count((string) $segunda->getContent(), 'class="prod"'));
    }

    public function test_a_paginacao_preserva_os_filtros(): void
    {
        $produto = $this->montarCatalogo();

        Product::factory()->velaroCatalog()->count(12)->sequence(fn ($sequencia): array => [
            'sku' => sprintf('VL-PG-%02d', $sequencia->index + 1),
            'slug' => 'pg-'.($sequencia->index + 1),
            'collection_id' => $produto->getAttribute('collection_id'),
            'material_id' => $produto->getAttribute('material_id'),
        ])->create();

        $resposta = $this->get(route('site.catalogo', ['material' => 'prata-950']));

        $resposta->assertOk();
        $resposta->assertSee('material=prata-950', false);
    }

    /**
     * A capa e a imagem marcada como primaria, mesmo quando outra foto vem antes
     * dela na ordem — o `position` desempata, nao manda.
     */
    public function test_a_capa_e_a_imagem_primaria_mesmo_fora_da_primeira_posicao(): void
    {
        $produto = $this->montarCatalogo();

        ProductImage::query()->where('product_id', $produto->getKey())->delete();

        ProductImage::factory()->create([
            'product_id' => $produto->getKey(),
            'path' => 'images/aliancas/diamond-perfil.svg',
            'alt' => 'Diamond vista de perfil',
            'position' => 0,
            'is_primary' => false,
        ]);
        ProductImage::factory()->create([
            'product_id' => $produto->getKey(),
            'path' => 'images/aliancas/diamond.svg',
            'alt' => 'Diamond vista frontal',
            'position' => 3,
            'is_primary' => true,
        ]);

        $resposta = $this->get(route('site.produto', $produto->slug));

        $resposta->assertOk();
        $this->assertSame('Diamond vista frontal', $resposta->viewData('capa')->alt);

        // A grade usa a mesma ordenacao: o card mostra a mesma foto do detalhe.
        $lista = $this->get(route('site.catalogo'));
        $lista->assertOk();
        $this->assertSame('Diamond vista frontal', $lista->viewData('cartoes')[0]['imagem']['alt']);
    }

    /**
     * "Modelos relacionados" tem quatro cards no prototipo. A colecao vem
     * primeiro, mas quando ela e pequena a secao completa com o resto do
     * catalogo em vez de aparecer pela metade.
     */
    public function test_os_relacionados_enchem_os_quatro_cards_do_prototipo(): void
    {
        $produto = $this->montarCatalogo();
        $solta = ProductCollection::factory()->create(['name' => 'Urbana', 'slug' => 'urbana', 'is_active' => true, 'position' => 2]);

        // Um unico vizinho de colecao; os outros tres vem do catalogo geral.
        Product::factory()->velaroCatalog()->create([
            'name' => 'Diamond Heart', 'slug' => 'diamond-heart', 'sku' => 'VL-DM-09',
            'collection_id' => $produto->getAttribute('collection_id'),
            'category_id' => $produto->getAttribute('category_id'),
        ]);
        Product::factory()->velaroCatalog()->count(3)->sequence(fn ($sequencia): array => [
            'sku' => sprintf('VL-OT-%02d', $sequencia->index + 1),
            'slug' => 'outro-'.($sequencia->index + 1),
            'collection_id' => $solta->getKey(),
        ])->create();

        $resposta = $this->get(route('site.produto', $produto->slug));

        $resposta->assertOk();
        $relacionados = $resposta->viewData('relacionados');
        $this->assertCount(4, $relacionados);
        // O vizinho de colecao continua sendo o primeiro da fila.
        $this->assertSame('VL-DM-09', $relacionados[0]['sku']);
        // E o proprio modelo nunca se recomenda.
        $this->assertNotContains('VL-DM-01', array_column($relacionados, 'sku'));
    }

    /**
     * `/catalogo` e publica e indexavel: link velho ou rastreador com parametro
     * torto tem de receber a grade, nunca um redirect de erro de validacao.
     */
    public function test_parametro_mal_formado_devolve_a_grade_em_vez_de_redirecionar(): void
    {
        $this->montarCatalogo();

        foreach (['largura=grossa', 'largura=500', 'page=abc', 'page=-3', 'q='.str_repeat('a', 400)] as $consulta) {
            $this->get(route('site.catalogo').'?'.$consulta)
                ->assertOk()
                ->assertSee('CATÁLOGO VELARO');
        }

        // Parametro torto e ausencia de filtro: a grade continua completa.
        $this->get(route('site.catalogo').'?largura=grossa')->assertSee('VL-DM-01');
    }

    /**
     * Um modelo completo do prototipo: colecao Diamond, prata 950, diamantada, 5mm.
     */
    private function montarCatalogo(): Product
    {
        $colecao = ProductCollection::factory()->create(['name' => 'Diamond', 'slug' => 'diamond', 'is_active' => true, 'position' => 1]);
        $categoria = Category::factory()->create(['name' => 'Alianças Tradicionais', 'slug' => 'aliancas-tradicionais', 'position' => 1]);
        $material = Material::factory()->create(['name' => 'Prata 950', 'slug' => 'prata-950', 'position' => 1]);
        $acabamento = Finish::factory()->create(['name' => 'Diamantada', 'slug' => 'diamantada', 'position' => 1]);

        $produto = Product::factory()->velaroCatalog()->create([
            'name' => 'Diamond',
            'slug' => 'diamond',
            'sku' => 'VL-DM-01',
            'description' => 'Aliança de perfil reto em prata 950 com superfície diamantada.',
            'collection_id' => $colecao->getKey(),
            'category_id' => $categoria->getKey(),
            'material_id' => $material->getKey(),
            'finish_id' => $acabamento->getKey(),
            'width_mm' => 5,
            'shape' => 'Reta',
            'allows_engraving' => true,
            'engraving_max_chars' => 20,
            'delivery_days' => 7,
            'price' => self::CUSTO_B2B,
            'is_active' => true,
            'meta' => [
                'peso_aproximado_g' => 4.2,
                'garantia_meses' => 12,
                'origem' => 'Fabricação própria Velaro',
            ],
        ]);

        ProductImage::factory()->create([
            'product_id' => $produto->getKey(),
            'path' => 'images/aliancas/diamond.svg',
            'alt' => 'Diamond — Prata 950 | Diamantada · 5mm',
            'position' => 0,
            'is_primary' => true,
        ]);

        foreach ([16, 18, 20] as $aro) {
            ProductVariant::factory()->create([
                'product_id' => $produto->getKey(),
                'sku' => 'VL-DM-01-'.$aro,
                'ring_size' => (string) $aro,
                'is_active' => true,
            ]);
        }

        return $produto;
    }

    private function assertSemPrecoNoHtml(string $html): void
    {
        foreach (['1234.56', '1234,56', '1.234,56', 'R$ 1.234,56'] as $preco) {
            $this->assertStringNotContainsString($preco, $html, 'O custo B2B vazou para a rota publica.');
        }
    }
}
