<?php

/*
[Modulo: tests/Feature/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Cobre a tela 2.5: lista filtravel, gaveta, detalhe com os dois status e o isolamento entre lojistas.
*/

namespace Tests\Feature\Portal;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderBatch;
use App\Models\OrderItem;
use App\Models\OrderItemEngraving;
use App\Models\OrderStatusEvent;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Reseller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Tela 2.5 — Pedidos, lista e detalhe.
 *
 * As três regras críticas do escopo têm caso próprio aqui: os dois status são
 * independentes (§6), a gravação adicional aparece no detalhe, e a rota é sempre
 * por `public_number` — `orders.id` não é exposto (§4.5).
 */
class PedidosTest extends TestCase
{
    use RefreshDatabase;

    private Reseller $tomazelli;

    private Reseller $vizinho;

    private User $lojista;

    private Customer $maria;

    private Order $pedido;

    private Order $doVizinho;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tomazelli = Reseller::factory()->approved()->create(['trade_name' => 'Tomazelli Alianças']);
        $this->vizinho = Reseller::factory()->approved()->create(['trade_name' => 'Aliança & Cia']);
        $this->lojista = User::factory()->forReseller($this->tomazelli)->create();

        $this->maria = $this->cliente($this->tomazelli, 'Maria Silva');

        $this->pedido = $this->pedido($this->tomazelli, $this->maria, [
            'public_number' => 'ORD012548',
            'operational_status' => Order::OPERATIONAL_STATUS_IN_PRODUCTION,
            'payment_status' => Order::PAYMENT_STATUS_PENDING,
            'subtotal_amount' => 450.00,
            'engraving_amount' => 35.00,
            'shipping_amount' => 0.00,
            'discount_amount' => 0.00,
            'total_amount' => 485.00,
            'expected_at' => '2026-05-23',
        ]);

        $this->doVizinho = $this->pedido($this->vizinho, $this->cliente($this->vizinho, 'Beatriz Nogueira'), [
            'public_number' => 'ORD099999',
            'total_amount' => 3210.00,
        ]);
    }

    // ─────────────────────────── lista ───────────────────────────

    public function test_a_lista_mostra_numero_cliente_valor_e_os_dois_status(): void
    {
        $resposta = $this->actingAs($this->lojista)->get(route('portal.pedidos.index'));

        $resposta->assertOk()
            ->assertSee('#ORD012548')
            ->assertSee('Maria Silva')
            ->assertSee('R$ 485,00')
            ->assertSee('Status do pedido')
            ->assertSee('Status do pagamento')
            ->assertSee('Em produção')
            ->assertSee('Pendente');
    }

    public function test_os_dois_status_sao_colunas_independentes(): void
    {
        // Regra 1 (§6): um pedido em produção com pagamento pendente é estado
        // válido. Derivar um status do outro apagaria justamente este caso.
        $this->assertSame(Order::OPERATIONAL_STATUS_IN_PRODUCTION, $this->pedido->operational_status);
        $this->assertSame(Order::PAYMENT_STATUS_PENDING, $this->pedido->payment_status);

        $this->actingAs($this->lojista)
            ->get(route('portal.pedidos.index', ['status' => Order::OPERATIONAL_STATUS_IN_PRODUCTION, 'pagamento' => Order::PAYMENT_STATUS_PENDING]))
            ->assertOk()
            ->assertSee('#ORD012548');

        // Filtrar pelo status de pagamento errado não pode trazer o pedido de volta.
        $this->actingAs($this->lojista)
            ->get(route('portal.pedidos.index', ['pagamento' => Order::PAYMENT_STATUS_PAID]))
            ->assertOk()
            ->assertDontSee('#ORD012548');
    }

    public function test_o_filtro_de_periodo_corta_pela_data_do_pedido(): void
    {
        $antigo = $this->pedido($this->tomazelli, $this->maria, [
            'public_number' => 'ORD000111',
            'created_at' => Carbon::now()->subDays(200),
        ]);

        // O padrão da tela é "últimos 90 dias" — o antigo fica de fora.
        $this->actingAs($this->lojista)
            ->get(route('portal.pedidos.index'))
            ->assertOk()
            ->assertSee('#ORD012548')
            ->assertDontSee('#'.$antigo->public_number);

        $this->actingAs($this->lojista)
            ->get(route('portal.pedidos.index', ['periodo' => 0]))
            ->assertOk()
            ->assertSee('#'.$antigo->public_number);
    }

    public function test_a_busca_encontra_por_numero_cliente_e_produto(): void
    {
        $produto = Product::factory()->create(['name' => 'Aliança Clássica 6mm', 'price' => 229.00]);
        $this->item($this->pedido, $produto, 18);

        $outro = $this->pedido($this->tomazelli, $this->cliente($this->tomazelli, 'João Santos'), [
            'public_number' => 'ORD012547',
        ]);

        foreach (['ORD012548', 'Maria', 'Aliança Clássica'] as $busca) {
            $this->actingAs($this->lojista)
                ->get(route('portal.pedidos.index', ['q' => $busca]))
                ->assertOk()
                ->assertSee('#ORD012548')
                ->assertDontSee('#'.$outro->public_number);
        }
    }

    public function test_o_filtro_de_lote_e_o_de_gravacao_entram_pelos_filtros_avancados(): void
    {
        $lote = OrderBatch::factory()->open()->create(['reseller_id' => $this->tomazelli->getKey(), 'code' => 'LOTE-2026-W24']);
        $this->pedido->forceFill(['batch_id' => $lote->getKey()])->save();

        $produto = Product::factory()->create(['name' => 'Aliança Trabalhada 6mm']);
        $item = $this->item($this->pedido, $produto, 18);
        OrderItemEngraving::factory()->create(['order_item_id' => $item->getKey(), 'enabled' => true, 'text' => 'M & J']);

        $semLote = $this->pedido($this->tomazelli, $this->maria, ['public_number' => 'ORD012540']);

        $this->actingAs($this->lojista)
            ->get(route('portal.pedidos.index', ['lote' => 'LOTE-2026-W24']))
            ->assertOk()
            ->assertSee('#ORD012548')
            ->assertDontSee('#'.$semLote->public_number);

        $this->actingAs($this->lojista)
            ->get(route('portal.pedidos.index', ['gravacao' => 'sim']))
            ->assertOk()
            ->assertSee('#ORD012548')
            ->assertDontSee('#'.$semLote->public_number);

        $this->actingAs($this->lojista)
            ->get(route('portal.pedidos.index', ['gravacao' => 'nao']))
            ->assertOk()
            ->assertSee('#'.$semLote->public_number)
            ->assertDontSee('#ORD012548');
    }

    public function test_o_tamanho_de_pagina_do_rodape_e_respeitado(): void
    {
        Order::factory()->count(9)->forReseller($this->tomazelli)->create([
            'customer_id' => $this->maria->getKey(),
            'user_id' => null,
        ]);

        $resposta = $this->actingAs($this->lojista)->get(route('portal.pedidos.index', ['por_pagina' => 8]));

        $resposta->assertOk()->assertSee('8 por página')->assertSee('de 10 pedidos');
    }

    public function test_filtro_invalido_devolve_a_lista_em_vez_de_422(): void
    {
        $this->actingAs($this->lojista)
            ->get(route('portal.pedidos.index', [
                'status' => 'entregue_ontem',
                'pagamento' => 'quase',
                'periodo' => 'sempre',
                'por_pagina' => 999,
                'page' => 'abc',
            ]))
            ->assertOk()
            ->assertSee('#ORD012548');
    }

    // ─────────────────────────── gaveta ───────────────────────────

    public function test_a_gaveta_abre_no_pedido_pedido_pela_query_string(): void
    {
        $this->actingAs($this->lojista)
            ->get(route('portal.pedidos.index', ['pedido' => 'ORD012548']))
            ->assertOk()
            ->assertSee('Pedido #ORD012548')
            ->assertSee('Resumo do pedido (custo Velaro)')
            ->assertSee('Subtotal dos itens');
    }

    public function test_a_gaveta_nao_abre_com_numero_de_outro_lojista(): void
    {
        // A gaveta vem por query string e não passa pelo route model binding — o
        // escopo tem de valer também aqui, e sem mensagem: dizer "não é seu" já
        // confirmaria que o pedido existe.
        $resposta = $this->actingAs($this->lojista)
            ->get(route('portal.pedidos.index', ['pedido' => 'ORD099999']));

        $resposta->assertOk()
            ->assertDontSee('Pedido #ORD099999')
            ->assertDontSee('Beatriz Nogueira')
            ->assertDontSee('R$ 3.210,00');
    }

    // ─────────────────────────── detalhe ───────────────────────────

    public function test_o_detalhe_mostra_itens_gravacao_as_quatro_linhas_de_valor_e_os_dois_status(): void
    {
        $produto = Product::factory()->create([
            'name' => 'Aliança Clássica 6mm',
            'sku' => 'VL-CL-03',
            'price' => 229.00,
            'engraving_max_chars' => 20,
        ]);
        $item = $this->item($this->pedido, $produto, 18, 2, 225.00);
        OrderItemEngraving::factory()->create([
            'order_item_id' => $item->getKey(),
            'enabled' => true,
            'text' => 'Maria + João',
            'chars' => 12,
            'price' => 35.00,
        ]);

        $resposta = $this->actingAs($this->lojista)->get(route('portal.pedidos.show', $this->pedido));

        $resposta->assertOk()
            ->assertSee('Pedido #ORD012548')
            // itens
            ->assertSee('Aliança Clássica 6mm')
            ->assertSee('VL-CL-03')
            ->assertSee('Aro: 18')
            // as quatro linhas do resumo mais o total
            ->assertSee('Subtotal dos itens')
            ->assertSee('R$ 450,00')
            ->assertSee('Gravação interna')
            ->assertSee('R$ 35,00')
            ->assertSee('Frete')
            ->assertSee('Descontos')
            ->assertSee('R$ 485,00')
            // gravação
            ->assertSee('Maria + João')
            ->assertSee('até 20 caracteres')
            // dois status
            ->assertSee('Em produção')
            ->assertSee('Pendente');
    }

    public function test_o_card_de_gravacao_diz_nao_quando_ninguem_pediu_gravacao(): void
    {
        // Ausência de card não é resposta: o lojista precisa ler "Não".
        $produto = Product::factory()->create(['name' => 'Aliança Lisa 4mm']);
        $item = $this->item($this->pedido, $produto, 16);
        OrderItemEngraving::factory()->disabled()->create(['order_item_id' => $item->getKey()]);

        $this->actingAs($this->lojista)
            ->get(route('portal.pedidos.show', $this->pedido))
            ->assertOk()
            ->assertSee('Gravação interna')
            ->assertSee('Solicitada')
            ->assertSee('Não');
    }

    public function test_a_linha_do_tempo_vem_de_order_status_events(): void
    {
        OrderStatusEvent::factory()->opening()->create([
            'order_id' => $this->pedido->getKey(),
            'created_at' => Carbon::parse('2026-05-16 10:32:00'),
        ]);
        OrderStatusEvent::factory()->create([
            'order_id' => $this->pedido->getKey(),
            'from_status' => Order::OPERATIONAL_STATUS_PAYMENT_CONFIRMED,
            'to_status' => Order::OPERATIONAL_STATUS_IN_PRODUCTION,
            'created_at' => Carbon::parse('2026-05-18 09:10:00'),
        ]);

        $resposta = $this->actingAs($this->lojista)->get(route('portal.pedidos.show', $this->pedido));

        $resposta->assertOk()
            ->assertSee('Linha do tempo do pedido')
            ->assertSee('16/05/2026 10:32')
            ->assertSee('18/05/2026 09:10')
            ->assertSee('Histórico de atualizações')
            // o degrau atual é o `now`; os seguintes continuam pendentes
            ->assertSee('tl tl--now', escape: false)
            ->assertSee('tl tl--todo', escape: false);
    }

    public function test_a_rota_e_pelo_numero_publico_e_o_id_interno_nao_abre_a_tela(): void
    {
        // Regra 3 (§4.5): `orders.id` nunca é o identificador externo.
        $this->actingAs($this->lojista)
            ->get(route('portal.pedidos.show', $this->pedido))
            ->assertOk();

        $this->actingAs($this->lojista)
            ->get('/portal/pedidos/'.$this->pedido->getKey())
            ->assertNotFound();
    }

    // ─────────────────────────── isolamento ───────────────────────────

    public function test_a_lista_nao_mostra_pedido_de_outro_lojista(): void
    {
        $this->actingAs($this->lojista)
            ->get(route('portal.pedidos.index', ['periodo' => 0]))
            ->assertOk()
            ->assertSee('#ORD012548')
            ->assertDontSee('#ORD099999')
            ->assertDontSee('Beatriz Nogueira');
    }

    public function test_a_busca_nao_alcanca_o_pedido_de_outro_lojista(): void
    {
        $this->actingAs($this->lojista)
            ->get(route('portal.pedidos.index', ['q' => 'ORD099999', 'periodo' => 0]))
            ->assertOk()
            ->assertDontSee('#ORD099999');
    }

    public function test_pedido_de_outro_lojista_devolve_404(): void
    {
        $this->actingAs($this->lojista)
            ->get(route('portal.pedidos.show', $this->doVizinho))
            ->assertNotFound();
    }

    public function test_o_pedido_de_outro_lojista_e_o_inexistente_respondem_igual(): void
    {
        // Os números são curtos e sequenciais: com 403 o lojista percorreria a
        // faixa e mediria a base do concorrente.
        $alheio = $this->actingAs($this->lojista)->get(route('portal.pedidos.show', $this->doVizinho));
        $inexistente = $this->actingAs($this->lojista)->get(route('portal.pedidos.show', 'ORD777777'));

        $this->assertSame(404, $alheio->status());
        $this->assertSame($inexistente->status(), $alheio->status());
        $this->assertSame($inexistente->getContent(), $alheio->getContent());
    }

    public function test_o_lote_de_outro_lojista_nao_entra_no_filtro_avancado(): void
    {
        OrderBatch::factory()->open()->create(['reseller_id' => $this->vizinho->getKey(), 'code' => 'LOTE-DO-VIZINHO']);

        $this->actingAs($this->lojista)
            ->get(route('portal.pedidos.index'))
            ->assertOk()
            ->assertDontSee('LOTE-DO-VIZINHO');
    }

    public function test_quem_nao_e_revendedor_aprovado_nao_abre_a_lista(): void
    {
        $pendente = User::factory()->forReseller(Reseller::factory()->pending()->create())->create();

        $this->actingAs($pendente)->get(route('portal.pedidos.index'))->assertForbidden();
    }

    // ─────────────────────────── apoio ───────────────────────────

    private function cliente(Reseller $revendedor, string $nome): Customer
    {
        return Customer::factory()->forReseller($revendedor)->create(['name' => $nome, 'user_id' => null]);
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    private function pedido(Reseller $revendedor, Customer $cliente, array $atributos = []): Order
    {
        return Order::factory()->forReseller($revendedor)->create($atributos + [
            'customer_id' => $cliente->getKey(),
            'user_id' => null,
        ]);
    }

    private function item(Order $pedido, Product $produto, int $aro, int $quantidade = 1, float $unitario = 229.00): OrderItem
    {
        $variante = ProductVariant::factory()->create([
            'product_id' => $produto->getKey(),
            'ring_size' => (string) $aro,
        ]);

        return OrderItem::factory()->withVariant($variante)->create([
            'order_id' => $pedido->getKey(),
            'product_id' => $produto->getKey(),
            'quantity' => $quantidade,
            'unit_price' => $unitario,
            'total_price' => round($unitario * $quantidade, 2),
        ]);
    }
}
