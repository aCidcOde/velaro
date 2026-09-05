<?php

/*
[Modulo: tests/Feature/Vitrine]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Cobre as telas 2.9 e 2.10 da vitrine: publicacao, curadoria do catalogo, preco B2C, ausencia de marca Velaro e de pagamento.
*/

namespace Tests\Feature\Vitrine;

use App\Models\Category;
use App\Models\Favorite;
use App\Models\Finish;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Reseller;
use App\Models\ResellerPriceRule;
use App\Models\ResellerPriceSetting;
use App\Models\ResellerStore;
use App\Models\StockItem;
use App\Services\Vitrine\VitrineCatalogoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VitrineLojaTest extends TestCase
{
    use RefreshDatabase;

    private Reseller $tomazelli;

    private ResellerStore $loja;

    private Category $aliancas;

    private Category $solitarios;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tomazelli = Reseller::factory()->approved()->create(['trade_name' => 'Tomazelli Alianças']);

        $this->loja = ResellerStore::factory()->published()->create([
            'reseller_id' => $this->tomazelli->getKey(),
            'name' => 'Tomazelli Alianças',
            'slogan' => 'Símbolo de amor. Promessa para a vida toda.',
            'slug' => 'tomazelli-aliancas',
            'whatsapp' => '(11) 98888-2020',
            'phone' => '(11) 3333-2020',
            'email' => 'contato@tomazellialiancas.com.br',
            'address' => 'Rua das Alianças, 123 - Centro, São Paulo - SP',
        ]);

        // Multiplicador 2x sem arredondamento: o preço da vitrine fica previsível
        // e a distância entre custo e preço fica visível a olho nu no assert.
        ResellerPriceSetting::factory()->create([
            'reseller_id' => $this->tomazelli->getKey(),
            'pricing_model' => ResellerPriceSetting::PRICING_MODEL_MULTIPLIER,
            'multiplier' => 2.00,
            'rounding' => ResellerPriceSetting::ROUNDING_NONE,
        ]);

        $this->aliancas = Category::factory()->named('Alianças')->create(['position' => 1]);
        $this->solitarios = Category::factory()->named('Solitários')->create(['position' => 2]);
    }

    /**
     * Peça do catálogo da fábrica, com ficha completa e um aro com saldo.
     */
    private function peca(string $nome, string $slug, float $custo, ?Category $categoria = null): Product
    {
        $produto = Product::factory()->create([
            'name' => $nome,
            'slug' => $slug,
            'price' => $custo,
            'is_active' => true,
            'category_id' => ($categoria ?? $this->aliancas)->getKey(),
            'material_id' => Material::factory()->named('Ouro 18k '.$slug)->create()->getKey(),
            'finish_id' => Finish::factory()->named('Polido '.$slug)->create()->getKey(),
            'width_mm' => 4,
            'shape' => 'Anatômica',
            'allows_engraving' => true,
            'engraving_max_chars' => 20,
        ]);

        $comSaldo = ProductVariant::factory()->forProduct($produto)->withRingSize(18)->create();
        StockItem::factory()->forVariant($comSaldo)->create(['on_hand' => 5, 'reserved' => 0, 'available' => 5]);

        return $produto;
    }

    private function urlDaLoja(array $parametros = []): string
    {
        return route('vitrine.index', [$this->loja, ...$parametros]);
    }

    // ───────────────────── publicação ─────────────────────

    public function test_vitrine_publicada_abre_para_visitante_sem_login(): void
    {
        $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);

        $this->get($this->urlDaLoja())
            ->assertOk()
            ->assertSee('Tomazelli Alianças')
            ->assertSee('Símbolo de amor. Promessa para a vida toda.')
            ->assertSee('Aliança Clássica 4mm');

        $this->assertGuest();
    }

    public function test_loja_desativada_devolve_404_e_nao_403(): void
    {
        $this->loja->update(['is_active' => false]);

        $this->get($this->urlDaLoja())->assertNotFound();
    }

    public function test_loja_nunca_publicada_devolve_404(): void
    {
        $this->loja->update(['published_at' => null]);

        $this->get($this->urlDaLoja())->assertNotFound();
    }

    public function test_ficha_de_loja_nao_publicada_tambem_devolve_404(): void
    {
        $peca = $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);
        $this->loja->update(['is_active' => false]);

        $this->get(route('vitrine.produto', ['store' => $this->loja, 'product' => $peca->slug]))
            ->assertNotFound();
    }

    /**
     * A página de erro também é tela da loja: um slug errado no tablet, ou um
     * link velho, não pode terminar num documento com a marca da fábrica.
     */
    public function test_pagina_de_404_nao_carrega_a_marca_do_fornecedor(): void
    {
        $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);

        $paginas = [
            $this->get('/loja/loja-que-nao-existe'),
            $this->get(route('vitrine.produto', ['store' => $this->loja, 'product' => 'peca-que-nao-existe'])),
        ];

        foreach ($paginas as $pagina) {
            $conteudo = $pagina->assertNotFound()->getContent();

            $this->assertIsString($conteudo);
            $this->assertStringNotContainsStringIgnoringCase('velaro', $conteudo);
            $this->assertStringNotContainsStringIgnoringCase('svd', $conteudo);
        }
    }

    public function test_slug_inexistente_devolve_404(): void
    {
        $this->get('/loja/loja-que-nao-existe')->assertNotFound();
    }

    // ───────────────────── regra 1: zero marca Velaro ─────────────────────

    public function test_vitrine_nao_exibe_marca_velaro_em_lugar_nenhum(): void
    {
        $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);

        $conteudo = $this->get($this->urlDaLoja())->assertOk()->getContent();

        $this->assertIsString($conteudo);
        // Nota: `Tests\TestCase` roda com `withoutVite()`, então as tags de asset
        // não entram neste HTML. O bundle servido em produção chama-se
        // `build/assets/velaro-*.css` — nome de arquivo de build, não texto de
        // tela, mas é a única ocorrência da marca no código-fonte da loja.
        $this->assertStringNotContainsStringIgnoringCase('velaro', $conteudo);
        $this->assertStringNotContainsStringIgnoringCase('svd', $conteudo);
        // O ícone da aba é o da loja (ou nenhum), nunca o da fábrica.
        $this->assertStringNotContainsString('/images/icons/favicon', $conteudo);
    }

    public function test_ficha_do_produto_nao_exibe_marca_velaro(): void
    {
        $peca = $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);

        $conteudo = $this->get(route('vitrine.produto', ['store' => $this->loja, 'product' => $peca->slug]))
            ->assertOk()
            ->getContent();

        $this->assertIsString($conteudo);
        $this->assertStringNotContainsStringIgnoringCase('velaro', $conteudo);
    }

    public function test_descricao_do_catalogo_perde_a_frase_que_cita_o_fornecedor(): void
    {
        $peca = $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);
        $peca->update([
            'description' => 'Aliança de perfil reto em ouro 18k. '
                .'Produzida na fábrica própria da Velaro Alianças, com controle de peso peça a peça. '
                .'Acabamento polido.',
        ]);

        $this->get(route('vitrine.produto', ['store' => $this->loja, 'product' => $peca->slug]))
            ->assertOk()
            ->assertSee('Aliança de perfil reto em ouro 18k.')
            ->assertSee('Acabamento polido.')
            ->assertDontSee('fábrica própria');
    }

    /**
     * O nome da categoria já saía limpo — `rotuloSemFornecedor()` apaga o termo
     * e "Velaro Signature" vira a aba "Signature". O **slug** não passa por
     * filtro nenhum, e não pode passar: é a chave da rota. O resultado era uma
     * aba de texto limpo apontando para `?categoria=velaro-signature`, com a
     * marca da fábrica na barra de endereço do consumidor final. A migalha da
     * ficha repetia o mesmo `href`.
     *
     * Some a aba, não o catálogo: a peça continua em "Todos os produtos".
     */
    public function test_categoria_batizada_com_a_marca_do_fornecedor_sai_da_navegacao(): void
    {
        $daFabrica = Category::factory()->named('Velaro Signature')->create(['position' => 9]);

        $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);
        $assinada = $this->peca('Solitário Assinado', 'solitario-assinado', 300.00, $daFabrica);

        $vitrine = $this->get($this->urlDaLoja())->assertOk();
        $conteudo = $vitrine->getContent();

        $this->assertIsString($conteudo);
        // Nem o rótulo da aba, nem o `?categoria=` que ela carregava.
        $this->assertStringNotContainsStringIgnoringCase('velaro', $conteudo);
        // A peça daquela categoria continua exposta na grade.
        $vitrine->assertSee('Solitário Assinado');

        $ficha = $this->get(route('vitrine.produto', ['store' => $this->loja, 'product' => $assinada->slug]))
            ->assertOk();

        $conteudoDaFicha = $ficha->getContent();
        $this->assertIsString($conteudoDaFicha);
        $this->assertStringNotContainsStringIgnoringCase('velaro', $conteudoDaFicha);
        // A migalha perde só o degrau da categoria; a ficha abre normalmente.
        $ficha->assertSee('Solitário Assinado');
    }

    public function test_titulo_do_documento_leva_a_marca_da_loja(): void
    {
        $peca = $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);

        $this->get(route('vitrine.produto', ['store' => $this->loja, 'product' => $peca->slug]))
            ->assertOk()
            ->assertSee('<title>Aliança Clássica 4mm · Tomazelli Alianças</title>', false);
    }

    // ───────────────────── regra 2: preço B2C, nunca o custo B2B ─────────────────────

    public function test_preco_exibido_e_o_b2c_do_revendedor_e_nunca_o_custo_velaro(): void
    {
        $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);

        $this->get($this->urlDaLoja())
            ->assertOk()
            ->assertSee('R$ 200,00')
            ->assertDontSee('R$ 100,00');
    }

    /**
     * A mesma peça do mesmo catálogo, dois lojistas, duas margens: cada vitrine
     * mostra só o preço do dono dela.
     *
     * A margem é segredo comercial do revendedor, e o resolvedor é montado a
     * partir da **loja aberta** (`ResellerScope::for()`), não de um usuário
     * autenticado — aqui não há nenhum. Se o escopo escapasse, a vitrine de um
     * entregaria a política de preço do outro.
     */
    public function test_preco_de_um_lojista_nunca_aparece_na_vitrine_do_outro(): void
    {
        $peca = $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);

        $outro = Reseller::factory()->approved()->create(['trade_name' => 'Joalheria Aurora']);
        $outraLoja = ResellerStore::factory()->published()->create([
            'reseller_id' => $outro->getKey(),
            'name' => 'Joalheria Aurora',
            'slug' => 'joalheria-aurora',
        ]);
        ResellerPriceSetting::factory()->create([
            'reseller_id' => $outro->getKey(),
            'pricing_model' => ResellerPriceSetting::PRICING_MODEL_MULTIPLIER,
            'multiplier' => 5.00,
            'rounding' => ResellerPriceSetting::ROUNDING_NONE,
        ]);

        $this->get(route('vitrine.produto', ['store' => $this->loja, 'product' => $peca->slug]))
            ->assertOk()
            ->assertSee('R$ 200,00')
            ->assertDontSee('R$ 500,00');

        $this->get(route('vitrine.produto', ['store' => $outraLoja, 'product' => $peca->slug]))
            ->assertOk()
            ->assertSee('R$ 500,00')
            ->assertDontSee('R$ 200,00');
    }

    public function test_preco_respeita_a_cascata_de_regras_do_lojista(): void
    {
        $peca = $this->peca('Aliança Cravejada 4mm', 'alianca-cravejada-4mm', 100.00);

        // Regra por produto vence o padrão global do lojista.
        ResellerPriceRule::factory()->create([
            'reseller_id' => $this->tomazelli->getKey(),
            'scope' => ResellerPriceRule::SCOPE_PRODUCT,
            'product_id' => $peca->getKey(),
            'mode' => ResellerPriceRule::MODE_MANUAL,
            'value' => 349.00,
            'rounding' => ResellerPriceSetting::ROUNDING_NONE,
            'is_active' => true,
        ]);

        $this->get($this->urlDaLoja())
            ->assertOk()
            ->assertSee('R$ 349,00')
            ->assertDontSee('R$ 200,00');
    }

    public function test_custo_b2b_nao_chega_a_view_nem_nos_dados_da_pagina(): void
    {
        $peca = $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 137.45);

        $resposta = $this->get($this->urlDaLoja())->assertOk();

        // Nem o número formatado, nem o valor cru da coluna, nem o model com a
        // coluna dentro: a view recebe cartões, não `Product`.
        $resposta->assertDontSee('137,45')->assertDontSee('137.45');

        foreach ($resposta->original->getData() as $chave => $valor) {
            $this->assertNotInstanceOf(
                Product::class,
                $valor,
                "A view da vitrine recebeu um Product em `{$chave}` — `products.price` viaja junto."
            );
        }

        $this->assertSame(137.45, (float) $peca->refresh()->price);
    }

    public function test_show_prices_desligado_esconde_o_preco_na_grade_e_na_ficha(): void
    {
        $peca = $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);
        $this->loja->update(['show_prices' => false]);

        $this->get($this->urlDaLoja())
            ->assertOk()
            ->assertDontSee('R$ 200,00')
            ->assertSee('Consulte na loja');

        $this->get(route('vitrine.produto', ['store' => $this->loja, 'product' => $peca->slug]))
            ->assertOk()
            ->assertDontSee('R$ 200,00')
            ->assertSee('Consulte na loja');
    }

    // ───────────────────── regra 3: nenhum pagamento ─────────────────────

    public function test_vitrine_nao_oferece_pagamento_online(): void
    {
        $peca = $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);

        $conteudo = $this->get(route('vitrine.produto', ['store' => $this->loja, 'product' => $peca->slug]))
            ->assertOk()
            ->assertSee('O pagamento é realizado no caixa da loja.')
            // O botão leva ao carrinho da loja, não a um checkout.
            ->assertSee(route('vitrine.carrinho', $this->loja), false)
            ->getContent();

        $this->assertIsString($conteudo);

        foreach (['Pix', 'Cartão de crédito', 'Finalizar compra', 'Pagar agora', 'gateway'] as $termo) {
            $this->assertStringNotContainsStringIgnoringCase($termo, $conteudo);
        }

        // Nenhum formulário: a única ação da ficha é um link para o carrinho.
        $this->assertStringNotContainsString('<form', $conteudo);
    }

    public function test_retirada_na_loja_aparece_conforme_os_toggles(): void
    {
        $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);

        $this->get($this->urlDaLoja())
            ->assertOk()
            ->assertSee('Retirada exclusiva na loja.')
            ->assertSee('Seu pedido estará disponível para retirada na loja Tomazelli Alianças.');

        $this->loja->update(['pickup_only' => false, 'payment_in_store' => false]);

        $this->get($this->urlDaLoja())
            ->assertOk()
            ->assertSee('Entrega combinada com a loja.')
            ->assertDontSee('O pagamento é realizado no caixa da loja.');
    }

    // ───────────────────── curadoria do catálogo ─────────────────────

    public function test_sem_curadoria_a_vitrine_mostra_o_catalogo_ativo_inteiro(): void
    {
        $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);
        $this->peca('Aliança Fosca 6mm', 'alianca-fosca-6mm', 120.00);

        $this->get($this->urlDaLoja())
            ->assertOk()
            ->assertSee('Aliança Clássica 4mm')
            ->assertSee('Aliança Fosca 6mm');
    }

    public function test_produto_inativo_nunca_entra_na_vitrine(): void
    {
        $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);
        $fora = $this->peca('Aliança Descontinuada', 'alianca-descontinuada', 90.00);
        $fora->update(['is_active' => false]);

        $this->get($this->urlDaLoja())
            ->assertOk()
            ->assertSee('Aliança Clássica 4mm')
            ->assertDontSee('Aliança Descontinuada');

        $this->get(route('vitrine.produto', ['store' => $this->loja, 'product' => $fora->slug]))
            ->assertNotFound();
    }

    public function test_curadoria_do_lojista_limita_o_que_a_vitrine_expoe(): void
    {
        $exposta = $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);
        $escondida = $this->peca('Aliança Fosca 6mm', 'alianca-fosca-6mm', 120.00);

        $this->loja->storeProducts()->create([
            'product_id' => $exposta->getKey(),
            'position' => 1,
            'is_featured' => true,
        ]);

        $this->get($this->urlDaLoja())
            ->assertOk()
            ->assertSee('Aliança Clássica 4mm')
            ->assertDontSee('Aliança Fosca 6mm');

        // O que o lojista não expôs não existe na loja dele — 404, não ficha vazia.
        $this->get(route('vitrine.produto', ['store' => $this->loja, 'product' => $escondida->slug]))
            ->assertNotFound();
    }

    public function test_categorias_visiveis_definem_as_abas_e_o_recorte_da_grade(): void
    {
        $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00, $this->aliancas);
        $this->peca('Solitário 20pts', 'solitario-20pts', 300.00, $this->solitarios);

        $this->loja->storeCategories()->create([
            'category_id' => $this->aliancas->getKey(),
            'position' => 1,
        ]);

        $this->get($this->urlDaLoja())
            ->assertOk()
            ->assertSee('Alianças')
            ->assertSee('Aliança Clássica 4mm')
            ->assertDontSee('Solitário 20pts')
            ->assertDontSee('Solitários');
    }

    public function test_peca_sem_slug_fica_de_fora_por_nao_ter_endereco_na_loja(): void
    {
        $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);
        $semEndereco = $this->peca('Item Sem Slug', 'item-sem-slug', 80.00);
        $semEndereco->update(['slug' => null]);

        $this->get($this->urlDaLoja())
            ->assertOk()
            ->assertSee('Aliança Clássica 4mm')
            ->assertDontSee('Item Sem Slug');
    }

    public function test_peca_sem_categoria_continua_na_grade_enquanto_o_lojista_nao_escolher(): void
    {
        $solta = $this->peca('Aliança Sem Categoria', 'alianca-sem-categoria', 100.00);
        $solta->update(['category_id' => null]);
        $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00, $this->aliancas);

        // Sem escolha gravada, a vitrine mostra o catálogo ativo inteiro — as
        // abas derivadas das categorias existentes não podem recortar a grade.
        $this->get($this->urlDaLoja())
            ->assertOk()
            ->assertSee('Aliança Sem Categoria')
            ->assertSee('Aliança Clássica 4mm');

        // Escolhida uma categoria, a peça sem categoria sai — é o recorte que o
        // lojista pediu.
        $this->loja->storeCategories()->create([
            'category_id' => $this->aliancas->getKey(),
            'position' => 1,
        ]);

        $this->get($this->urlDaLoja())
            ->assertOk()
            ->assertDontSee('Aliança Sem Categoria')
            ->assertSee('Aliança Clássica 4mm');
    }

    public function test_aba_de_categoria_filtra_a_grade(): void
    {
        $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00, $this->aliancas);
        $this->peca('Solitário 20pts', 'solitario-20pts', 300.00, $this->solitarios);

        $this->get($this->urlDaLoja(['categoria' => 'solitarios']))
            ->assertOk()
            ->assertSee('Solitário 20pts')
            ->assertDontSee('Aliança Clássica 4mm');
    }

    public function test_categoria_desconhecida_abre_a_grade_completa_em_vez_de_erro(): void
    {
        $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);

        $this->get($this->urlDaLoja(['categoria' => 'colecao-que-nao-existe']))
            ->assertOk()
            ->assertSee('Aliança Clássica 4mm');
    }

    // ───────────────────── ficha da peça ─────────────────────

    public function test_ficha_traz_galeria_ficha_tecnica_e_disponibilidade_por_aro(): void
    {
        $peca = $this->peca('Aliança Diamantada 6mm', 'alianca-diamantada-6mm', 132.50);

        // Um aro sem saldo: continua na grade, riscado.
        $semSaldo = ProductVariant::factory()->forProduct($peca)->withRingSize(22)->create();
        StockItem::factory()->forVariant($semSaldo)->outOfStock()->create();

        $this->get(route('vitrine.produto', ['store' => $this->loja, 'product' => $peca->slug]))
            ->assertOk()
            ->assertSee('Aliança Diamantada 6mm')
            ->assertSee('Ficha técnica')
            ->assertSee('Anatômica')
            ->assertSee('4mm')
            ->assertSee('Tamanho do aro')
            // O aro com saldo é um link que soma a peça daquele tamanho ao
            // carrinho da loja; o aro sem saldo fica riscado e sem endereço.
            ->assertSee('href="'.e(route('vitrine.carrinho', [
                $this->loja,
                'acao' => 'adicionar',
                'peca' => $peca->slug,
                'aro' => '18',
            ])).'"', false)
            ->assertSee('<span class="schip is-off">22</span>', false);
    }

    public function test_vitrine_nunca_escreve_no_estoque(): void
    {
        $peca = $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);
        $saldo = StockItem::query()->firstOrFail();
        $antes = $saldo->only(['on_hand', 'reserved', 'available']);

        $this->get($this->urlDaLoja())->assertOk();
        $this->get(route('vitrine.produto', ['store' => $this->loja, 'product' => $peca->slug]))->assertOk();

        $this->assertSame($antes, $saldo->refresh()->only(['on_hand', 'reserved', 'available']));
    }

    public function test_gravacao_aparece_com_o_limite_e_o_preco_parametrizados(): void
    {
        $peca = $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);

        $this->get(route('vitrine.produto', ['store' => $this->loja, 'product' => $peca->slug]))
            ->assertOk()
            ->assertSee('Gravação adicional')
            ->assertSee('Até 20 caracteres.');
    }

    public function test_peca_sem_gravacao_nao_mostra_o_bloco(): void
    {
        $peca = $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);
        $peca->update(['allows_engraving' => false]);

        $this->get(route('vitrine.produto', ['store' => $this->loja, 'product' => $peca->slug]))
            ->assertOk()
            ->assertDontSee('Gravação adicional');
    }

    // ───────────────────── favoritos ─────────────────────

    public function test_coracao_fica_marcado_quando_o_visitante_ja_favoritou_nesta_loja(): void
    {
        $peca = $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);
        $token = str_repeat('a1b2c3d4', 4);

        Favorite::factory()->create([
            'product_id' => $peca->getKey(),
            'reseller_store_id' => $this->loja->getKey(),
            'visitor_token' => $token,
        ]);

        // Sem o token do navegador o card não sabe de favorito nenhum — é o
        // visitante novo, que é o caso comum de quem abre a loja.
        $this->get($this->urlDaLoja())
            ->assertOk()
            ->assertDontSee('Peça favoritada');

        $this->withCookie(VitrineCatalogoService::COOKIE_VISITANTE, $token)
            ->get($this->urlDaLoja())
            ->assertOk()
            ->assertSee('Peça favoritada');
    }

    public function test_favorito_de_outra_loja_nao_marca_o_card_desta(): void
    {
        $peca = $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);
        $token = str_repeat('a1b2c3d4', 4);
        $vizinha = ResellerStore::factory()->published()->create();

        Favorite::factory()->create([
            'product_id' => $peca->getKey(),
            'reseller_store_id' => $vizinha->getKey(),
            'visitor_token' => $token,
        ]);

        $this->withCookie(VitrineCatalogoService::COOKIE_VISITANTE, $token)
            ->get($this->urlDaLoja())
            ->assertOk()
            ->assertDontSee('Peça favoritada');
    }

    // ───────────────────── contato da loja ─────────────────────

    public function test_contato_exibido_e_o_da_loja_do_revendedor(): void
    {
        $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);

        $this->get($this->urlDaLoja())
            ->assertOk()
            ->assertSee('Rua das Alianças, 123 - Centro, São Paulo - SP')
            ->assertSee('contato@tomazellialiancas.com.br')
            ->assertSee('https://wa.me/5511988882020', false);
    }
}
