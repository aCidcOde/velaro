<?php

/*
[Modulo: tests/Feature/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Cobre a tela 2.2: custo B2B visivel, disponibilidade lida do cofre, filtros, drawer, exportacao e nao vazamento de preco alheio.
*/

namespace Tests\Feature\Portal;

use App\Models\Finish;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Reseller;
use App\Models\ResellerPriceRule;
use App\Models\StockItem;
use App\Models\StockLocation;
use App\Models\User;
use App\Services\Portal\CatalogoRevendedorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * O catálogo revendedor é a única tela do portal que mostra `products.price`, o
 * custo B2B. Duas coisas precisam ser verdade ao mesmo tempo, e os casos abaixo
 * afirmam as duas:
 *
 * 1. o custo **aparece** — é a tela em que o lojista vê quanto paga (regra 1);
 * 2. o preço que **outro lojista** pratica não aparece: o catálogo é o mesmo para
 *    todo mundo, e a regra de preço de cada um fica na sua conta.
 */
class CatalogoRevendedorTest extends TestCase
{
    /** Custo B2B improvável de aparecer por acaso no HTML. */
    private const CUSTO_CLASSICA = 1234.56;

    private const CUSTO_TRABALHADA = 987.65;

    private const CUSTO_ESGOTADA = 741.23;

    use RefreshDatabase;

    private Reseller $tomazelli;

    private Reseller $vizinho;

    private User $lojista;

    private StockLocation $cofre;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tomazelli = Reseller::factory()->approved()->create(['trade_name' => 'Tomazelli Alianças']);
        $this->vizinho = Reseller::factory()->approved()->create(['trade_name' => 'Aliança & Cia']);
        $this->lojista = User::factory()->forReseller($this->tomazelli)->create();
        $this->cofre = StockLocation::factory()->defaultLocation()->create();
    }

    public function test_o_catalogo_mostra_o_custo_b2b_de_cada_peca(): void
    {
        $this->montarCatalogo();

        $resposta = $this->actingAs($this->lojista)->get(route('portal.catalogo'));

        $resposta->assertOk();
        $resposta->assertSee('Catálogo Revendedor');
        $resposta->assertSee('SKU: ALC4-4MM');
        $resposta->assertSee('R$ 1.234,56');
        $resposta->assertSee('exclusivos para revendedores', false);
    }

    public function test_a_disponibilidade_vem_do_saldo_do_cofre(): void
    {
        $this->montarCatalogo();

        $resposta = $this->actingAs($this->lojista)->get(route('portal.catalogo'));

        $resposta->assertOk();
        $resposta->assertSee('Em estoque');
        $resposta->assertSee('Sob encomenda');
        // Peça de pronta-entrega com o cofre zerado não é nenhum dos dois: é o
        // terceiro estado, e escondê-lo faria o lojista prometer prazo errado.
        $resposta->assertSee('Sem saldo em cofre');
    }

    public function test_os_kpis_batem_com_a_grade(): void
    {
        $this->montarCatalogo();

        $indicadores = $this->indicadores();

        $this->assertSame(3, $indicadores['Total de produtos']);
        $this->assertSame(1, $indicadores['Em estoque']);
        $this->assertSame(1, $indicadores['Sob encomenda']);
        $this->assertSame(1, $indicadores['Coleções ativas']);
    }

    public function test_produto_inativo_e_produto_sem_colecao_ficam_de_fora(): void
    {
        $this->montarCatalogo();

        Product::factory()->create(['sku' => 'INATIVA-4MM', 'is_active' => false, 'user_id' => null]);
        Product::factory()->create(['sku' => 'SEMCOLECAO-4MM', 'collection_id' => null, 'user_id' => null]);

        $resposta = $this->actingAs($this->lojista)->get(route('portal.catalogo'));

        $resposta->assertOk();
        $resposta->assertDontSee('INATIVA-4MM');
        $resposta->assertDontSee('SEMCOLECAO-4MM');
    }

    public function test_a_busca_encontra_pelo_sku_do_aro(): void
    {
        $this->montarCatalogo();

        // O que vem impresso na etiqueta é o SKU da variante, não o do modelo.
        $resposta = $this->actingAs($this->lojista)->get(route('portal.catalogo', ['q' => 'ALC4-4MM-A18']));

        $resposta->assertOk();
        $resposta->assertSee('SKU: ALC4-4MM');
        $resposta->assertDontSee('SKU: ALTA-6MM');
    }

    public function test_o_filtro_de_disponibilidade_recorta_a_grade(): void
    {
        $this->montarCatalogo();

        $resposta = $this->actingAs($this->lojista)->get(route('portal.catalogo', ['disponibilidade' => 'encomenda']));

        $resposta->assertOk();
        $resposta->assertSee('SKU: ALTA-6MM');
        $resposta->assertDontSee('SKU: ALC4-4MM');
    }

    public function test_os_filtros_de_taxonomia_e_largura_recortam_a_grade(): void
    {
        $this->montarCatalogo();

        $resposta = $this->actingAs($this->lojista)->get(route('portal.catalogo', [
            'acabamento' => 'polido',
            'largura' => '4',
        ]));

        $resposta->assertOk();
        $resposta->assertSee('SKU: ALC4-4MM');
        $resposta->assertDontSee('SKU: ALTA-6MM');
    }

    public function test_filtro_sem_resultado_devolve_a_tela_com_o_vazio(): void
    {
        $this->montarCatalogo();

        $resposta = $this->actingAs($this->lojista)->get(route('portal.catalogo', ['q' => 'peça-que-não-existe']));

        $resposta->assertOk();
        $resposta->assertSee('Nenhuma peça encontrada com esses filtros.');
    }

    public function test_valor_invalido_na_query_nao_derruba_a_tela(): void
    {
        $this->montarCatalogo();

        // Link velho, `select` de uma coleção desativada, rastreador: nada disso
        // pode virar 422 numa tela de trabalho do lojista.
        $resposta = $this->actingAs($this->lojista)->get(route('portal.catalogo', [
            'ordenar' => 'preco',
            'disponibilidade' => 'talvez',
            'largura' => 'grossa',
            'page' => 'abc',
        ]));

        $resposta->assertOk();
        $resposta->assertSee('SKU: ALC4-4MM');
    }

    public function test_a_ordenacao_por_custo_muda_a_ordem_da_grade(): void
    {
        $this->montarCatalogo();

        $resposta = $this->actingAs($this->lojista)->get(route('portal.catalogo', ['ordenar' => 'custo_asc']));

        $resposta->assertOk();
        $resposta->assertSeeInOrder(['SKU: ALESG-5MM', 'SKU: ALTA-6MM', 'SKU: ALC4-4MM']);
    }

    public function test_o_drawer_abre_pela_referencia_com_o_custo_e_o_aviso(): void
    {
        $this->montarCatalogo();

        $resposta = $this->actingAs($this->lojista)->get(route('portal.catalogo', ['ver' => 'ALC4-4MM']));

        $resposta->assertOk();
        $resposta->assertSee('Ref. ALC4-4MM');
        $resposta->assertSee('Custo para o lojista');
        $resposta->assertSee('Preço interno. Não exibir a clientes.');
        $resposta->assertSee('Prazo de entrega');
        // Saldo por aro: o portal lê stock_items e nunca escreve nele.
        $resposta->assertSee('Disponibilidade por aro');
        $resposta->assertSee('Aro 18');
    }

    public function test_referencia_desconhecida_nao_derruba_a_grade(): void
    {
        $this->montarCatalogo();

        $resposta = $this->actingAs($this->lojista)->get(route('portal.catalogo', ['ver' => 'NAO-EXISTE']));

        $resposta->assertOk();
        $resposta->assertSee('Escolha um modelo na grade');
        $resposta->assertSee('SKU: ALC4-4MM');
    }

    public function test_a_exportacao_leva_o_recorte_da_tela_com_o_custo(): void
    {
        $this->montarCatalogo();

        $resposta = $this->actingAs($this->lojista)->get(route('portal.catalogo', [
            'disponibilidade' => 'encomenda',
            'exportar' => 'csv',
        ]));

        $resposta->assertOk();
        $resposta->assertDownload();

        $csv = $resposta->streamedContent();

        $this->assertStringContainsString('Custo Velaro (R$)', $csv);
        $this->assertStringContainsString('ALTA-6MM', $csv);
        $this->assertStringContainsString('987,65', $csv);
        // O recorte é o mesmo da tela: o filtro vale para o arquivo também.
        $this->assertStringNotContainsString('ALC4-4MM', $csv);
    }

    public function test_a_regra_de_preco_de_outro_lojista_nao_aparece_no_catalogo(): void
    {
        $this->montarCatalogo();

        $produto = Product::query()->where('sku', 'ALC4-4MM')->firstOrFail();

        // O concorrente vende a mesma peça com o multiplicador dele. Esse número
        // é o que não pode vazar — o custo Velaro, sim, é igual para os dois.
        ResellerPriceRule::factory()->forProduct($produto)->create([
            'reseller_id' => $this->vizinho->getKey(),
            'value' => 4.44,
        ]);

        $resposta = $this->actingAs($this->lojista)->get(route('portal.catalogo'));

        $resposta->assertOk();
        $resposta->assertSee('R$ 1.234,56');
        $resposta->assertDontSee('4,44');
        $resposta->assertDontSee('Aliança &amp; Cia', false);
    }

    public function test_o_catalogo_e_o_mesmo_para_os_dois_lojistas(): void
    {
        // O catálogo é o da fábrica: não há `reseller_id` em `products`, e por
        // isso a grade não muda de dono para dono. O que muda é o preço de
        // venda que cada um define — e esse não está nesta tela.
        $this->montarCatalogo();

        $doVizinho = User::factory()->forReseller($this->vizinho)->create();

        $minha = $this->actingAs($this->lojista)->get(route('portal.catalogo'));
        $dele = $this->actingAs($doVizinho)->get(route('portal.catalogo'));

        $minha->assertOk()->assertSee('R$ 1.234,56');
        $dele->assertOk()->assertSee('R$ 1.234,56');
    }

    public function test_quem_nao_e_revendedor_aprovado_nao_ve_o_custo(): void
    {
        $this->montarCatalogo();

        // Visitante sem sessão volta para o login; a ordem importa, porque
        // `actingAs` não é desfeito no meio do teste.
        $this->get(route('portal.catalogo'))->assertRedirect(route('login'));

        // Cadastro ainda em análise: 403 sobre o ambiente inteiro, não 404 —
        // aqui a negativa não é sobre a existência de um registro.
        $pendente = User::factory()->forReseller(Reseller::factory()->pending()->create())->create();

        $this->actingAs($pendente)->get(route('portal.catalogo'))->assertForbidden();
    }

    /**
     * Três peças que cobrem os três estados de disponibilidade: pronta-entrega
     * com saldo, sob encomenda e pronta-entrega com o cofre zerado.
     */
    private function montarCatalogo(): void
    {
        $colecao = ProductCollection::factory()->named('Tradicional')->create();
        $ouro = Material::factory()->named('Ouro 18k')->create();
        $polido = Finish::factory()->named('Polido')->create();
        $fosco = Finish::factory()->named('Fosco')->create();

        $classica = $this->produto('ALC4-4MM', 'Aliança Clássica 4mm', self::CUSTO_CLASSICA, 4, $colecao, $ouro, $polido);
        $this->aro($classica, 18, 12);

        $trabalhada = $this->produto('ALTA-6MM', 'Aliança Trabalhada 6mm', self::CUSTO_TRABALHADA, 6, $colecao, $ouro, $fosco, true);
        $this->aro($trabalhada, 20, 0);

        $esgotada = $this->produto('ALESG-5MM', 'Aliança Fina 5mm', self::CUSTO_ESGOTADA, 5, $colecao, $ouro, $fosco);
        $this->aro($esgotada, 16, 0);
    }

    private function produto(
        string $sku,
        string $nome,
        float $custo,
        float $largura,
        ProductCollection $colecao,
        Material $material,
        Finish $acabamento,
        bool $sobEncomenda = false,
    ): Product {
        $produto = Product::factory()->create([
            'user_id' => null,
            'name' => $nome,
            'slug' => str($nome)->slug()->value(),
            'sku' => $sku,
            'price' => $custo,
            'collection_id' => $colecao->getKey(),
            'material_id' => $material->getKey(),
            'finish_id' => $acabamento->getKey(),
            'width_mm' => $largura,
            'delivery_days' => 2,
            'is_made_to_order' => $sobEncomenda,
            'is_active' => true,
        ]);

        ProductImage::factory()->forProduct($produto)->primary()->create();

        return $produto;
    }

    private function aro(Product $produto, int $aro, int $saldo): void
    {
        $variante = ProductVariant::factory()->forProduct($produto)->withRingSize($aro)->create([
            'sku' => $produto->sku.'-A'.$aro,
            'is_active' => true,
        ]);

        StockItem::factory()->forVariant($variante)->atLocation($this->cofre)->create([
            'on_hand' => $saldo,
            'reserved' => 0,
            'available' => $saldo,
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function indicadores(): array
    {
        $this->actingAs($this->lojista);

        $valores = [];

        foreach (app(CatalogoRevendedorService::class)->indicadores() as $indicador) {
            $valores[$indicador['rotulo']] = $indicador['valor'];
        }

        return $valores;
    }
}
