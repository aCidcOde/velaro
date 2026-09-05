<?php

/*
[Modulo: tests/Feature/Backend]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Cobre a tela 3.4: saldo por SKU e local, permissoes por tipo, movimento com before/after e log.
*/

namespace Tests\Feature\Backend;

use App\Models\AclPermission;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductionRequest;
use App\Models\ProductVariant;
use App\Models\StockItem;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Backend\EstoqueService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EstoqueTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────── LEITURA ───────────────────────────────

    public function test_a_tabela_soma_os_aros_do_produto(): void
    {
        $admin = $this->createBackendAdmin();
        [$produto, $variante, $local] = $this->catalogoComSaldo(onHand: 40, reserved: 4, minimum: 10);
        // Segundo aro do mesmo produto: a linha da tela e o produto, a escrita e o aro.
        $segundo = ProductVariant::factory()->forProduct($produto)->withRingSize(20)->create();
        StockItem::factory()->forVariant($segundo)->atLocation($local)->create([
            'on_hand' => 60, 'reserved' => 6, 'available' => 54, 'minimum' => 10, 'restock_point' => 30,
        ]);

        $this->actingAs($admin)
            ->get(route('backend.estoque.index'))
            ->assertOk()
            ->assertSee($produto->name)
            ->assertSee('100')   // 40 + 60 em estoque
            ->assertSee('18 - 20'); // faixa de aros

        $this->assertSame(18, (int) $variante->getAttribute('ring_size'));
    }

    public function test_o_filtro_de_situacao_separa_baixo_estoque(): void
    {
        $admin = $this->createBackendAdmin();
        [$emEstoque] = $this->catalogoComSaldo(onHand: 90, reserved: 0, minimum: 10);
        [$baixo] = $this->catalogoComSaldo(onHand: 3, reserved: 0, minimum: 10);

        $this->actingAs($admin)
            ->get(route('backend.estoque.index', ['situacao' => 'low_stock']))
            ->assertOk()
            ->assertSee($baixo->name)
            ->assertDontSee($emEstoque->name);
    }

    public function test_a_gaveta_abre_o_item_escolhido(): void
    {
        $admin = $this->createBackendAdmin();
        [$produto] = $this->catalogoComSaldo(onHand: 40, reserved: 4, minimum: 10);

        $this->actingAs($admin)
            ->get(route('backend.estoque.index', ['produto' => $produto->id]))
            ->assertOk()
            ->assertSee('Estoque por tamanho')
            ->assertSee('Últimas movimentações');
    }

    public function test_usuario_sem_a_permissao_de_ver_nao_abre_o_estoque(): void
    {
        $admin = $this->semPermissao('velaro.stock.view');
        [, $variante] = $this->catalogoComSaldo();

        $this->actingAs($admin)->get(route('backend.estoque.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('backend.estoque.historico', ['variant' => $variante->id]))->assertForbidden();
    }

    /**
     * O grupo `/backend` inteiro passa por `can:access-backend`. Este teste
     * parte de um admin que TEM esse acesso e nenhuma permissao `velaro.*`:
     * se alguma rota da tela abrisse assim, o gate granular nao estaria
     * aplicado.
     */
    public function test_cada_rota_do_estoque_exige_o_proprio_gate_e_nao_so_o_acesso_ao_painel(): void
    {
        [$produto, $variante, $local] = $this->catalogoComSaldo();

        $rotas = [
            ['get', route('backend.estoque.index'), [], 'velaro.stock.view'],
            ['get', route('backend.estoque.historico', ['variant' => $variante->id]), [], 'velaro.stock.view'],
            ['get', route('backend.estoque.movimentacao'), [], 'velaro.stock.adjust'],
            ['post', route('backend.estoque.movimentacao.store'), $this->dadosDeEntrada($produto, $variante, $local), 'velaro.stock.adjust'],
        ];

        foreach ($rotas as [$verbo, $url, $carga, $chave]) {
            $semNada = $this->adminSemVelaro();
            $this->assertTrue($semNada->canAccessBackend(), 'o usuario entra no painel');
            $this->actingAs($semNada)->$verbo($url, $carga)->assertForbidden();

            $resposta = $this->actingAs($this->apenasCom($chave))->$verbo($url, $carga);
            $this->assertNotSame(403, $resposta->getStatusCode(), $verbo.' '.$url.' com '.$chave);
        }
    }

    public function test_ver_estoque_nao_basta_para_mover_saldo(): void
    {
        [$produto, $variante, $local] = $this->catalogoComSaldo(onHand: 10, reserved: 0, minimum: 2);

        $dados = $this->dadosDeEntrada($produto, $variante, $local, 99);
        $dados['type'] = StockMovement::TYPE_ADJUSTMENT;

        $this->actingAs($this->apenasCom('velaro.stock.view'))
            ->post(route('backend.estoque.movimentacao.store'), $dados)
            ->assertForbidden();

        $this->assertSame(10, StockItem::query()->where('product_variant_id', $variante->id)->firstOrFail()->on_hand);
    }

    public function test_ajustar_estoque_nao_basta_para_abrir_ordem_de_producao(): void
    {
        [$produto, $variante, $local] = $this->catalogoComSaldo();

        $dados = $this->dadosDeEntrada($produto, $variante, $local, 5);
        $dados['type'] = StockMovement::TYPE_PRODUCTION;
        $dados['due_date'] = now()->addDays(5)->format('Y-m-d');

        $this->actingAs($this->apenasCom('velaro.stock.adjust'))
            ->post(route('backend.estoque.movimentacao.store'), $dados)
            ->assertForbidden();

        $this->assertSame(0, ProductionRequest::query()->count());
    }

    public function test_o_extrato_mostra_o_antes_e_o_depois_do_movimento(): void
    {
        $admin = $this->createBackendAdmin();
        [, $variante] = $this->catalogoComSaldo(onHand: 40, reserved: 4, minimum: 10);
        $item = StockItem::query()->where('product_variant_id', $variante->id)->firstOrFail();

        StockMovement::factory()->create([
            'stock_item_id' => $item->id,
            'type' => StockMovement::TYPE_ADJUSTMENT,
            'qty' => -2,
            'before' => 38,
            'after' => 36,
            'reason' => 'Inventário — divergência de contagem',
        ]);

        $this->actingAs($admin)
            ->get(route('backend.estoque.historico', ['variant' => $variante->id, 'aro' => '']))
            ->assertOk()
            ->assertSee('Inventário — divergência de contagem')
            ->assertSee('38')
            ->assertSee('36');
    }

    // ─────────────────────────────── PERMISSOES DE ESCRITA ───────────────────────────────

    public function test_quem_nao_pode_escrever_nao_abre_o_formulario_de_movimentacao(): void
    {
        $admin = $this->createBackendAdmin();
        $this->negar($admin, 'velaro.stock.adjust');
        $this->negar($admin, 'velaro.stock.request_production');

        $this->actingAs($admin->refresh())
            ->get(route('backend.estoque.movimentacao'))
            ->assertForbidden();
    }

    public function test_o_formulario_de_movimentacao_abre_com_o_item_da_gaveta(): void
    {
        $admin = $this->createBackendAdmin();
        [, $variante] = $this->catalogoComSaldo(onHand: 28, reserved: 4, minimum: 10);

        $this->actingAs($admin)
            ->get(route('backend.estoque.movimentacao', ['variante' => $variante->id, 'tipo' => StockMovement::TYPE_ADJUSTMENT]))
            ->assertOk()
            ->assertSee($variante->sku)
            ->assertSee('No ajuste, a quantidade é o novo saldo do aro')
            ->assertSee('velaro.stock.request_production');
    }

    public function test_sem_velaro_stock_adjust_a_entrada_e_recusada(): void
    {
        $admin = $this->semPermissao('velaro.stock.adjust');
        [$produto, $variante, $local] = $this->catalogoComSaldo();

        $this->actingAs($admin)
            ->post(route('backend.estoque.movimentacao.store'), $this->dadosDeEntrada($produto, $variante, $local))
            ->assertForbidden();

        $this->assertSame(0, StockMovement::query()->count());
    }

    public function test_sem_velaro_stock_request_production_a_ordem_e_recusada(): void
    {
        $admin = $this->semPermissao('velaro.stock.request_production');
        [$produto, $variante, $local] = $this->catalogoComSaldo();

        $dados = $this->dadosDeEntrada($produto, $variante, $local);
        $dados['type'] = StockMovement::TYPE_PRODUCTION;
        $dados['due_date'] = now()->addDays(10)->format('Y-m-d');

        $this->actingAs($admin)
            ->post(route('backend.estoque.movimentacao.store'), $dados)
            ->assertForbidden();

        $this->assertSame(0, ProductionRequest::query()->count());
    }

    // ─────────────────────────────── ESCRITA ───────────────────────────────

    public function test_entrada_soma_o_saldo_grava_movimento_e_log(): void
    {
        $admin = $this->createBackendAdmin();
        [$produto, $variante, $local] = $this->catalogoComSaldo(onHand: 28, reserved: 4, minimum: 10);

        $this->actingAs($admin)
            ->post(route('backend.estoque.movimentacao.store'), $this->dadosDeEntrada($produto, $variante, $local, 30))
            ->assertRedirect();

        $item = StockItem::query()->where('product_variant_id', $variante->id)->firstOrFail();
        $this->assertSame(58, $item->on_hand);
        $this->assertSame(54, $item->available);

        // Regra 3: saldo nunca muda sem movimento correspondente.
        $this->assertDatabaseHas('stock_movements', [
            'stock_item_id' => $item->id,
            'type' => StockMovement::TYPE_INBOUND,
            'qty' => 30,
            'before' => 24,
            'after' => 54,
            'actor_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'velaro.stock.moved', 'target_id' => $item->id]);
    }

    public function test_ajuste_manual_define_o_novo_saldo_e_gera_log_proprio(): void
    {
        $admin = $this->createBackendAdmin();
        [$produto, $variante, $local] = $this->catalogoComSaldo(onHand: 46, reserved: 2, minimum: 10);

        $dados = $this->dadosDeEntrada($produto, $variante, $local, 44);
        $dados['type'] = StockMovement::TYPE_ADJUSTMENT;
        $dados['reason'] = 'Inventário — divergência de contagem';

        $this->actingAs($admin)
            ->post(route('backend.estoque.movimentacao.store'), $dados)
            ->assertRedirect();

        $item = StockItem::query()->where('product_variant_id', $variante->id)->firstOrFail();
        $this->assertSame(44, $item->on_hand);
        $this->assertSame(42, $item->available);

        // Acao sensivel (§7): before/after no movimento e linha propria no log.
        $this->assertDatabaseHas('stock_movements', [
            'stock_item_id' => $item->id,
            'type' => StockMovement::TYPE_ADJUSTMENT,
            'qty' => -2,
            'before' => 44,
            'after' => 42,
            'reason' => 'Inventário — divergência de contagem',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'velaro.stock.adjusted', 'target_id' => $item->id]);
    }

    public function test_movimentacao_sem_motivo_e_recusada(): void
    {
        $admin = $this->createBackendAdmin();
        [$produto, $variante, $local] = $this->catalogoComSaldo();

        $dados = $this->dadosDeEntrada($produto, $variante, $local);
        $dados['reason'] = '';

        $this->actingAs($admin)
            ->from(route('backend.estoque.movimentacao'))
            ->post(route('backend.estoque.movimentacao.store'), $dados)
            ->assertSessionHasErrors('reason');

        $this->assertSame(0, StockMovement::query()->count());
    }

    public function test_saida_maior_que_o_disponivel_e_recusada_e_nao_escreve_saldo(): void
    {
        $admin = $this->createBackendAdmin();
        [$produto, $variante, $local] = $this->catalogoComSaldo(onHand: 10, reserved: 4, minimum: 2);

        $dados = $this->dadosDeEntrada($produto, $variante, $local, 9);
        $dados['type'] = StockMovement::TYPE_OUTBOUND;

        $this->actingAs($admin)
            ->from(route('backend.estoque.movimentacao'))
            ->post(route('backend.estoque.movimentacao.store'), $dados)
            ->assertSessionHasErrors('quantity');

        $item = StockItem::query()->where('product_variant_id', $variante->id)->firstOrFail();
        $this->assertSame(10, $item->on_hand);
        $this->assertSame(0, StockMovement::query()->count());
    }

    public function test_reserva_consome_o_disponivel_sem_mexer_no_saldo_fisico(): void
    {
        $admin = $this->createBackendAdmin();
        [$produto, $variante, $local] = $this->catalogoComSaldo(onHand: 20, reserved: 0, minimum: 2);
        $pedido = Order::factory()->create();

        $dados = $this->dadosDeEntrada($produto, $variante, $local, 6);
        $dados['type'] = StockMovement::TYPE_RESERVATION;
        $dados['order_id'] = $pedido->id;
        $dados['reason'] = 'Reserva de pedido';

        $this->actingAs($admin)
            ->post(route('backend.estoque.movimentacao.store'), $dados)
            ->assertRedirect();

        $item = StockItem::query()->where('product_variant_id', $variante->id)->firstOrFail();
        $this->assertSame(20, $item->on_hand);
        $this->assertSame(6, $item->reserved);
        $this->assertSame(14, $item->available);
        $this->assertDatabaseHas('stock_movements', [
            'stock_item_id' => $item->id,
            'type' => StockMovement::TYPE_RESERVATION,
            'before' => 20,
            'after' => 14,
            'order_id' => $pedido->id,
        ]);
    }

    public function test_reserva_sem_pedido_vinculado_e_recusada(): void
    {
        $admin = $this->createBackendAdmin();
        [$produto, $variante, $local] = $this->catalogoComSaldo();

        $dados = $this->dadosDeEntrada($produto, $variante, $local, 2);
        $dados['type'] = StockMovement::TYPE_RESERVATION;

        $this->actingAs($admin)
            ->from(route('backend.estoque.movimentacao'))
            ->post(route('backend.estoque.movimentacao.store'), $dados)
            ->assertSessionHasErrors('order_id');
    }

    public function test_solicitar_producao_abre_ordem_sem_mexer_no_saldo(): void
    {
        $admin = $this->createBackendAdmin();
        [$produto, $variante, $local] = $this->catalogoComSaldo(onHand: 12, reserved: 0, minimum: 20);

        $dados = $this->dadosDeEntrada($produto, $variante, $local, 20);
        $dados['type'] = StockMovement::TYPE_PRODUCTION;
        $dados['due_date'] = now()->addDays(10)->format('Y-m-d');
        $dados['priority'] = ProductionRequest::PRIORITY_HIGH;
        $dados['reason'] = 'Reposição do aro — saldo abaixo do mínimo';

        $this->actingAs($admin)
            ->post(route('backend.estoque.movimentacao.store'), $dados)
            ->assertRedirect();

        $this->assertDatabaseHas('production_requests', [
            'product_variant_id' => $variante->id,
            'qty_requested' => 20,
            'status' => ProductionRequest::STATUS_PENDING,
            'priority' => ProductionRequest::PRIORITY_HIGH,
            'requested_by' => $admin->id,
        ]);

        // Producao nao move saldo: a peca entra no cofre depois, como entrada.
        $item = StockItem::query()->where('product_variant_id', $variante->id)->firstOrFail();
        $this->assertSame(12, $item->on_hand);
        $this->assertSame(0, StockMovement::query()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'velaro.stock.production_requested']);
    }

    public function test_producao_sem_prazo_previsto_e_recusada(): void
    {
        $admin = $this->createBackendAdmin();
        [$produto, $variante, $local] = $this->catalogoComSaldo();

        $dados = $this->dadosDeEntrada($produto, $variante, $local, 20);
        $dados['type'] = StockMovement::TYPE_PRODUCTION;

        $this->actingAs($admin)
            ->from(route('backend.estoque.movimentacao'))
            ->post(route('backend.estoque.movimentacao.store'), $dados)
            ->assertSessionHasErrors('due_date');

        $this->assertSame(0, ProductionRequest::query()->count());
    }

    public function test_aro_de_outro_produto_e_recusado(): void
    {
        $admin = $this->createBackendAdmin();
        [, $variante, $local] = $this->catalogoComSaldo();
        $outro = Product::factory()->create();

        $dados = $this->dadosDeEntrada($outro, $variante, $local);

        $this->actingAs($admin)
            ->from(route('backend.estoque.movimentacao'))
            ->post(route('backend.estoque.movimentacao.store'), $dados)
            ->assertSessionHasErrors('product_variant_id');

        $this->assertSame(0, StockMovement::query()->count());
    }

    /**
     * Regra 3 do doc 3.4, na forma mais dura: nenhum `UPDATE` em `stock_items`
     * pode existir sem a linha de `stock_movements` que o explica. O teste
     * conta os updates de verdade, e confere que a cadeia `before`/`after`
     * fecha — é ela que permite auditar o saldo sem depender do valor atual.
     */
    public function test_nenhum_saldo_muda_sem_movimento_correspondente(): void
    {
        $admin = $this->createBackendAdmin();
        [$produto, $variante, $local] = $this->catalogoComSaldo(onHand: 20, reserved: 2, minimum: 5);

        $updates = 0;
        DB::listen(function (QueryExecuted $consulta) use (&$updates): void {
            if (preg_match('/^update\s+["`\[]?stock_items["`\]]?/i', trim($consulta->sql)) === 1) {
                $updates++;
            }
        });

        foreach ([StockMovement::TYPE_INBOUND, StockMovement::TYPE_OUTBOUND, StockMovement::TYPE_ADJUSTMENT] as $tipo) {
            $dados = $this->dadosDeEntrada($produto, $variante, $local, 4);
            $dados['type'] = $tipo;

            $this->actingAs($admin)
                ->post(route('backend.estoque.movimentacao.store'), $dados)
                ->assertRedirect();
        }

        $this->assertSame(3, $updates, 'todo UPDATE em stock_items tem um movimento correspondente');
        $this->assertSame(3, StockMovement::query()->count());

        $linhas = StockMovement::query()->orderBy('id')->get();
        $item = StockItem::query()->where('product_variant_id', $variante->id)->firstOrFail();

        $this->assertSame($item->available, $linhas->last()?->after);

        foreach ($linhas as $posicao => $linha) {
            if ($posicao === 0) {
                continue;
            }

            $this->assertSame($linhas[$posicao - 1]->after, $linha->before, 'a cadeia before/after é contínua');
        }
    }

    /**
     * §7 do Anexo I: ação sensível registra ator **e** justificativa. O log
     * guarda o saldo anterior e o posterior, e não só o fato.
     */
    public function test_o_log_do_ajuste_guarda_ator_saldo_anterior_posterior_e_motivo(): void
    {
        $admin = $this->createBackendAdmin();
        [$produto, $variante, $local] = $this->catalogoComSaldo(onHand: 20, reserved: 0, minimum: 5);

        $dados = $this->dadosDeEntrada($produto, $variante, $local, 17);
        $dados['type'] = StockMovement::TYPE_ADJUSTMENT;
        $dados['reason'] = 'Inventário de abril';

        $this->actingAs($admin)->post(route('backend.estoque.movimentacao.store'), $dados)->assertRedirect();

        $log = DB::table('audit_logs')->where('action', 'velaro.stock.adjusted')->first();

        $this->assertNotNull($log);
        $this->assertSame($admin->id, (int) $log->actor_id);

        /** @var array<string, mixed> $antes */
        $antes = json_decode((string) $log->before, true);
        /** @var array<string, mixed> $depois */
        $depois = json_decode((string) $log->after, true);

        $this->assertSame(20, $antes['available']);
        $this->assertSame(17, $depois['available']);
        $this->assertSame('Inventário de abril', $depois['reason']);
    }

    /**
     * A variação do cartão "Itens em estoque" compara `on_hand`, e `before`/
     * `after` guardam o **disponível**: uma reserva no mês mexe no disponível
     * sem tirar peça do cofre. Somar o delta dela anunciaria uma queda de
     * estoque que nunca aconteceu.
     */
    public function test_reserva_no_mes_nao_distorce_a_variacao_de_itens_em_estoque(): void
    {
        $admin = $this->createBackendAdmin();
        [$produto, $variante, $local] = $this->catalogoComSaldo(onHand: 20, reserved: 0, minimum: 5);
        $pedido = Order::factory()->create();

        $dados = $this->dadosDeEntrada($produto, $variante, $local, 5);
        $dados['type'] = StockMovement::TYPE_RESERVATION;
        $dados['order_id'] = $pedido->id;

        $this->actingAs($admin)->post(route('backend.estoque.movimentacao.store'), $dados)->assertRedirect();

        $kpis = app(EstoqueService::class)->kpis();

        $this->assertSame(20, $kpis[0]['valor']);
        $this->assertSame(0.0, $kpis[0]['variacao']);
    }

    public function test_movimentacao_sem_o_produto_do_aro_e_recusada(): void
    {
        $admin = $this->createBackendAdmin();
        [$produto, $variante, $local] = $this->catalogoComSaldo();

        $dados = $this->dadosDeEntrada($produto, $variante, $local);
        unset($dados['product_id']);

        $this->actingAs($admin)
            ->from(route('backend.estoque.movimentacao'))
            ->post(route('backend.estoque.movimentacao.store'), $dados)
            ->assertSessionHasErrors('product_id');

        $this->assertSame(0, StockMovement::query()->count());
    }

    public function test_entrada_em_cofre_novo_cria_a_linha_de_saldo_do_aro(): void
    {
        $admin = $this->createBackendAdmin();
        [$produto, $variante] = $this->catalogoComSaldo(onHand: 5, reserved: 0, minimum: 2);
        // UNIQUE(product_variant_id, stock_location_id): o mesmo aro em outro
        // cofre e outra linha de saldo, nao a mesma.
        $outroCofre = StockLocation::factory()->create();

        $this->actingAs($admin)
            ->post(route('backend.estoque.movimentacao.store'), $this->dadosDeEntrada($produto, $variante, $outroCofre, 12))
            ->assertRedirect();

        $this->assertDatabaseHas('stock_items', [
            'product_variant_id' => $variante->id,
            'stock_location_id' => $outroCofre->id,
            'on_hand' => 12,
            'available' => 12,
        ]);
        $this->assertSame(2, StockItem::query()->where('product_variant_id', $variante->id)->count());
    }

    // ─────────────────────────────── APOIO ───────────────────────────────

    /**
     * @return array{0: Product, 1: ProductVariant, 2: StockLocation}
     */
    private function catalogoComSaldo(int $onHand = 40, int $reserved = 4, int $minimum = 10): array
    {
        $produto = Product::factory()->create(['is_active' => true]);
        $variante = ProductVariant::factory()->forProduct($produto)->withRingSize(18)->create(['is_active' => true]);
        $local = StockLocation::factory()->defaultLocation()->create();

        StockItem::factory()->forVariant($variante)->atLocation($local)->create([
            'on_hand' => $onHand,
            'reserved' => $reserved,
            'available' => $onHand - $reserved,
            'minimum' => $minimum,
            'restock_point' => $minimum * 3,
        ]);

        return [$produto, $variante, $local];
    }

    /**
     * @return array<string, mixed>
     */
    private function dadosDeEntrada(Product $produto, ProductVariant $variante, StockLocation $local, int $quantidade = 10): array
    {
        return [
            'type' => StockMovement::TYPE_INBOUND,
            'product_id' => $produto->id,
            'product_variant_id' => $variante->id,
            'stock_location_id' => $local->id,
            'quantity' => $quantidade,
            'reason' => 'Ordem de produção concluída',
            'occurred_at' => now()->format('Y-m-d\TH:i'),
        ];
    }

    private function semPermissao(string $chave): User
    {
        $admin = $this->createBackendAdmin();
        $this->negar($admin, $chave);

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

    private function negar(User $admin, string $chave): void
    {
        $admin->permissionOverrides()->updateOrCreate(
            ['permission_id' => $this->permissaoId($chave)],
            ['is_allowed' => false],
        );
    }

    private function permissaoId(string $chave): int
    {
        /** @var int $id */
        $id = AclPermission::query()->where('key', $chave)->value('id');

        return $id;
    }
}
