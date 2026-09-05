<?php

/*
[Modulo: tests/Feature/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Confere o lojista demo do VelaroSeeder: acesso ao portal, contas fechadas e idempotencia em base limpa.
*/

namespace Tests\Feature\Portal;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderBatch;
use App\Models\OrderItemEngraving;
use App\Models\Product;
use App\Models\Reseller;
use App\Models\Setting;
use App\Models\SupportTicket;
use App\Models\User;
use App\Support\ResellerScope;
use Database\Seeders\VelaroSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * O seed do Ambiente 2. Antes dele `users.reseller_id` era nulo em toda a base e
 * não havia com quem entrar em `/portal`.
 *
 * O teste roda em base vazia — sem o admin do DatabaseSeeder — porque é assim que
 * o clone recém-feito começa, e todas as FKs de operador (`approved_by`,
 * `assignee_id`, `issued_by`, `reconciled_by`) precisam aguentar o nulo.
 */
class PortalSeedTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tabelas conferidas na comparação entre as duas execuções.
     *
     * @var list<string>
     */
    private const TABELAS = [
        'resellers', 'users', 'reseller_stores', 'reseller_price_settings',
        'reseller_price_rules', 'customers', 'customer_consents', 'order_batches',
        'orders', 'order_items', 'order_item_engravings', 'order_status_events',
        'payments', 'invoices', 'invoice_items', 'support_tags', 'support_tickets',
        'support_ticket_tag', 'support_messages', 'support_status_events',
    ];

    public function test_rodar_duas_vezes_nao_cria_nem_altera_nenhuma_linha(): void
    {
        $this->seed(VelaroSeeder::class);
        $primeira = $this->retrato();

        // O relógio anda entre as duas execuções de propósito: qualquer `now()`
        // que tenha sobrado no seed passa a gravar um valor diferente e a
        // comparação de conteúdo reprova. Sem viajar no tempo, duas execuções
        // seguidas cairiam no mesmo segundo e o teste passaria por sorte.
        $this->travel(37)->hours();

        $this->seed(VelaroSeeder::class);
        $segunda = $this->retrato();

        // Contagem igual prova que nada duplicou; o conteúdo igual prova que nem
        // as datas nem o hash da senha andaram entre as execuções.
        $this->assertSame($primeira['contagens'], $segunda['contagens']);
        $this->assertSame($primeira['conteudo'], $segunda['conteudo']);
    }

    public function test_o_lojista_demo_entra_no_portal(): void
    {
        $this->seed(VelaroSeeder::class);

        $lojista = User::query()->where('email', 'lojista@velaro.test')->firstOrFail();

        $this->assertNotNull($lojista->email_verified_at, 'o grupo portal passa por `verified`');
        $this->assertFalse((bool) $lojista->is_blocked);

        $revendedor = $lojista->reseller;

        $this->assertInstanceOf(Reseller::class, $revendedor);
        $this->assertSame(Reseller::STATUS_APPROVED, $revendedor->status);
        $this->assertSame('Tomazelli Alianças', $revendedor->trade_name);
        $this->assertNotEmpty($revendedor->code);
        $this->assertNotEmpty($revendedor->protocol);

        // O middleware `reseller` deixa passar e o dashboard abre com os dados
        // do próprio lojista.
        $this->actingAs($lojista)->get(route('portal.dashboard'))->assertOk();
    }

    /**
     * A senha sai de `config('velaro.seed.reseller_password')`, e nao de `env()`
     * direto: o deploy roda `config:cache`, e a partir dai `env()` fora de arquivo
     * de config devolve null — a senha cairia no fallback publico do repositorio
     * sem nenhum sinal. Este teste fixa o mecanismo, nao so o valor.
     */
    public function test_a_senha_do_seed_vem_da_configuracao(): void
    {
        config(['velaro.seed.reseller_password' => 'segredo-do-ambiente']);

        $this->seed(VelaroSeeder::class);

        $lojista = User::query()->where('email', 'lojista@velaro.test')->firstOrFail();

        $this->assertTrue(password_verify('segredo-do-ambiente', (string) $lojista->password));
    }

    /**
     * Producao nao recebe demonstracao: o bloco do Portal e ignorado, e nenhuma
     * conta com credencial versionada nasce no ambiente real.
     */
    public function test_producao_nao_semeia_a_demonstracao_do_portal(): void
    {
        app()->instance('env', 'production');

        // Chamado direto, e nao por `$this->seed()`: em producao o comando
        // `db:seed` pede confirmacao no console, o que travaria o teste.
        $seeder = new VelaroSeeder;
        $seeder->setContainer(app());
        $seeder->run();

        $this->assertDatabaseMissing('users', ['email' => 'lojista@velaro.test']);
        $this->assertSame(0, Reseller::query()->count());

        // O que vale em qualquer ambiente continua sendo semeado.
        $this->assertGreaterThan(0, Setting::query()->count());
        $this->assertGreaterThan(0, Product::query()->count());
    }

    public function test_o_lojista_demo_tem_o_ciclo_de_pedido_inteiro(): void
    {
        $this->seed(VelaroSeeder::class);

        $escopo = ResellerScope::for($this->tomazelli());

        $estados = $escopo->orders()->pluck('operational_status')->unique()->sort()->values()->all();

        $this->assertSame([
            Order::OPERATIONAL_STATUS_IN_PRODUCTION,
            Order::OPERATIONAL_STATUS_IN_TRANSIT,
            Order::OPERATIONAL_STATUS_PICKED_UP,
            Order::OPERATIONAL_STATUS_READY_FOR_PICKUP,
            Order::OPERATIONAL_STATUS_REGISTERED,
        ], $estados);

        $this->assertSame(8, $escopo->customers()->count());
        $this->assertSame(6, $escopo->orders()->count());
        $this->assertSame(3, $escopo->tickets()->count());
        $this->assertNotNull($escopo->store());

        // Gravação em parte dos pedidos, e não em todos.
        $comGravacao = OrderItemEngraving::query()->where('enabled', true)->count();
        $this->assertGreaterThan(0, $comGravacao);
        $this->assertLessThan(OrderItemEngraving::query()->count(), $comGravacao);
    }

    public function test_as_contas_de_cada_pedido_e_de_cada_lote_fecham(): void
    {
        $this->seed(VelaroSeeder::class);

        foreach (Order::query()->whereNotNull('reseller_id')->with('items')->get() as $pedido) {
            $itens = round((float) $pedido->items->sum('total_price'), 2);

            $this->assertSame($itens, round((float) $pedido->subtotal_amount, 2), "subtotal de {$pedido->public_number}");
            $this->assertSame(
                round((float) $pedido->subtotal_amount + (float) $pedido->engraving_amount + (float) $pedido->shipping_amount - (float) $pedido->discount_amount, 2),
                round((float) $pedido->total_amount, 2),
                "total de {$pedido->public_number}",
            );
        }

        foreach (OrderBatch::query()->get() as $lote) {
            $this->assertSame(
                round((float) $lote->orders()->sum('total_amount'), 2),
                round((float) $lote->total_amount, 2),
                "total do lote {$lote->code}",
            );
        }

        // A nota do lote quitado é rateada pedido a pedido — é o que resolve a
        // coluna NF-e por pedido na tela 2.4.
        $nota = Invoice::query()->firstOrFail();

        $this->assertSame(Invoice::STATUS_AUTHORIZED, $nota->status);
        $this->assertSame(
            round((float) $nota->amount, 2),
            round((float) $nota->items()->sum('amount'), 2),
        );
    }

    public function test_o_segundo_revendedor_tem_base_propria_e_nada_dele_e_do_primeiro(): void
    {
        $this->seed(VelaroSeeder::class);

        $tomazelli = $this->tomazelli();
        $vizinho = Reseller::query()->where('code', 'ALC-0042')->firstOrFail();

        $this->assertNotSame($tomazelli->getKey(), $vizinho->getKey());

        // O vizinho existe em todos os models cobertos pelo escopo — sem isso, o
        // isolamento não teria contra o que ser testado.
        $this->assertGreaterThan(0, $vizinho->customers()->count());
        $this->assertGreaterThan(0, $vizinho->orders()->count());
        $this->assertGreaterThan(0, $vizinho->batches()->count());
        $this->assertGreaterThan(0, $vizinho->tickets()->count());
        $this->assertGreaterThan(0, $vizinho->priceRules()->count());
        $this->assertNotNull($vizinho->store);

        $escopo = ResellerScope::for($tomazelli);

        $this->assertEmpty(array_intersect(
            $escopo->orders()->pluck('id')->all(),
            $vizinho->orders()->pluck('id')->all(),
        ));

        // E o portal de um não alcança o registro do outro.
        $lojista = User::query()->where('email', 'lojista@velaro.test')->firstOrFail();
        $pedidoAlheio = $vizinho->orders()->firstOrFail();
        $chamadoAlheio = $vizinho->tickets()->firstOrFail();

        $this->actingAs($lojista)->get(route('portal.pedidos.show', $pedidoAlheio))->assertNotFound();
        $this->actingAs($lojista)->get(route('portal.suporte.show', $chamadoAlheio))->assertNotFound();
    }

    public function test_o_consentimento_de_marketing_e_revogavel_por_cliente(): void
    {
        $this->seed(VelaroSeeder::class);

        $revogado = Customer::query()
            ->whereHas('consents', fn ($query) => $query->where('granted', false)->whereNotNull('revoked_at'))
            ->first();

        // Data de casamento sem aceite de marketing válido não alimenta campanha
        // (regra 1 da tela 2.3): o seed precisa ter esse caso para a tela poder
        // ser testada contra ele.
        $this->assertInstanceOf(Customer::class, $revogado);
        $this->assertNotNull($revogado->wedding_date);
    }

    public function test_a_conversa_do_chamado_carrega_observacao_interna(): void
    {
        $this->seed(VelaroSeeder::class);

        $chamado = SupportTicket::query()
            ->whereHas('messages', fn ($query) => $query->where('is_internal_note', true))
            ->first();

        // `is_internal_note` nunca é exposto ao revendedor (regra 3 da tela 2.8).
        // O seed semeia uma para a tela ter o que esconder.
        $this->assertInstanceOf(SupportTicket::class, $chamado);
        $this->assertGreaterThan(
            $chamado->messages()->visibleToReseller()->count(),
            $chamado->messages()->count(),
        );
    }

    private function tomazelli(): Reseller
    {
        return Reseller::query()->where('code', 'VEL-02412')->firstOrFail();
    }

    /**
     * @return array{contagens: array<string, int>, conteudo: array<string, string>}
     */
    private function retrato(): array
    {
        $contagens = [];
        $conteudo = [];

        foreach (self::TABELAS as $tabela) {
            $linhas = DB::table($tabela)->orderBy('id')->get()
                ->map(static fn (object $linha): array => (array) $linha)
                ->all();

            $contagens[$tabela] = count($linhas);
            $conteudo[$tabela] = md5((string) json_encode($linhas));
        }

        return ['contagens' => $contagens, 'conteudo' => $conteudo];
    }
}
