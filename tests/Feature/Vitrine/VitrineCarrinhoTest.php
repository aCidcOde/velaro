<?php

/*
[Modulo: tests/Feature/Vitrine]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Cobre o carrinho de balcao da tela 2.10: linhas em sessao, gravacao a parte, identidade dos totais, registro do pedido e isolamento do comprovante.
*/

namespace Tests\Feature\Vitrine;

use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerConsent;
use App\Models\Finish;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemEngraving;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Reseller;
use App\Models\ResellerPriceSetting;
use App\Models\ResellerStore;
use App\Models\Setting;
use App\Models\StockItem;
use App\Services\Vitrine\VitrineCarrinhoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VitrineCarrinhoTest extends TestCase
{
    use RefreshDatabase;

    /** CPF válido pelos dois dígitos verificadores — o `Cpf` rule confere. */
    private const CPF = '529.982.247-25';

    private Reseller $tomazelli;

    private ResellerStore $loja;

    private Category $aliancas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tomazelli = Reseller::factory()->approved()->create(['trade_name' => 'Tomazelli Alianças']);

        $this->loja = ResellerStore::factory()->published()->create([
            'reseller_id' => $this->tomazelli->getKey(),
            'name' => 'Tomazelli Alianças',
            'slug' => 'tomazelli-aliancas',
            'address' => 'Rua das Alianças, 123 - Centro',
            'whatsapp' => '(11) 98888-2020',
        ]);

        // Multiplicador 2x sem arredondamento: o preço da vitrine é o dobro do
        // custo, e a distância entre os dois fica visível a olho nu no assert.
        ResellerPriceSetting::factory()->create([
            'reseller_id' => $this->tomazelli->getKey(),
            'pricing_model' => ResellerPriceSetting::PRICING_MODEL_MULTIPLIER,
            'multiplier' => 2.00,
            'rounding' => ResellerPriceSetting::ROUNDING_NONE,
        ]);

        $this->aliancas = Category::factory()->named('Alianças')->create(['position' => 1]);

        // A gravação é parametrizável (regra 3 da tela 2.10): limite e preço
        // saem daqui, não de constante no código.
        $this->parametrizarGravacao(20, '30.00');
    }

    private function parametrizarGravacao(int $maxChars, string $preco): void
    {
        Setting::query()->updateOrCreate(
            ['group' => 'gravacao', 'key' => 'gravacao.max_chars'],
            ['value' => (string) $maxChars, 'type' => 'integer', 'is_public' => true],
        );

        Setting::query()->updateOrCreate(
            ['group' => 'gravacao', 'key' => 'gravacao.preco'],
            ['value' => $preco, 'type' => 'decimal', 'is_public' => true],
        );
    }

    /**
     * Peça do catálogo com aro 18 em estoque.
     */
    private function peca(string $nome, string $slug, float $custo, bool $gravavel = true): Product
    {
        $produto = Product::factory()->create([
            'name' => $nome,
            'slug' => $slug,
            'price' => $custo,
            'is_active' => true,
            'category_id' => $this->aliancas->getKey(),
            'material_id' => Material::factory()->named('Ouro 18k '.$slug)->create()->getKey(),
            'finish_id' => Finish::factory()->named('Polido '.$slug)->create()->getKey(),
            'width_mm' => 4,
            'shape' => 'Anatômica',
            'allows_engraving' => $gravavel,
            'engraving_max_chars' => $gravavel ? 20 : 0,
            'delivery_days' => 7,
        ]);

        $aro = ProductVariant::factory()->forProduct($produto)->withRingSize(18)->create();
        StockItem::factory()->forVariant($aro)->create(['on_hand' => 5, 'reserved' => 0, 'available' => 5]);

        return $produto;
    }

    private function urlCarrinho(array $parametros = []): string
    {
        return route('vitrine.carrinho', [$this->loja, ...$parametros]);
    }

    /**
     * Soma a peça ao carrinho pelo caminho real — o link da ficha.
     */
    private function adicionar(Product $peca, string $aro = '18'): void
    {
        $this->get($this->urlCarrinho([
            'acao' => VitrineCarrinhoService::ACAO_ADICIONAR,
            'peca' => $peca->slug,
            'aro' => $aro,
        ]))->assertRedirect($this->urlCarrinho());
    }

    /**
     * @return array<string, string>
     */
    private function formulario(array $extras = []): array
    {
        return [
            'name' => 'Maria Silva',
            'whatsapp' => '(11) 98765-4321',
            'document' => self::CPF,
            ...$extras,
        ];
    }

    // ───────────────────── a tela ─────────────────────

    public function test_carrinho_abre_sem_login_com_a_grade_e_o_painel_do_pedido(): void
    {
        $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);

        $this->get($this->urlCarrinho())
            ->assertOk()
            ->assertSee('Carrinho de compras')
            ->assertSee('O carrinho está vazio.')
            // A grade continua na tela: o atendimento é presencial e o vendedor
            // segue escolhendo peças com o cliente.
            ->assertSee('Aliança Clássica 4mm')
            ->assertSee('Subtotal')
            ->assertSee('Adicional de gravação')
            ->assertSee('Descontos');

        $this->assertGuest();
    }

    public function test_carrinho_de_loja_nao_publicada_devolve_404(): void
    {
        $this->loja->update(['is_active' => false]);

        $this->get($this->urlCarrinho())->assertNotFound();
    }

    /**
     * "Painel CARRINHO DE COMPRAS (com X para fechar)" — seção 5 da tela 2.10.
     * Fechar é voltar à vitrine: o carrinho mora na sessão e nada se perde.
     */
    public function test_painel_do_carrinho_tem_o_x_para_fechar(): void
    {
        $this->adicionar($this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00));

        $this->get($this->urlCarrinho())
            ->assertOk()
            ->assertSee('Fechar o carrinho e voltar à loja')
            ->assertSee(route('vitrine.index', $this->loja), false);
    }

    /**
     * `show_prices` governa o **catálogo**, não o fecho da venda.
     *
     * Com o toggle desligado a grade e a ficha dizem "Consulte na loja" (coberto
     * em VitrineLojaTest), mas o painel do carrinho e o comprovante continuam
     * mostrando as quatro linhas de "Totais" e o TOTAL: a seção 5 da tela 2.10
     * as lista sem condicionante, e um pedido sem total manda o cliente ao caixa
     * pagar um número que ninguém mostrou.
     *
     * O que segue proibido nas duas telas é o **custo B2B**.
     */
    public function test_show_prices_desligado_nao_esconde_o_total_do_carrinho_nem_do_comprovante(): void
    {
        $this->loja->update(['show_prices' => false]);
        $this->adicionar($this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00));

        $painel = $this->get($this->urlCarrinho())
            ->assertOk()
            ->assertSee('R$ 200,00')
            ->assertDontSee('R$ 100,00');

        $this->semMarcaDoFornecedor($painel->getContent());

        $resposta = $this->post(route('vitrine.finalizar', $this->loja), $this->formulario())
            ->assertRedirect();

        $this->get((string) $resposta->headers->get('Location'))
            ->assertOk()
            ->assertSee('R$ 200,00')
            ->assertDontSee('R$ 100,00');
    }

    public function test_aviso_de_pagamento_no_caixa_e_obrigatorio_na_tela(): void
    {
        $this->adicionar($this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00));

        $this->get($this->urlCarrinho())
            ->assertOk()
            ->assertSee('Pagamento realizado no caixa da loja')
            ->assertSee('O pagamento é realizado no caixa da loja.')
            ->assertSee('Retirada exclusiva na loja.');
    }

    // ───────────────────── linhas do carrinho ─────────────────────

    public function test_adicionar_pela_ficha_soma_a_peca_e_redireciona_para_a_url_limpa(): void
    {
        $peca = $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);

        // O redirect é o que impede um F5 de repetir a ação (PRG).
        $this->get($this->urlCarrinho([
            'acao' => VitrineCarrinhoService::ACAO_ADICIONAR,
            'peca' => $peca->slug,
            'aro' => '18',
        ]))->assertRedirect($this->urlCarrinho());

        $this->get($this->urlCarrinho())
            ->assertOk()
            ->assertSee('Aliança Clássica 4mm')
            ->assertSee('Aro 18')
            ->assertSee('1 item');
    }

    public function test_stepper_muda_a_quantidade_e_zero_tira_a_linha(): void
    {
        $peca = $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);
        $this->adicionar($peca);

        $this->get($this->urlCarrinho([
            'acao' => VitrineCarrinhoService::ACAO_QUANTIDADE,
            'peca' => $peca->slug,
            'aro' => '18',
            'quantidade' => 3,
        ]))->assertRedirect($this->urlCarrinho());

        $this->get($this->urlCarrinho())
            ->assertOk()
            ->assertSee('3 itens')
            // 3 × (100 de custo × 2) = 600.
            ->assertSee('R$ 600,00');

        $this->get($this->urlCarrinho([
            'acao' => VitrineCarrinhoService::ACAO_QUANTIDADE,
            'peca' => $peca->slug,
            'aro' => '18',
            'quantidade' => 0,
        ]))->assertRedirect($this->urlCarrinho());

        $this->get($this->urlCarrinho())->assertOk()->assertSee('O carrinho está vazio.');
    }

    public function test_lixeira_remove_a_linha(): void
    {
        $peca = $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);
        $this->adicionar($peca);

        $this->get($this->urlCarrinho([
            'acao' => VitrineCarrinhoService::ACAO_REMOVER,
            'peca' => $peca->slug,
            'aro' => '18',
        ]))->assertRedirect($this->urlCarrinho());

        $this->get($this->urlCarrinho())->assertOk()->assertSee('O carrinho está vazio.');
    }

    public function test_peca_fora_do_catalogo_da_loja_nao_entra_no_carrinho(): void
    {
        $peca = $this->peca('Aliança Descontinuada', 'alianca-descontinuada', 100.00);
        $peca->update(['is_active' => false]);

        $this->get($this->urlCarrinho([
            'acao' => VitrineCarrinhoService::ACAO_ADICIONAR,
            'peca' => $peca->slug,
            'aro' => '18',
        ]))->assertRedirect($this->urlCarrinho());

        $this->get($this->urlCarrinho())
            ->assertOk()
            ->assertSee('O carrinho está vazio.')
            ->assertDontSee('Aliança Descontinuada');
    }

    public function test_peca_que_sai_do_catalogo_some_do_painel_e_da_sacola_do_topo(): void
    {
        $peca = $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);
        $this->adicionar($peca);

        $this->get($this->urlCarrinho())->assertOk()->assertSee('1 item')->assertSee('Sacola');

        // O lojista desativa a peça com o carrinho aberto no tablet.
        $peca->update(['is_active' => false]);

        $this->get($this->urlCarrinho())
            ->assertOk()
            // O contador do topo conta o mesmo que o painel: nada.
            ->assertSee('<b>0</b>', false)
            ->assertSee('O carrinho está vazio.')
            ->assertDontSee('Aliança Clássica 4mm');
    }

    public function test_acao_deixa_um_aviso_de_uma_linha_no_painel(): void
    {
        $peca = $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);

        $this->get($this->urlCarrinho([
            'acao' => VitrineCarrinhoService::ACAO_ADICIONAR,
            'peca' => $peca->slug,
            'aro' => '18',
        ]))->assertRedirect($this->urlCarrinho());

        $this->followingRedirects()
            ->get($this->urlCarrinho([
                'acao' => VitrineCarrinhoService::ACAO_REMOVER,
                'peca' => $peca->slug,
                'aro' => '18',
            ]))
            ->assertOk()
            ->assertSee('Aliança Clássica 4mm foi retirada do carrinho.');
    }

    public function test_acao_desconhecida_nao_derruba_a_pagina_publica(): void
    {
        $this->get($this->urlCarrinho(['acao' => 'apagar-tudo', 'peca' => 'nao-existe']))
            ->assertOk()
            ->assertSee('Carrinho de compras');
    }

    public function test_carrinho_e_por_loja_e_uma_nao_ve_a_outra(): void
    {
        $outraLoja = ResellerStore::factory()->published()->create([
            'reseller_id' => Reseller::factory()->approved()->create()->getKey(),
            'name' => 'Joalheria Aurora',
            'slug' => 'joalheria-aurora',
        ]);

        ResellerPriceSetting::factory()->create([
            'reseller_id' => $outraLoja->reseller_id,
            'pricing_model' => ResellerPriceSetting::PRICING_MODEL_MULTIPLIER,
            'multiplier' => 2.00,
            'rounding' => ResellerPriceSetting::ROUNDING_NONE,
        ]);

        $peca = $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);
        $this->adicionar($peca);

        $this->get(route('vitrine.carrinho', $outraLoja))
            ->assertOk()
            ->assertSee('O carrinho está vazio.');

        $this->get($this->urlCarrinho())->assertOk()->assertSee('1 item');
    }

    // ───────────────────── gravação ─────────────────────

    public function test_gravacao_e_discriminada_a_parte_com_limite_e_preco_das_configuracoes(): void
    {
        $this->parametrizarGravacao(18, '42.50');
        $this->adicionar($this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00));

        $this->get($this->urlCarrinho())
            ->assertOk()
            ->assertSee('Gravação adicional')
            ->assertSee('Deseja gravação adicional?')
            ->assertSee('Sim, desejo gravação')
            ->assertSee('Não, obrigado')
            // Limite da peça (20) contra o global (18): vale o menor.
            ->assertSee('Limite: 18 caracteres')
            ->assertSee('R$ 42,50');
    }

    public function test_ligar_a_gravacao_soma_a_linha_do_adicional_no_total(): void
    {
        $peca = $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);
        $this->adicionar($peca);
        $this->adicionar($peca);

        $this->get($this->urlCarrinho([
            'acao' => VitrineCarrinhoService::ACAO_GRAVACAO,
            'gravacao' => 1,
        ]))->assertRedirect($this->urlCarrinho());

        // Subtotal 2 × 200 = 400; gravação 2 × 30 = 60; total 460.
        $this->get($this->urlCarrinho())
            ->assertOk()
            ->assertSee('R$ 400,00')
            ->assertSee('R$ 60,00')
            ->assertSee('R$ 460,00')
            ->assertSee('Texto / nome');
    }

    public function test_peca_sem_gravacao_nao_mostra_o_bloco_no_carrinho(): void
    {
        $this->adicionar($this->peca('Pingente Liso', 'pingente-liso', 80.00, gravavel: false));

        $this->get($this->urlCarrinho())
            ->assertOk()
            ->assertDontSee('Deseja gravação adicional?');
    }

    // ───────────────────── registro do pedido ─────────────────────

    public function test_finalizar_cria_pedido_em_draft_vinculado_a_loja_e_ao_cliente(): void
    {
        $peca = $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);
        $this->adicionar($peca);

        $this->post(route('vitrine.finalizar', $this->loja), $this->formulario([
            'email' => 'Maria.Silva@Email.com',
            'wedding_date' => '2026-05-12',
        ]))->assertRedirect();

        $pedido = Order::query()->firstOrFail();

        $this->assertSame(Order::STATUS_DRAFT, $pedido->status);
        $this->assertSame(Order::OPERATIONAL_STATUS_REGISTERED, $pedido->operational_status);
        $this->assertSame((int) $this->tomazelli->getKey(), (int) $pedido->reseller_id);
        $this->assertNull($pedido->user_id);

        $cliente = Customer::query()->firstOrFail();

        $this->assertSame((int) $cliente->getKey(), (int) $pedido->customer_id);
        $this->assertSame('Maria Silva', $cliente->name);
        $this->assertSame(self::CPF, $cliente->document);
        $this->assertSame('maria.silva@email.com', $cliente->email);
        // O cliente final é da carteira DESTE lojista — ele não tem conta.
        $this->assertSame((int) $this->tomazelli->getKey(), (int) $cliente->reseller_id);
        $this->assertNull($cliente->user_id);
    }

    public function test_finalizar_leva_ao_comprovante_do_proprio_pedido(): void
    {
        $this->adicionar($this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00));

        $resposta = $this->post(route('vitrine.finalizar', $this->loja), $this->formulario());

        $pedido = Order::query()->firstOrFail();

        $resposta->assertRedirect(route('vitrine.confirmado', [
            'store' => $this->loja,
            'order' => $pedido->public_number,
        ]));
    }

    public function test_item_guarda_o_preco_b2c_e_nunca_o_custo_b2b(): void
    {
        $peca = $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 137.45);
        $this->adicionar($peca);

        $this->post(route('vitrine.finalizar', $this->loja), $this->formulario());

        $item = OrderItem::query()->firstOrFail();

        // Custo 137,45 × multiplicador 2 = 274,90. O custo não vira unit_price.
        $this->assertSame('274.90', (string) $item->unit_price);
        $this->assertSame('274.90', (string) $item->total_price);
        $this->assertNotSame('137.45', (string) $item->unit_price);

        // O aro escolhido entra na linha.
        $aro = ProductVariant::query()->where('product_id', $peca->getKey())->firstOrFail();
        $this->assertSame((int) $aro->getKey(), (int) $item->product_variant_id);
    }

    public function test_total_fecha_a_identidade_subtotal_mais_gravacao_mais_frete_menos_desconto(): void
    {
        $peca = $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);
        $this->adicionar($peca);
        $this->adicionar($peca);

        $this->get($this->urlCarrinho(['acao' => VitrineCarrinhoService::ACAO_GRAVACAO, 'gravacao' => 1]));

        $this->post(route('vitrine.finalizar', $this->loja), $this->formulario([
            'engraving_text' => 'Ana & Pedro',
            'engraving_date' => '2026-05-12',
        ]))->assertRedirect();

        $pedido = Order::query()->firstOrFail();

        $this->assertSame('400.00', (string) $pedido->subtotal_amount);
        $this->assertSame('60.00', (string) $pedido->engraving_amount);
        $this->assertSame('0.00', (string) $pedido->shipping_amount);
        $this->assertSame('0.00', (string) $pedido->discount_amount);
        $this->assertSame('460.00', (string) $pedido->total_amount);

        $this->assertSame(
            round((float) $pedido->subtotal_amount + (float) $pedido->engraving_amount
                + (float) $pedido->shipping_amount - (float) $pedido->discount_amount, 2),
            round((float) $pedido->total_amount, 2),
        );
    }

    public function test_gravacao_e_gravada_por_item_com_texto_data_e_valor(): void
    {
        $peca = $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);
        $this->adicionar($peca);
        $this->adicionar($peca);

        $this->get($this->urlCarrinho(['acao' => VitrineCarrinhoService::ACAO_GRAVACAO, 'gravacao' => 1]));

        $this->post(route('vitrine.finalizar', $this->loja), $this->formulario([
            'engraving_text' => 'Ana & Pedro',
            'engraving_date' => '2026-05-12',
        ]));

        $gravacao = OrderItemEngraving::query()->firstOrFail();

        $this->assertTrue((bool) $gravacao->enabled);
        $this->assertSame('Ana & Pedro', $gravacao->text);
        $this->assertSame(11, (int) $gravacao->chars);
        // Cobrada por aliança: 2 peças na linha × R$ 30,00.
        $this->assertSame('60.00', (string) $gravacao->price);
    }

    public function test_sem_gravacao_a_linha_nasce_desligada_e_o_adicional_e_zero(): void
    {
        $this->adicionar($this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00));

        $this->post(route('vitrine.finalizar', $this->loja), $this->formulario());

        $gravacao = OrderItemEngraving::query()->firstOrFail();

        $this->assertFalse((bool) $gravacao->enabled);
        $this->assertNull($gravacao->text);
        $this->assertSame('0.00', (string) Order::query()->firstOrFail()->engraving_amount);
    }

    public function test_cliente_conhecido_pelo_cpf_reaproveita_a_ficha_da_carteira(): void
    {
        $existente = Customer::factory()->create([
            'reseller_id' => $this->tomazelli->getKey(),
            'name' => 'Maria S.',
            'document' => self::CPF,
        ]);

        $this->adicionar($this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00));
        $this->post(route('vitrine.finalizar', $this->loja), $this->formulario());

        $this->assertSame(1, Customer::query()->where('reseller_id', $this->tomazelli->getKey())->count());
        $this->assertSame('Maria Silva', $existente->fresh()?->name);
    }

    public function test_cpf_conhecido_de_outra_loja_nao_vira_cliente_desta_sem_passar_pelo_balcao(): void
    {
        $outro = Reseller::factory()->approved()->create();
        Customer::factory()->create([
            'reseller_id' => $outro->getKey(),
            'name' => 'Maria de Outra Loja',
            'document' => self::CPF,
        ]);

        $this->adicionar($this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00));
        $this->post(route('vitrine.finalizar', $this->loja), $this->formulario());

        $this->assertSame(1, Customer::query()->where('reseller_id', $this->tomazelli->getKey())->count());
        $this->assertSame(1, Customer::query()->where('reseller_id', $outro->getKey())->count());
        $this->assertSame('Maria de Outra Loja', Customer::query()->where('reseller_id', $outro->getKey())->firstOrFail()->name);
    }

    public function test_aceite_de_marketing_vira_consentimento_com_data(): void
    {
        $this->adicionar($this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00));

        $this->post(route('vitrine.finalizar', $this->loja), $this->formulario(['accept_marketing' => '1']));

        $consentimento = CustomerConsent::query()->firstOrFail();

        $this->assertSame(CustomerConsent::TYPE_MARKETING, $consentimento->type);
        $this->assertTrue((bool) $consentimento->granted);
        $this->assertNotNull($consentimento->granted_at);
    }

    public function test_sem_aceite_nao_ha_consentimento_gravado(): void
    {
        $this->adicionar($this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00));

        $this->post(route('vitrine.finalizar', $this->loja), $this->formulario());

        $this->assertSame(0, CustomerConsent::query()->count());
    }

    public function test_carrinho_e_esvaziado_depois_do_pedido_registrado(): void
    {
        $this->adicionar($this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00));

        $this->post(route('vitrine.finalizar', $this->loja), $this->formulario());

        $this->get($this->urlCarrinho())->assertOk()->assertSee('O carrinho está vazio.');
    }

    // ───────────────────── regra 3: nenhum pagamento ─────────────────────

    public function test_pedido_da_vitrine_nasce_com_pagamento_pendente_no_caixa(): void
    {
        $this->adicionar($this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00));

        $this->post(route('vitrine.finalizar', $this->loja), $this->formulario());

        $pedido = Order::query()->firstOrFail();

        // A vitrine não recebe: quem confirma o pagamento é o caixa da loja.
        $this->assertSame(Order::PAYMENT_STATUS_PENDING, $pedido->payment_status);
        $this->assertSame(0, Payment::query()->where('order_id', $pedido->getKey())->count());
    }

    public function test_carrinho_nao_oferece_meio_de_pagamento_nenhum(): void
    {
        $this->adicionar($this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00));

        $conteudo = $this->get($this->urlCarrinho())->assertOk()->getContent();

        $this->assertIsString($conteudo);

        // O único formulário da tela é o que registra o pedido.
        $this->assertSame(1, substr_count($conteudo, '<form'));
        $this->assertStringContainsString(route('vitrine.finalizar', $this->loja), $conteudo);

        foreach (['gateway', 'stripe', 'mercadopago', 'pagseguro', 'copia e cola', 'qrcode'] as $termo) {
            $this->assertStringNotContainsStringIgnoringCase($termo, $conteudo);
        }

        // Nenhum campo de cartão nem de chave Pix.
        foreach (['card_number', 'cvv', 'pix_key', 'numero_do_cartao'] as $campo) {
            $this->assertStringNotContainsString($campo, $conteudo);
        }
    }

    // ───────────────────── validação ─────────────────────

    public function test_carrinho_vazio_nao_vira_pedido(): void
    {
        $this->post(route('vitrine.finalizar', $this->loja), $this->formulario())
            ->assertSessionHasErrors('carrinho');

        $this->assertSame(0, Order::query()->count());
    }

    public function test_identificacao_do_cliente_e_obrigatoria(): void
    {
        $this->adicionar($this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00));

        $this->post(route('vitrine.finalizar', $this->loja), [])
            ->assertSessionHasErrors(['name', 'whatsapp', 'document']);

        $this->assertSame(0, Order::query()->count());
    }

    public function test_cpf_invalido_reprova(): void
    {
        $this->adicionar($this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00));

        $this->post(route('vitrine.finalizar', $this->loja), $this->formulario(['document' => '111.111.111-11']))
            ->assertSessionHasErrors('document');

        $this->assertSame(0, Order::query()->count());
    }

    public function test_gravacao_pedida_exige_o_texto_e_respeita_o_limite(): void
    {
        $this->adicionar($this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00));
        $this->get($this->urlCarrinho(['acao' => VitrineCarrinhoService::ACAO_GRAVACAO, 'gravacao' => 1]));

        $this->post(route('vitrine.finalizar', $this->loja), $this->formulario())
            ->assertSessionHasErrors('engraving_text');

        $this->post(route('vitrine.finalizar', $this->loja), $this->formulario([
            'engraving_text' => str_repeat('A', 21),
        ]))->assertSessionHasErrors('engraving_text');

        $this->assertSame(0, Order::query()->count());
    }

    public function test_finalizar_em_loja_nao_publicada_devolve_404_e_nao_erro_de_formulario(): void
    {
        $this->loja->update(['is_active' => false]);

        $this->post(route('vitrine.finalizar', $this->loja), [])->assertNotFound();
    }

    /**
     * Regra 1 pela porta dos fundos: o padrão do `FormRequest` é voltar para
     * `url()->previous()`, que **sem `Referer` cai na raiz da aplicação** — o
     * site institucional da Velaro, com logo, "Seja um revendedor Velaro" e o
     * rodapé da fábrica. Um CPF digitado errado no balcão jogava o consumidor
     * final direto na marca do fornecedor.
     *
     * E o cabeçalho falta em situação comum: a loja em domínio próprio postando
     * para a plataforma, ou uma `Referrer-Policy` mais fechada no tablet.
     */
    public function test_formulario_reprovado_volta_ao_carrinho_da_loja_e_nunca_ao_site_da_fabrica(): void
    {
        $this->adicionar($this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00));

        // Sem `from()`: é exatamente o caso em que não há `Referer` na requisição.
        $resposta = $this->post(route('vitrine.finalizar', $this->loja), [])
            ->assertRedirect($this->urlCarrinho());

        $this->assertNotSame(url('/'), $resposta->headers->get('Location'));

        // E a tela para onde ele volta continua sendo a da loja, sem a fábrica.
        $this->semMarcaDoFornecedor($this->followRedirects($resposta)->assertOk()->getContent());
    }

    // ───────────────────── comprovante ─────────────────────

    public function test_comprovante_traz_numero_itens_valores_e_a_orientacao_de_pagamento(): void
    {
        $peca = $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00);
        $this->adicionar($peca);
        $this->get($this->urlCarrinho(['acao' => VitrineCarrinhoService::ACAO_GRAVACAO, 'gravacao' => 1]));

        $this->post(route('vitrine.finalizar', $this->loja), $this->formulario([
            'engraving_text' => 'Ana & Pedro',
            'engraving_date' => '2026-05-12',
        ]));

        $pedido = Order::query()->firstOrFail();

        $this->get(route('vitrine.confirmado', ['store' => $this->loja, 'order' => $pedido->public_number]))
            ->assertOk()
            ->assertSee('Pedido registrado no balcão')
            ->assertSee($pedido->public_number)
            ->assertSee('Aliança Clássica 4mm')
            ->assertSee('Maria Silva')
            ->assertSee('R$ 200,00')
            ->assertSee('R$ 30,00')
            ->assertSee('R$ 230,00')
            ->assertSee('A pagar no caixa da loja')
            ->assertSee('Ana &amp; Pedro', false)
            ->assertSee('Rua das Alianças, 123 - Centro');
    }

    public function test_comprovante_mascara_o_dado_pessoal_do_cliente(): void
    {
        $this->adicionar($this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00));

        $this->post(route('vitrine.finalizar', $this->loja), $this->formulario([
            'email' => 'maria.silva@email.com',
        ]));

        $pedido = Order::query()->firstOrFail();

        // O endereço é público e o número do pedido é curto: o comprovante
        // confirma o pedido, não entrega a ficha do cliente.
        $this->get(route('vitrine.confirmado', ['store' => $this->loja, 'order' => $pedido->public_number]))
            ->assertOk()
            ->assertDontSee(self::CPF)
            ->assertDontSee('529.982')
            ->assertDontSee('98765-4321')
            ->assertDontSee('maria.silva@email.com')
            ->assertSee('***.982.247-**')
            ->assertSee('ma***@email.com');
    }

    public function test_pedido_de_outra_loja_devolve_404_no_comprovante(): void
    {
        $outraLoja = ResellerStore::factory()->published()->create([
            'reseller_id' => Reseller::factory()->approved()->create()->getKey(),
            'slug' => 'joalheria-aurora',
        ]);

        $this->adicionar($this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00));
        $this->post(route('vitrine.finalizar', $this->loja), $this->formulario());

        $pedido = Order::query()->firstOrFail();

        // 404, nunca 403: o número é curto e 403 confirmaria que ele existe.
        $this->get(route('vitrine.confirmado', ['store' => $outraLoja, 'order' => $pedido->public_number]))
            ->assertNotFound();
    }

    public function test_pedido_b2b_do_proprio_lojista_nao_abre_na_vitrine(): void
    {
        // O pedido B2B carrega o CUSTO Velaro em `order_items.unit_price`.
        // Abri-lo aqui entregaria esse custo ao consumidor final.
        $b2b = Order::factory()->create([
            'reseller_id' => $this->tomazelli->getKey(),
            'public_number' => 'ORD777777',
            'meta' => null,
        ]);

        $this->get(route('vitrine.confirmado', ['store' => $this->loja, 'order' => $b2b->public_number]))
            ->assertNotFound();
    }

    public function test_comprovante_nao_abre_por_id_interno(): void
    {
        $this->adicionar($this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00));
        $this->post(route('vitrine.finalizar', $this->loja), $this->formulario());

        $pedido = Order::query()->firstOrFail();

        $this->get('/loja/'.$this->loja->slug.'/pedido/'.$pedido->getKey())->assertNotFound();
    }

    public function test_comprovante_de_loja_nao_publicada_devolve_404(): void
    {
        $this->adicionar($this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 100.00));
        $this->post(route('vitrine.finalizar', $this->loja), $this->formulario());

        $pedido = Order::query()->firstOrFail();
        $this->loja->update(['is_active' => false]);

        $this->get(route('vitrine.confirmado', ['store' => $this->loja, 'order' => $pedido->public_number]))
            ->assertNotFound();
    }

    // ───────────────────── regras 1 e 2 nas telas novas ─────────────────────

    public function test_carrinho_e_comprovante_nao_exibem_marca_velaro_nem_custo_b2b(): void
    {
        $peca = $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 137.45);
        $this->adicionar($peca);

        $carrinho = $this->get($this->urlCarrinho())->assertOk();
        $this->semMarcaDoFornecedor($carrinho->getContent());
        $carrinho->assertDontSee('137,45')->assertDontSee('137.45');

        $this->post(route('vitrine.finalizar', $this->loja), $this->formulario());
        $pedido = Order::query()->firstOrFail();

        $comprovante = $this->get(route('vitrine.confirmado', ['store' => $this->loja, 'order' => $pedido->public_number]))
            ->assertOk()
            ->assertDontSee('137,45')
            ->assertDontSee('137.45')
            ->assertSee('Tomazelli Alianças');

        $this->semMarcaDoFornecedor($comprovante->getContent());
    }

    public function test_nenhum_model_de_produto_chega_aos_dados_das_telas_novas(): void
    {
        $peca = $this->peca('Aliança Clássica 4mm', 'alianca-classica-4mm', 137.45);
        $this->adicionar($peca);

        $this->verificarSemProduct($this->get($this->urlCarrinho())->assertOk()->viewData('carrinho'));

        $this->post(route('vitrine.finalizar', $this->loja), $this->formulario());
        $pedido = Order::query()->firstOrFail();

        $resposta = $this->get(route('vitrine.confirmado', ['store' => $this->loja, 'order' => $pedido->public_number]))
            ->assertOk();

        foreach (['itens', 'valores', 'cliente', 'pedido'] as $chave) {
            $this->verificarSemProduct($resposta->viewData($chave));
        }
    }

    /**
     * A mesma régua do teste da vitrine: nem a marca da fábrica nem a sigla do
     * grupo aparecem no HTML que o consumidor final recebe.
     */
    private function semMarcaDoFornecedor(mixed $conteudo): void
    {
        $this->assertIsString($conteudo);
        $this->assertStringNotContainsStringIgnoringCase('velaro', $conteudo);
        $this->assertStringNotContainsStringIgnoringCase('svd', $conteudo);
    }

    /**
     * O custo B2B só não vaza porque nenhum `Product` chega à view: o resolvedor
     * de preço precisa do custo, então a fronteira é o tipo do que sai do
     * service, não uma coluna escondida.
     */
    private function verificarSemProduct(mixed $valor): void
    {
        $this->assertNotInstanceOf(Product::class, $valor);

        if (is_array($valor)) {
            foreach ($valor as $item) {
                $this->verificarSemProduct($item);
            }
        }
    }
}
