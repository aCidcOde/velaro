<?php

/*
[Modulo: tests/Feature/Backend]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Cobre a tela 3.6: abas, lista, detalhe, permissoes granulares, cadastro manual com log e as retiradas.
*/

namespace Tests\Feature\Backend;

use App\Models\AclPermission;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderBatch;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Reseller;
use App\Models\User;
use App\Services\Backend\PedidoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PedidosTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────── LISTA E DETALHE ───────────────────────────────

    public function test_a_lista_mostra_o_pedido_com_o_revendedor_responsavel(): void
    {
        $admin = $this->createBackendAdmin();
        $revendedor = Reseller::factory()->create(['status' => Reseller::STATUS_APPROVED]);
        $pedido = Order::factory()->create([
            'reseller_id' => $revendedor->id,
            'operational_status' => Order::OPERATIONAL_STATUS_IN_TRANSIT,
        ]);

        $this->actingAs($admin)
            ->get(route('backend.pedidos.index'))
            ->assertOk()
            ->assertSee($pedido->public_number)
            // Regra 3.2: o Master ve tudo, sempre com o revendedor identificado.
            ->assertSee($revendedor->trade_name);
    }

    public function test_a_aba_de_transporte_recorta_a_lista(): void
    {
        $admin = $this->createBackendAdmin();
        $emTransporte = Order::factory()->create(['operational_status' => Order::OPERATIONAL_STATUS_IN_TRANSIT]);
        $registrado = Order::factory()->create(['operational_status' => Order::OPERATIONAL_STATUS_REGISTERED]);

        $this->actingAs($admin)
            ->get(route('backend.pedidos.index', ['aba' => PedidoService::ABA_EM_TRANSPORTE, 'periodo' => 0]))
            ->assertOk()
            ->assertSee($emTransporte->public_number)
            ->assertDontSee($registrado->public_number);
    }

    public function test_o_filtro_de_status_operacional_nao_mexe_no_status_financeiro(): void
    {
        $admin = $this->createBackendAdmin();

        // Regra 2: os dois status sao independentes. Este pedido esta pago e
        // ainda em producao — e tem de aparecer no filtro operacional.
        $pedido = Order::factory()->create([
            'operational_status' => Order::OPERATIONAL_STATUS_IN_PRODUCTION,
            'payment_status' => Order::PAYMENT_STATUS_PAID,
        ]);

        $this->actingAs($admin)
            ->get(route('backend.pedidos.index', ['status' => Order::OPERATIONAL_STATUS_IN_PRODUCTION, 'periodo' => 0]))
            ->assertOk()
            ->assertSee($pedido->public_number);
    }

    public function test_o_detalhe_mostra_os_dois_status_os_itens_e_a_gravacao(): void
    {
        $admin = $this->createBackendAdmin();
        $pedido = $this->pedidoComItem();

        $this->actingAs($admin)
            ->get(route('backend.pedidos.show', $pedido))
            ->assertOk()
            ->assertSee('Aliança Classic 4mm')
            ->assertSee('M &amp; S', false)
            ->assertSee(trans('order.operational_status.'.$pedido->operational_status, [], 'pt_BR'))
            ->assertSee(trans('order.payment_status.'.$pedido->payment_status, [], 'pt_BR'));
    }

    public function test_usuario_sem_a_permissao_de_ver_nao_abre_pedidos(): void
    {
        $admin = $this->semPermissao('velaro.orders.view');

        $this->actingAs($admin)->get(route('backend.pedidos.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('backend.pedidos.show', $this->pedidoComItem()))->assertForbidden();
    }

    /**
     * O grupo `/backend` inteiro passa por `can:access-backend`. Este teste
     * parte de um admin que TEM esse acesso e nenhuma permissao `velaro.*`:
     * se alguma rota da tela abrisse assim, o gate granular nao estaria
     * aplicado.
     */
    public function test_cada_rota_de_pedidos_exige_o_proprio_gate_e_nao_so_o_acesso_ao_painel(): void
    {
        [$revendedor, $variante] = $this->catalogo();
        $pedido = Order::factory()->create();

        $rotas = [
            ['get', route('backend.pedidos.index'), [], 'velaro.orders.view'],
            ['get', route('backend.pedidos.show', $pedido), [], 'velaro.orders.view'],
            ['get', route('backend.pedidos.create'), [], 'velaro.orders.update_status'],
            ['post', route('backend.pedidos.store'), $this->dadosValidos($revendedor, $variante), 'velaro.orders.update_status'],
        ];

        foreach ($rotas as [$verbo, $url, $carga, $chave]) {
            $semNada = $this->adminSemVelaro();
            $this->assertTrue($semNada->canAccessBackend(), 'o usuario entra no painel');
            $this->actingAs($semNada)->$verbo($url, $carga)->assertForbidden();

            $resposta = $this->actingAs($this->apenasCom($chave))->$verbo($url, $carga);
            $this->assertNotSame(403, $resposta->getStatusCode(), $verbo.' '.$url.' com '.$chave);
        }
    }

    public function test_ver_pedidos_nao_basta_para_criar_pedido(): void
    {
        [$revendedor, $variante] = $this->catalogo();

        $this->actingAs($this->apenasCom('velaro.orders.view'))
            ->post(route('backend.pedidos.store'), $this->dadosValidos($revendedor, $variante))
            ->assertForbidden();

        $this->assertSame(0, Order::query()->count());
    }

    // ─────────────────────────────── CADASTRO MANUAL ───────────────────────────────

    public function test_o_formulario_de_novo_pedido_exige_permissao_de_escrita(): void
    {
        $this->actingAs($this->semPermissao('velaro.orders.update_status'))
            ->get(route('backend.pedidos.create'))
            ->assertForbidden();
    }

    public function test_o_formulario_de_novo_pedido_abre_com_catalogo_e_revendedores(): void
    {
        $admin = $this->createBackendAdmin();
        [$revendedor, $variante] = $this->catalogo();

        $this->actingAs($admin)
            ->get(route('backend.pedidos.create'))
            ->assertOk()
            ->assertSee($revendedor->trade_name)
            ->assertSee($variante->sku)
            ->assertSee('Canal de origem');
    }

    public function test_cria_pedido_interno_com_snapshot_de_preco_timeline_e_log(): void
    {
        $admin = $this->createBackendAdmin();
        [$revendedor, $variante] = $this->catalogo();

        $this->actingAs($admin)
            ->post(route('backend.pedidos.store'), $this->dadosValidos($revendedor, $variante))
            ->assertRedirect();

        $pedido = Order::query()->where('reseller_id', $revendedor->id)->firstOrFail();

        // Os dois status nascem independentes e nenhum deles vem do formulario.
        $this->assertSame(Order::OPERATIONAL_STATUS_REGISTERED, $pedido->operational_status);
        $this->assertSame(Order::PAYMENT_STATUS_PENDING, $pedido->payment_status);
        $this->assertSame($admin->id, $pedido->user_id);

        // `unit_price` e copiado do catalogo no momento da selecao: mudar o
        // produto depois nao pode reescrever o que o pedido custou.
        $item = $pedido->items()->firstOrFail();
        $this->assertSame('1120.00', (string) $item->unit_price);
        $this->assertSame('2240.00', (string) $item->total_price);
        $this->assertSame('2240.00', (string) $pedido->subtotal_amount);
        $this->assertSame('2240.00', (string) $pedido->total_amount);

        $this->assertDatabaseHas('order_status_events', [
            'order_id' => $pedido->id,
            'to_status' => Order::OPERATIONAL_STATUS_REGISTERED,
            'actor_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('order_item_engravings', [
            'order_item_id' => $item->id,
            'enabled' => true,
            'text' => 'M & S',
        ]);
        // Criterio de aceite das 12 telas do Master: escrita gera audit_logs.
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'velaro.order.created',
            'target_id' => $pedido->id,
            'actor_id' => $admin->id,
        ]);
    }

    /**
     * `order_items.unit_price` é snapshot imutável: mexer no catálogo depois
     * não pode reescrever o que o pedido custou.
     */
    public function test_mudar_o_preco_do_catalogo_nao_reescreve_pedido_ja_criado(): void
    {
        $admin = $this->createBackendAdmin();
        [$revendedor, $variante] = $this->catalogo();

        $this->actingAs($admin)
            ->post(route('backend.pedidos.store'), $this->dadosValidos($revendedor, $variante))
            ->assertRedirect();

        $pedido = Order::query()->firstOrFail();
        $item = $pedido->items()->firstOrFail();

        $variante->product?->forceFill(['price' => 4321.00])->save();

        $this->assertSame('1120.00', (string) $item->refresh()->unit_price);
        $this->assertSame('2240.00', (string) $item->total_price);
        $this->assertSame('2240.00', (string) $pedido->refresh()->total_amount);
    }

    public function test_cria_a_ficha_do_cliente_final_na_carteira_do_revendedor(): void
    {
        $admin = $this->createBackendAdmin();
        [$revendedor, $variante] = $this->catalogo();

        $this->actingAs($admin)
            ->post(route('backend.pedidos.store'), $this->dadosValidos($revendedor, $variante))
            ->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'reseller_id' => $revendedor->id,
            'document' => '123.456.789-00',
            'name' => 'Maria Silva Oliveira',
        ]);
    }

    public function test_pedido_sem_item_e_recusado(): void
    {
        $admin = $this->createBackendAdmin();
        [$revendedor, $variante] = $this->catalogo();

        $dados = $this->dadosValidos($revendedor, $variante);
        // Linha em branco e linha nao usada, nao item vazio: sai antes das regras
        // e o pedido fica sem nenhum item.
        $dados['itens'] = [['product_variant_id' => '', 'quantity' => 1]];

        $this->actingAs($admin)
            ->from(route('backend.pedidos.create'))
            ->post(route('backend.pedidos.store'), $dados)
            ->assertSessionHasErrors('itens');

        $this->assertSame(0, Order::query()->count());
    }

    public function test_lote_de_outro_revendedor_e_recusado(): void
    {
        $admin = $this->createBackendAdmin();
        [$revendedor, $variante] = $this->catalogo();
        $lote = OrderBatch::factory()->create();

        $dados = $this->dadosValidos($revendedor, $variante);
        $dados['batch_id'] = $lote->id;

        $this->actingAs($admin)
            ->from(route('backend.pedidos.create'))
            ->post(route('backend.pedidos.store'), $dados)
            ->assertSessionHasErrors('batch_id');

        $this->assertSame(0, Order::query()->count());
    }

    public function test_usuario_sem_permissao_de_escrita_nao_cria_pedido(): void
    {
        $admin = $this->semPermissao('velaro.orders.update_status');
        [$revendedor, $variante] = $this->catalogo();

        $this->actingAs($admin)
            ->post(route('backend.pedidos.store'), $this->dadosValidos($revendedor, $variante))
            ->assertForbidden();

        $this->assertSame(0, Order::query()->count());
    }

    // ─────────────────────────────── ESTEIRA E RETIRADA ───────────────────────────────

    public function test_avanca_um_degrau_da_esteira_e_registra_evento_e_log(): void
    {
        $admin = $this->createBackendAdmin();
        $this->actingAs($admin);

        $pedido = Order::factory()->create(['operational_status' => Order::OPERATIONAL_STATUS_REGISTERED]);

        app(PedidoService::class)->avancarStatus(
            $pedido,
            Order::OPERATIONAL_STATUS_PAYMENT_CONFIRMED,
            $admin,
            'Pagamento do lote compensado.',
        );

        $this->assertSame(Order::OPERATIONAL_STATUS_PAYMENT_CONFIRMED, $pedido->refresh()->operational_status);
        // Regra 2: mover a esteira nao mexe no status financeiro.
        $this->assertSame(Order::PAYMENT_STATUS_PENDING, $pedido->payment_status);
        $this->assertDatabaseHas('order_status_events', [
            'order_id' => $pedido->id,
            'from_status' => Order::OPERATIONAL_STATUS_REGISTERED,
            'to_status' => Order::OPERATIONAL_STATUS_PAYMENT_CONFIRMED,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'velaro.order.status_updated', 'target_id' => $pedido->id]);
    }

    public function test_pular_degrau_da_esteira_e_recusado(): void
    {
        $admin = $this->createBackendAdmin();
        $this->actingAs($admin);

        $pedido = Order::factory()->create(['operational_status' => Order::OPERATIONAL_STATUS_REGISTERED]);

        $this->expectException(ValidationException::class);

        app(PedidoService::class)->avancarStatus($pedido, Order::OPERATIONAL_STATUS_IN_TRANSIT, $admin);
    }

    public function test_chegada_na_loja_notifica_revendedor_e_cliente(): void
    {
        $admin = $this->createBackendAdmin();
        $this->actingAs($admin);

        $revendedor = Reseller::factory()->create(['email' => 'loja@teste.test']);
        $cliente = Customer::factory()->create(['reseller_id' => $revendedor->id, 'email' => 'cliente@teste.test']);
        $pedido = Order::factory()->create([
            'reseller_id' => $revendedor->id,
            'customer_id' => $cliente->id,
            'operational_status' => Order::OPERATIONAL_STATUS_IN_TRANSIT,
        ]);

        app(PedidoService::class)->confirmarChegada($pedido, $admin);

        $pedido->refresh();
        $this->assertSame(Order::OPERATIONAL_STATUS_READY_FOR_PICKUP, $pedido->operational_status);
        $this->assertNotNull($pedido->arrived_at);
        $this->assertDatabaseHas('notification_logs', ['order_id' => $pedido->id, 'recipient_type' => 'reseller']);
        $this->assertDatabaseHas('notification_logs', ['order_id' => $pedido->id, 'recipient_type' => 'customer']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'velaro.order.arrived', 'target_id' => $pedido->id]);
    }

    public function test_confirma_retirada_por_pedido_com_portador_e_log(): void
    {
        $admin = $this->createBackendAdmin();
        $this->actingAs($admin);

        $pedido = Order::factory()->create(['operational_status' => Order::OPERATIONAL_STATUS_READY_FOR_PICKUP]);

        app(PedidoService::class)->confirmarRetirada($pedido, $admin, [
            'picked_up_by_name' => 'Maria Silva Oliveira',
            'picked_up_by_document' => '123.456.789-00',
            'note' => 'Retirada conferida no balcão.',
        ]);

        $pedido->refresh();
        $this->assertSame(Order::OPERATIONAL_STATUS_PICKED_UP, $pedido->operational_status);
        $this->assertSame('Maria Silva Oliveira', $pedido->picked_up_by_name);
        $this->assertNotNull($pedido->picked_up_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'velaro.order.picked_up', 'target_id' => $pedido->id]);
    }

    public function test_confirma_retirada_do_lote_inteiro_e_preserva_o_que_ja_saiu(): void
    {
        $admin = $this->createBackendAdmin();
        $this->actingAs($admin);

        $lote = OrderBatch::factory()->create();
        $doLote = Order::factory()->count(2)->create([
            'batch_id' => $lote->id,
            'reseller_id' => $lote->reseller_id,
            'operational_status' => Order::OPERATIONAL_STATUS_READY_FOR_PICKUP,
        ]);
        $jaRetirado = Order::factory()->create([
            'batch_id' => $lote->id,
            'reseller_id' => $lote->reseller_id,
            'operational_status' => Order::OPERATIONAL_STATUS_PICKED_UP,
            'picked_up_at' => now()->subDay(),
            'picked_up_by_name' => 'Quem retirou antes',
        ]);

        $carimbados = app(PedidoService::class)->confirmarRetiradaDoLote($lote, $admin, [
            'picked_up_by_name' => 'Lucas Tomazelli',
            'picked_up_by_document' => '987.654.321-00',
        ]);

        $this->assertSame(2, $carimbados);

        foreach ($doLote as $pedido) {
            $this->assertSame(Order::OPERATIONAL_STATUS_PICKED_UP, $pedido->refresh()->operational_status);
            $this->assertSame('Lucas Tomazelli', $pedido->picked_up_by_name);
        }

        // Pedido ja retirado mantem a data e o portador que tinha.
        $this->assertSame('Quem retirou antes', $jaRetirado->refresh()->picked_up_by_name);

        $lote->refresh();
        $this->assertNotNull($lote->picked_up_at);
        $this->assertSame('Lucas Tomazelli', $lote->picked_up_by_name);
        $this->assertDatabaseHas('audit_logs', ['action' => 'velaro.order_batch.picked_up', 'target_id' => $lote->id]);
    }

    // ─────────────────────────────── APOIO ───────────────────────────────

    /**
     * @return array{0: Reseller, 1: ProductVariant}
     */
    private function catalogo(): array
    {
        $revendedor = Reseller::factory()->create(['status' => Reseller::STATUS_APPROVED]);
        $produto = Product::factory()->create([
            'name' => 'Aliança Classic 4mm',
            'price' => 1120.00,
            'is_active' => true,
            'allows_engraving' => true,
        ]);
        $variante = ProductVariant::factory()->forProduct($produto)->withRingSize(18)->create(['is_active' => true]);

        return [$revendedor, $variante];
    }

    /**
     * @return array<string, mixed>
     */
    private function dadosValidos(Reseller $revendedor, ProductVariant $variante): array
    {
        return [
            'reseller_id' => $revendedor->id,
            'origin_channel' => 'whatsapp',
            'reference' => 'PC-2026-114',
            'customer_name' => 'Maria Silva Oliveira',
            'customer_document' => '123.456.789-00',
            'customer_phone' => '(11) 98765-4321',
            'customer_email' => 'maria@teste.test',
            'itens' => [
                ['product_variant_id' => $variante->id, 'quantity' => 2, 'engraving_text' => 'M & S'],
            ],
            'payment_method' => 'pix',
            'production_days' => 7,
            'due_date' => now()->addDays(15)->format('Y-m-d'),
            'delivery_mode' => 'remessa_semanal',
            'expected_at' => now()->addDays(14)->format('Y-m-d'),
            'notes' => 'Cliente solicitou gravação interna.',
        ];
    }

    private function pedidoComItem(): Order
    {
        $revendedor = Reseller::factory()->create(['status' => Reseller::STATUS_APPROVED]);
        $produto = Product::factory()->create(['name' => 'Aliança Classic 4mm', 'price' => 1120.00]);
        $variante = ProductVariant::factory()->forProduct($produto)->withRingSize(18)->create();

        $pedido = Order::factory()->create([
            'reseller_id' => $revendedor->id,
            'operational_status' => Order::OPERATIONAL_STATUS_IN_TRANSIT,
            'payment_status' => Order::PAYMENT_STATUS_PAID,
        ]);

        $item = OrderItem::factory()->create([
            'order_id' => $pedido->id,
            'product_id' => $produto->id,
            'product_variant_id' => $variante->id,
            'quantity' => 1,
            'unit_price' => 1120.00,
            'total_price' => 1120.00,
        ]);

        $item->engraving()->create(['enabled' => true, 'text' => 'M & S', 'chars' => 5, 'price' => 0]);

        return $pedido;
    }

    private function semPermissao(string $chave): User
    {
        $admin = $this->createBackendAdmin();
        $admin->permissionOverrides()->updateOrCreate(
            ['permission_id' => $this->permissaoId($chave)],
            ['is_allowed' => false],
        );

        return $admin->refresh();
    }

    /** Admin que entra no painel e não tem NENHUMA permissão `velaro.*`. */
    private function adminSemVelaro(): User
    {
        $admin = $this->createBackendAdmin();

        /** @var list<int> $chaves */
        $chaves = AclPermission::query()->where('key', 'like', 'velaro.%')->pluck('id')->all();

        foreach ($chaves as $id) {
            $admin->permissionOverrides()->updateOrCreate(['permission_id' => $id], ['is_allowed' => false]);
        }

        return $admin->refresh();
    }

    /** O mesmo admin, com exatamente as permissões pedidas — e nada além. */
    private function apenasCom(string ...$chaves): User
    {
        $admin = $this->adminSemVelaro();

        foreach ($chaves as $chave) {
            $admin->permissionOverrides()->updateOrCreate(
                ['permission_id' => $this->permissaoId($chave)],
                ['is_allowed' => true],
            );
        }

        return $admin->refresh();
    }

    private function permissaoId(string $chave): int
    {
        /** @var int $id */
        $id = AclPermission::query()->where('key', $chave)->value('id');

        return $id;
    }
}
