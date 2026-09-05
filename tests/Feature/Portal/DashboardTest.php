<?php

/*
[Modulo: tests/Feature/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Cobre a tela 2.1: indicadores, ultimos pedidos, pendencias, checklist e o isolamento entre lojistas.
*/

namespace Tests\Feature\Portal;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Reseller;
use App\Models\ResellerPriceSetting;
use App\Models\ResellerStore;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Portal\PainelLojistaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * O dashboard é a tela em que um vazamento entre revendedores apareceria como um
 * número: se o KPI somasse o pedido do vizinho, ninguém notaria olhando a tela —
 * só o teste. Por isso todo caso aqui monta **dois** lojistas com a mesma forma
 * de dado e afirma o que o primeiro vê e o que ele não pode ver.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private Reseller $tomazelli;

    private Reseller $vizinho;

    private User $lojista;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tomazelli = Reseller::factory()->approved()->create(['trade_name' => 'Tomazelli Alianças']);
        $this->vizinho = Reseller::factory()->approved()->create(['trade_name' => 'Aliança & Cia']);
        $this->lojista = User::factory()->forReseller($this->tomazelli)->create();
    }

    public function test_o_dashboard_abre_com_a_identidade_do_lojista(): void
    {
        $resposta = $this->actingAs($this->lojista)->get(route('portal.dashboard'));

        $resposta->assertOk();
        $resposta->assertSee('Dashboard do Lojista');
        $resposta->assertSee('Tomazelli Alianças');
        $resposta->assertSee('Pedidos em andamento');
        $resposta->assertSee('Clientes cadastrados');
    }

    public function test_os_indicadores_contam_so_o_que_e_do_proprio_lojista(): void
    {
        // Dois em produção, um pronto para retirada e um já retirado (que não
        // conta como "em andamento") — tudo do Tomazelli.
        $this->pedido($this->tomazelli, Order::OPERATIONAL_STATUS_IN_PRODUCTION, Order::PAYMENT_STATUS_PAID);
        $this->pedido($this->tomazelli, Order::OPERATIONAL_STATUS_IN_PRODUCTION, Order::PAYMENT_STATUS_PAID);
        $this->pedido($this->tomazelli, Order::OPERATIONAL_STATUS_READY_FOR_PICKUP, Order::PAYMENT_STATUS_PAID);
        $this->pedido($this->tomazelli, Order::OPERATIONAL_STATUS_PICKED_UP, Order::PAYMENT_STATUS_PAID);
        $this->pedido($this->tomazelli, Order::OPERATIONAL_STATUS_REGISTERED, Order::PAYMENT_STATUS_PENDING);

        Customer::factory()->forReseller($this->tomazelli)->count(3)->create(['user_id' => null]);
        SupportTicket::factory()->count(2)->create([
            'reseller_id' => $this->tomazelli->getKey(),
            'status' => SupportTicket::STATUS_IN_PROGRESS,
        ]);
        SupportTicket::factory()->create([
            'reseller_id' => $this->tomazelli->getKey(),
            'status' => SupportTicket::STATUS_RESOLVED,
        ]);

        // O vizinho tem uma base bem maior. Nada disso pode entrar na conta.
        $this->pedido($this->vizinho, Order::OPERATIONAL_STATUS_IN_PRODUCTION, Order::PAYMENT_STATUS_PENDING);
        $this->pedido($this->vizinho, Order::OPERATIONAL_STATUS_READY_FOR_PICKUP, Order::PAYMENT_STATUS_OVERDUE);
        Customer::factory()->forReseller($this->vizinho)->count(9)->create(['user_id' => null]);
        SupportTicket::factory()->count(4)->create([
            'reseller_id' => $this->vizinho->getKey(),
            'status' => SupportTicket::STATUS_OPEN,
        ]);

        $indicadores = $this->indicadores();

        $this->assertSame(4, $indicadores['Pedidos em andamento'], 'em andamento = tudo menos o retirado');
        $this->assertSame(2, $indicadores['Em produção']);
        $this->assertSame(1, $indicadores['Prontos para retirada']);
        $this->assertSame(1, $indicadores['Aguardando pagamento à Velaro']);
        $this->assertSame(2, $indicadores['Chamados abertos'], 'resolvido não é chamado aberto');
        $this->assertSame(3, $indicadores['Clientes cadastrados']);
    }

    public function test_a_tabela_de_ultimos_pedidos_nao_mostra_pedido_de_outro_lojista(): void
    {
        $cliente = Customer::factory()->forReseller($this->tomazelli)->create([
            'user_id' => null,
            'name' => 'Maria Silva',
        ]);

        $meu = $this->pedido($this->tomazelli, Order::OPERATIONAL_STATUS_IN_PRODUCTION, Order::PAYMENT_STATUS_PAID);
        $meu->forceFill(['customer_id' => $cliente->getKey()])->save();

        $clienteVizinho = Customer::factory()->forReseller($this->vizinho)->create([
            'user_id' => null,
            'name' => 'Cliente do Concorrente',
        ]);
        $doVizinho = $this->pedido($this->vizinho, Order::OPERATIONAL_STATUS_IN_PRODUCTION, Order::PAYMENT_STATUS_PAID);
        $doVizinho->forceFill(['customer_id' => $clienteVizinho->getKey()])->save();

        $resposta = $this->actingAs($this->lojista)->get(route('portal.dashboard'));

        $resposta->assertOk();
        $resposta->assertSee((string) $meu->public_number);
        $resposta->assertSee('Maria Silva');

        // Nem o número do pedido nem o nome do cliente final do concorrente.
        $resposta->assertDontSee((string) $doVizinho->public_number);
        $resposta->assertDontSee('Cliente do Concorrente');
    }

    public function test_os_dois_status_do_pedido_aparecem_com_os_rotulos_de_lang(): void
    {
        $this->pedido($this->tomazelli, Order::OPERATIONAL_STATUS_IN_TRANSIT, Order::PAYMENT_STATUS_AWAITING_CLEARANCE);

        $resposta = $this->actingAs($this->lojista)->get(route('portal.dashboard'));

        $resposta->assertOk();
        // O rótulo continua saindo de `lang/`, mas do arquivo pt-BR: o portal fixa
        // o idioma da interface em StatusDoPedido, porque `APP_LOCALE` é `en` no
        // scaffold e a tela inteira é escrita em português — ver a constante
        // IDIOMA em App\Services\Portal\Concerns\FormataDados.
        $resposta->assertSee(trans('order.operational_status.in_transit', [], 'pt_BR'));
        $resposta->assertSee(trans('order.payment_status.awaiting_clearance', [], 'pt_BR'));
        $resposta->assertSee('são independentes', false);
    }

    public function test_sem_pedido_a_tabela_mostra_o_vazio_em_vez_de_linha_falsa(): void
    {
        $resposta = $this->actingAs($this->lojista)->get(route('portal.dashboard'));

        $resposta->assertOk();
        $resposta->assertSee('Nenhum pedido ainda.');
    }

    public function test_o_checklist_reflete_o_estado_real_da_loja(): void
    {
        // Loja publicada sem logo e margem definida: dois de três.
        ResellerStore::factory()->published()->create([
            'reseller_id' => $this->tomazelli->getKey(),
            'logo_path' => null,
        ]);
        ResellerPriceSetting::factory()->create([
            'reseller_id' => $this->tomazelli->getKey(),
            'margin_global' => 62.5,
        ]);

        $resposta = $this->actingAs($this->lojista)->get(route('portal.dashboard'));

        $resposta->assertOk();
        $resposta->assertSee('2 de 3');
        // A logo que falta vira pendência acionável, não só um item apagado.
        $resposta->assertSee('Cadastrar logo da loja');
        $resposta->assertDontSee('Definir markup padrão');
    }

    public function test_loja_completa_zera_as_pendencias(): void
    {
        ResellerStore::factory()->published()->create([
            'reseller_id' => $this->tomazelli->getKey(),
            'logo_path' => 'lojas/tomazelli/logo.svg',
        ]);
        ResellerPriceSetting::factory()->create([
            'reseller_id' => $this->tomazelli->getKey(),
            'margin_global' => 62.5,
        ]);

        $resposta = $this->actingAs($this->lojista)->get(route('portal.dashboard'));

        $resposta->assertOk();
        $resposta->assertSee('3 de 3');
        $resposta->assertSee('Nada pendente.');
    }

    public function test_chamado_esperando_o_lojista_vira_pendencia_e_o_do_vizinho_nao(): void
    {
        $meu = SupportTicket::factory()->create([
            'reseller_id' => $this->tomazelli->getKey(),
            'status' => SupportTicket::STATUS_AWAITING_CUSTOMER,
        ]);

        $doVizinho = SupportTicket::factory()->create([
            'reseller_id' => $this->vizinho->getKey(),
            'status' => SupportTicket::STATUS_AWAITING_CUSTOMER,
        ]);

        $resposta = $this->actingAs($this->lojista)->get(route('portal.dashboard'));

        $resposta->assertOk();
        $resposta->assertSee('Responder chamado '.$meu->code);
        $resposta->assertDontSee((string) $doVizinho->code);
    }

    public function test_o_vizinho_ve_a_propria_base_e_nao_a_do_tomazelli(): void
    {
        // O espelho do primeiro caso: a mesma tela, o outro dono. Sem isto,
        // "não vejo o dado do outro" poderia ser só uma tela vazia.
        $vizinhoLogado = User::factory()->forReseller($this->vizinho)->create();

        $meu = $this->pedido($this->tomazelli, Order::OPERATIONAL_STATUS_IN_PRODUCTION, Order::PAYMENT_STATUS_PAID);
        $dele = $this->pedido($this->vizinho, Order::OPERATIONAL_STATUS_IN_PRODUCTION, Order::PAYMENT_STATUS_PAID);

        $resposta = $this->actingAs($vizinhoLogado)->get(route('portal.dashboard'));

        $resposta->assertOk();
        $resposta->assertSee((string) $dele->public_number);
        $resposta->assertDontSee((string) $meu->public_number);
    }

    private function pedido(Reseller $revendedor, string $operacional, string $pagamento): Order
    {
        return Order::factory()->forReseller($revendedor)->create([
            'user_id' => null,
            'customer_id' => null,
            'operational_status' => $operacional,
            'payment_status' => $pagamento,
        ]);
    }

    /**
     * Lê os seis KPIs direto do service, pelo rótulo — a view só os desenha, e
     * comparar HTML por número daria falso positivo com qualquer outro "3" da
     * página.
     *
     * @return array<string, int>
     */
    private function indicadores(): array
    {
        $this->actingAs($this->lojista);

        $painel = app(PainelLojistaService::class)->montar();

        $valores = [];

        /** @var list<array{rotulo: string, valor: int}> $indicadores */
        $indicadores = $painel['indicadores'];

        foreach ($indicadores as $indicador) {
            $valores[$indicador['rotulo']] = $indicador['valor'];
        }

        return $valores;
    }
}
