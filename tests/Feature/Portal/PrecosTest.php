<?php

/*
[Modulo: tests/Feature/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Cobre a tela 2.7: custo Velaro visivel, cascata de regras de preco, margens e isolamento entre lojistas.
*/

namespace Tests\Feature\Portal;

use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\Reseller;
use App\Models\ResellerPriceRule;
use App\Models\ResellerPriceSetting;
use App\Models\User;
use App\Services\Portal\ResellerPriceResolver;
use App\Services\Portal\ResellerPricingService;
use App\Support\ResellerScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrecosTest extends TestCase
{
    use RefreshDatabase;

    private Reseller $tomazelli;

    private Reseller $vizinho;

    private User $lojista;

    private ProductCollection $classica;

    private Product $alianca;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tomazelli = Reseller::factory()->approved()->create();
        $this->vizinho = Reseller::factory()->approved()->create();
        $this->lojista = User::factory()->forReseller($this->tomazelli)->create();

        $this->classica = ProductCollection::factory()->create(['name' => 'Clássica', 'slug' => 'classica']);
        $this->alianca = Product::factory()->create([
            'name' => 'Aliança Clássica 4mm',
            'sku' => 'ALC18-4MM',
            'price' => 100.00,
            'is_active' => true,
            'collection_id' => $this->classica->getKey(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $troca
     * @return array<string, mixed>
     */
    private function formulario(array $troca = []): array
    {
        return array_merge([
            'pricing_model' => ResellerPriceSetting::PRICING_MODEL_MULTIPLIER,
            'multiplier' => '3.60',
            'margin_global' => '50.00',
            'margin_min' => '40.00',
            'margin_ideal' => '50.00',
            'margin_max' => '60.00',
            'rounding' => ResellerPriceSetting::ROUNDING_NONE,
            'rule_scope' => ResellerPriceSetting::RULE_SCOPE_GLOBAL,
            'apply_to_all' => '1',
            'allow_manual_override' => '1',
            'allow_promotional_prices' => '1',
        ], $troca);
    }

    public function test_a_tela_mostra_o_custo_velaro_do_produto(): void
    {
        // `products.price` é o custo B2B e no portal ELE APARECE: é a tela em que
        // o lojista descobre quanto paga.
        $resposta = $this->actingAs($this->lojista)->get(route('portal.precos.edit'));

        $resposta->assertOk();
        $resposta->assertSee('Preços e margens');
        $resposta->assertSee('Custo Velaro');
        $resposta->assertSee('R$ 100,00');
        $resposta->assertSee('Ref. ALC18-4MM');
    }

    public function test_sem_configuracao_salva_a_tela_abre_nos_padroes_e_nao_cria_linha(): void
    {
        $this->actingAs($this->lojista)->get(route('portal.precos.edit'))->assertOk();

        $this->assertDatabaseCount('reseller_price_settings', 0);
        $this->assertDatabaseCount('reseller_price_rules', 0);
    }

    public function test_salvar_grava_a_configuracao_e_sincroniza_a_regra_global(): void
    {
        $resposta = $this->actingAs($this->lojista)->put(route('portal.precos.update'), $this->formulario([
            'multiplier' => '4.00',
            'action' => 'salvar',
        ]));

        $resposta->assertRedirect(route('portal.precos.edit'));

        $configuracao = $this->tomazelli->priceSetting()->first();
        $this->assertInstanceOf(ResellerPriceSetting::class, $configuracao);
        $this->assertSame('4.00', (string) $configuracao->multiplier);

        // A regra global é a forma consultável do modelo de precificação: o
        // resolvedor lê regras, e deixá-la para trás faria a vitrine praticar um
        // preço diferente do que a tela mostra.
        $regra = $this->tomazelli->priceRules()->where('scope', ResellerPriceRule::SCOPE_GLOBAL)->first();
        $this->assertInstanceOf(ResellerPriceRule::class, $regra);
        $this->assertSame(ResellerPriceRule::MODE_MULTIPLIER, $regra->mode);
        $this->assertSame('4.0000', (string) $regra->value);
    }

    public function test_recalcular_carimba_a_data_do_kpi(): void
    {
        $this->actingAs($this->lojista)->put(route('portal.precos.update'), $this->formulario([
            'action' => 'recalcular',
        ]));

        $this->assertNotNull($this->tomazelli->priceSetting()->first()?->recalculated_at);
    }

    public function test_aplicar_para_todos_liga_o_toggle_do_catalogo(): void
    {
        $semAplicar = $this->formulario(['action' => 'aplicar-a-todos']);
        unset($semAplicar['apply_to_all']);

        $this->actingAs($this->lojista)->put(route('portal.precos.update'), $semAplicar);

        $configuracao = $this->tomazelli->priceSetting()->first();

        $this->assertTrue($configuracao?->apply_to_all);
        $this->assertNotNull($configuracao?->recalculated_at);
    }

    public function test_faixas_de_margem_fora_de_ordem_sao_recusadas(): void
    {
        // Mínima acima da ideal deixaria a coluna Status sem sentido: o produto
        // bateria a ideal e ainda assim cairia em "margem baixa".
        $resposta = $this->actingAs($this->lojista)->put(route('portal.precos.update'), $this->formulario([
            'margin_min' => '70.00',
            'margin_ideal' => '50.00',
            'margin_max' => '40.00',
        ]));

        $resposta->assertSessionHasErrors(['margin_min', 'margin_max']);
        $this->assertDatabaseCount('reseller_price_settings', 0);
    }

    public function test_arredondamento_e_modelo_fora_da_lista_sao_recusados(): void
    {
        $resposta = $this->actingAs($this->lojista)->put(route('portal.precos.update'), $this->formulario([
            'rounding' => 'para-baixo',
            'pricing_model' => 'chute',
            'rule_scope' => 'inventado',
        ]));

        $resposta->assertSessionHasErrors(['rounding', 'pricing_model', 'rule_scope']);
    }

    public function test_margem_e_markup_sao_leituras_diferentes_da_mesma_venda(): void
    {
        // Custo 100 e preço 200: margem 50% (sobre a venda), markup 100% (sobre o
        // custo). Confundir as duas é o erro clássico de precificação, e a tela
        // mostra as colunas lado a lado justamente por isso.
        $resolvedor = $this->resolvedorCom(['multiplier' => 2.0, 'rounding' => ResellerPriceSetting::ROUNDING_NONE]);
        $preco = $resolvedor->resolve($this->alianca);

        $this->assertSame(200.00, $preco['price']);
        $this->assertSame(50.00, $preco['margin']);
        $this->assertSame(100.00, $preco['markup']);
        $this->assertSame(ResellerPriceResolver::STATUS_IDEAL, $preco['status']);
    }

    public function test_a_regra_mais_especifica_vence_a_mais_geral(): void
    {
        $configuracao = $this->configuracao(['multiplier' => 2.0, 'rounding' => ResellerPriceSetting::ROUNDING_NONE]);

        // Sem exceção nenhuma o preço sai da configuração.
        $this->assertSame(
            ResellerPriceResolver::ORIGIN_SETTING,
            (new ResellerPriceResolver($configuracao))->resolve($this->alianca)['origin'],
        );

        $global = ResellerPriceRule::factory()->create([
            'reseller_id' => $this->tomazelli->getKey(),
            'scope' => ResellerPriceRule::SCOPE_GLOBAL,
            'collection_id' => null,
            'product_id' => null,
            'mode' => ResellerPriceRule::MODE_MULTIPLIER,
            'value' => 3.0,
            'rounding' => null,
            'is_active' => true,
        ]);

        $porColecao = ResellerPriceRule::factory()->create([
            'reseller_id' => $this->tomazelli->getKey(),
            'scope' => ResellerPriceRule::SCOPE_COLLECTION,
            'collection_id' => $this->classica->getKey(),
            'product_id' => null,
            'mode' => ResellerPriceRule::MODE_MULTIPLIER,
            'value' => 4.0,
            'rounding' => null,
            'is_active' => true,
        ]);

        $porProduto = ResellerPriceRule::factory()->create([
            'reseller_id' => $this->tomazelli->getKey(),
            'scope' => ResellerPriceRule::SCOPE_PRODUCT,
            'collection_id' => null,
            'product_id' => $this->alianca->getKey(),
            'mode' => ResellerPriceRule::MODE_MANUAL,
            'value' => 777.00,
            'rounding' => null,
            'is_active' => true,
        ]);

        // Produto ganha de coleção, que ganha da global.
        $comTudo = new ResellerPriceResolver($configuracao, [$global, $porColecao, $porProduto]);
        $this->assertSame(777.00, $comTudo->resolve($this->alianca)['price']);
        $this->assertSame(ResellerPriceResolver::ORIGIN_PRODUCT, $comTudo->resolve($this->alianca)['origin']);

        $semProduto = new ResellerPriceResolver($configuracao, [$global, $porColecao]);
        $this->assertSame(400.00, $semProduto->resolve($this->alianca)['price']);
        $this->assertSame(ResellerPriceResolver::ORIGIN_COLLECTION, $semProduto->resolve($this->alianca)['origin']);

        $soGlobal = new ResellerPriceResolver($configuracao, [$global]);
        $this->assertSame(300.00, $soGlobal->resolve($this->alianca)['price']);
        $this->assertSame(ResellerPriceResolver::ORIGIN_GLOBAL, $soGlobal->resolve($this->alianca)['origin']);
    }

    public function test_regra_inativa_nao_entra_na_cascata(): void
    {
        $configuracao = $this->configuracao(['multiplier' => 2.0, 'rounding' => ResellerPriceSetting::ROUNDING_NONE]);

        $desligada = ResellerPriceRule::factory()->make([
            'reseller_id' => $this->tomazelli->getKey(),
            'scope' => ResellerPriceRule::SCOPE_GLOBAL,
            'mode' => ResellerPriceRule::MODE_MULTIPLIER,
            'value' => 9.0,
            'is_active' => false,
        ]);

        $resolvedor = new ResellerPriceResolver($configuracao, [$desligada]);

        $this->assertSame(200.00, $resolvedor->resolve($this->alianca)['price']);
    }

    public function test_a_maior_prioridade_desempata_dentro_do_mesmo_escopo(): void
    {
        $configuracao = $this->configuracao(['multiplier' => 2.0, 'rounding' => ResellerPriceSetting::ROUNDING_NONE]);

        $fraca = ResellerPriceRule::factory()->create([
            'reseller_id' => $this->tomazelli->getKey(),
            'scope' => ResellerPriceRule::SCOPE_GLOBAL,
            'collection_id' => null, 'product_id' => null,
            'mode' => ResellerPriceRule::MODE_MULTIPLIER, 'value' => 3.0,
            'rounding' => null, 'priority' => 1, 'is_active' => true,
        ]);

        $forte = ResellerPriceRule::factory()->create([
            'reseller_id' => $this->tomazelli->getKey(),
            'scope' => ResellerPriceRule::SCOPE_GLOBAL,
            'collection_id' => null, 'product_id' => null,
            'mode' => ResellerPriceRule::MODE_MULTIPLIER, 'value' => 5.0,
            'rounding' => null, 'priority' => 9, 'is_active' => true,
        ]);

        $resolvedor = new ResellerPriceResolver($configuracao, [$fraca, $forte]);

        $this->assertSame(500.00, $resolvedor->resolve($this->alianca)['price']);
    }

    public function test_o_arredondamento_nunca_puxa_o_preco_para_baixo(): void
    {
        // Arredondar para baixo comeria a margem que o lojista acabou de definir,
        // então a política 0,99 sempre sobe para o próximo X,99 — inclusive a
        // partir de um valor exato.
        $exato = $this->resolvedorCom([
            'multiplier' => 1.50,
            'rounding' => ResellerPriceSetting::ROUNDING_UP_099,
        ]);

        // 100 × 1,50 = 150,00 → 150,99.
        $this->assertSame(150.99, $exato->resolve($this->alianca)['price']);

        $quebrado = $this->resolvedorCom([
            'pricing_model' => ResellerPriceSetting::PRICING_MODEL_PERCENT,
            'margin_global' => 35.0,
            'rounding' => ResellerPriceSetting::ROUNDING_UP_099,
        ]);

        // Margem de 35% = 100 / 0,65 = 153,85 → 153,99.
        $this->assertSame(153.99, $quebrado->resolve($this->alianca)['price']);
    }

    public function test_o_modelo_percentual_usa_a_margem_global(): void
    {
        $resolvedor = $this->resolvedorCom([
            'pricing_model' => ResellerPriceSetting::PRICING_MODEL_PERCENT,
            'margin_global' => 50.0,
            'rounding' => ResellerPriceSetting::ROUNDING_NONE,
        ]);

        // Margem de 50% sobre a venda = custo / (1 - 0,50) = 200.
        $this->assertSame(200.00, $resolvedor->resolve($this->alianca)['price']);
    }

    public function test_a_faixa_de_margem_segue_as_metas_do_proprio_lojista(): void
    {
        $resolvedor = $this->resolvedorCom([
            'multiplier' => 1.10,
            'margin_min' => 40.0,
            'rounding' => ResellerPriceSetting::ROUNDING_NONE,
        ]);

        // 100 → 110: margem 9,09% — abaixo dos 20% da faixa crítica.
        $this->assertSame(ResellerPriceResolver::STATUS_CRITICAL, $resolvedor->resolve($this->alianca)['status']);

        $intermediario = $this->resolvedorCom([
            'multiplier' => 1.50,
            'margin_min' => 40.0,
            'rounding' => ResellerPriceSetting::ROUNDING_NONE,
        ]);

        // 100 → 150: margem 33,33% — acima da crítica, abaixo da mínima do lojista.
        $this->assertSame(ResellerPriceResolver::STATUS_LOW, $intermediario->resolve($this->alianca)['status']);
    }

    public function test_a_aba_de_regras_mostra_so_as_excecoes_do_proprio_lojista(): void
    {
        $outraColecao = ProductCollection::factory()->create(['name' => 'Diamantada', 'slug' => 'diamantada']);

        ResellerPriceRule::factory()->create([
            'reseller_id' => $this->tomazelli->getKey(),
            'scope' => ResellerPriceRule::SCOPE_COLLECTION,
            'collection_id' => $this->classica->getKey(),
            'product_id' => null,
            'mode' => ResellerPriceRule::MODE_MULTIPLIER,
            'value' => 3.9,
            'is_active' => true,
        ]);

        // A regra do vizinho existe na mesma tabela — e é exatamente o segredo
        // comercial que não pode atravessar: nem o fator que ele pratica, nem a
        // peça em que ele o pratica.
        $pecaDoVizinho = Product::factory()->create([
            'name' => 'Aliança Exclusiva do Vizinho',
            'price' => 300.00,
            'is_active' => true,
            'collection_id' => $outraColecao->getKey(),
        ]);

        ResellerPriceRule::factory()->create([
            'reseller_id' => $this->vizinho->getKey(),
            'scope' => ResellerPriceRule::SCOPE_PRODUCT,
            'collection_id' => null,
            'product_id' => $pecaDoVizinho->getKey(),
            'mode' => ResellerPriceRule::MODE_MULTIPLIER,
            'value' => 7.77,
            'is_active' => true,
        ]);

        $resposta = $this->actingAs($this->lojista)
            ->get(route('portal.precos.edit', ['aba' => ResellerPricingService::TAB_RULES]));

        $resposta->assertOk();
        $resposta->assertSee('Clássica');
        $resposta->assertSee('3,9x');
        $resposta->assertDontSee('7,77x');
        $resposta->assertDontSee('Aliança Exclusiva do Vizinho');
    }

    public function test_a_regra_do_vizinho_nao_muda_o_preco_deste_lojista(): void
    {
        ResellerPriceRule::factory()->create([
            'reseller_id' => $this->vizinho->getKey(),
            'scope' => ResellerPriceRule::SCOPE_PRODUCT,
            'collection_id' => null,
            'product_id' => $this->alianca->getKey(),
            'mode' => ResellerPriceRule::MODE_MANUAL,
            'value' => 999.00,
            'is_active' => true,
        ]);

        $this->actingAs($this->lojista)->put(route('portal.precos.update'), $this->formulario([
            'multiplier' => '2.00',
            'rounding' => ResellerPriceSetting::ROUNDING_NONE,
        ]));

        $resposta = $this->actingAs($this->lojista)->get(route('portal.precos.edit'));

        $resposta->assertOk();
        $resposta->assertSee('R$ 200,00');
        $resposta->assertDontSee('R$ 999,00');
    }

    public function test_o_filtro_por_colecao_estreita_a_tabela(): void
    {
        Product::factory()->create([
            'name' => 'Aliança Diamantada 6mm',
            'price' => 120.00,
            'is_active' => true,
            'collection_id' => ProductCollection::factory()->create(['name' => 'Diamantada', 'slug' => 'diamantada'])->getKey(),
        ]);

        $resposta = $this->actingAs($this->lojista)->get(route('portal.precos.edit', ['colecao' => 'classica']));

        $resposta->assertOk();
        $resposta->assertSee('Aliança Clássica 4mm');
        $resposta->assertDontSee('Aliança Diamantada 6mm');
    }

    public function test_filtro_torto_nao_derruba_a_tela(): void
    {
        // A tela é de leitura: link velho no favorito precisa abrir a tabela, não
        // um 422 que joga o lojista para fora da página.
        $resposta = $this->actingAs($this->lojista)->get(route('portal.precos.edit', [
            'aba' => 'inexistente',
            'por_pagina' => '999999',
            'page' => 'abc',
        ]));

        $resposta->assertOk();
        $resposta->assertSee('Aliança Clássica 4mm');
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    private function configuracao(array $atributos): ResellerPriceSetting
    {
        return ResellerPriceSetting::factory()->make(array_merge([
            'reseller_id' => $this->tomazelli->getKey(),
            'pricing_model' => ResellerPriceSetting::PRICING_MODEL_MULTIPLIER,
            'margin_min' => 40.0,
        ], $atributos));
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    private function resolvedorCom(array $atributos): ResellerPriceResolver
    {
        return new ResellerPriceResolver($this->configuracao($atributos));
    }

    public function test_o_escopo_do_container_entrega_o_revendedor_autenticado(): void
    {
        $this->actingAs($this->lojista)->get(route('portal.precos.edit'))->assertOk();

        $this->assertSame(
            $this->tomazelli->getKey(),
            $this->app->make(ResellerScope::class)->reseller->getKey(),
        );
    }
}
