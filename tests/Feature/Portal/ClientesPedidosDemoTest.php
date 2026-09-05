<?php

/*
[Modulo: tests/Feature/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Abre as telas 2.3, 2.5 e 2.11 com os dados do VelaroSeeder, que e a demonstracao que o cliente ve.
*/

namespace Tests\Feature\Portal;

use App\Models\Order;
use App\Models\User;
use App\Support\ResellerScope;
use Database\Seeders\VelaroSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Os testes de fábrica montam o dado que cada regra precisa; este monta nenhum.
 *
 * Ele abre as telas com o **seed de demonstração** — o mesmo pedido `ORD012548`
 * do protótipo, com dois itens, gravação, lote em aberto e cliente com
 * consentimento — porque é essa a base que alguém abre para ver o produto. Um
 * caso que só o seed alcança: o pedido do seed tem duas linhas de item, gravação
 * em uma delas e um lote de verdade, combinação que nenhum teste de unidade
 * monta por acidente.
 */
class ClientesPedidosDemoTest extends TestCase
{
    use RefreshDatabase;

    private User $lojista;

    private ResellerScope $escopo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(VelaroSeeder::class);

        $this->lojista = User::query()->where('email', 'lojista@velaro.test')->firstOrFail();
        $this->escopo = ResellerScope::for($this->lojista->reseller);
    }

    public function test_a_carteira_e_a_ficha_abrem_com_o_cliente_do_seed(): void
    {
        $this->actingAs($this->lojista)
            ->get(route('portal.clientes.index'))
            ->assertOk()
            ->assertSee('Maria Silva')
            ->assertSee('123.456.789-00');

        $maria = $this->escopo->customers()->where('name', 'Maria Silva')->firstOrFail();

        $this->actingAs($this->lojista)
            ->get(route('portal.clientes.show', $maria))
            ->assertOk()
            ->assertSee('Datas de relacionamento')
            ->assertSee('Consentimento (LGPD)')
            ->assertSee('Histórico de pedidos');
    }

    public function test_o_pedido_do_prototipo_abre_com_itens_gravacao_e_lote(): void
    {
        $pedido = $this->escopo->orders()->where('public_number', 'ORD012548')->firstOrFail();

        $this->actingAs($this->lojista)
            ->get(route('portal.pedidos.show', $pedido))
            ->assertOk()
            ->assertSee('Pedido #ORD012548')
            ->assertSee('Itens do pedido (2)')
            ->assertSee('Solicitada')
            ->assertSee('LOTE-2026-W24-VEL02412')
            // A conta fecha: subtotal + gravação + frete − desconto = total.
            ->assertSee($this->moeda($pedido->total_amount));
    }

    public function test_o_pedido_que_chegou_na_loja_abre_o_bloco_de_retirada(): void
    {
        $pronto = $this->escopo->orders()
            ->where('operational_status', Order::OPERATIONAL_STATUS_READY_FOR_PICKUP)
            ->firstOrFail();

        $this->actingAs($this->lojista)
            ->get(route('portal.pedidos.show', $pronto))
            ->assertOk()
            ->assertSee('Confirmação de retirada')
            ->assertSee('Como o cliente recebe')
            ->assertSee('Tomazelli Alianças');
    }

    public function test_o_lojista_do_seed_nao_alcanca_a_carteira_do_segundo_lojista(): void
    {
        // O seed tem dois revendedores justamente para isto ter contra o que ser
        // testado com dado real, e não só com fábrica.
        $vizinho = User::query()->where('email', 'lojista2@velaro.test')->firstOrFail();
        $escopoVizinho = ResellerScope::for($vizinho->reseller);

        $clienteDoVizinho = $escopoVizinho->customers()->firstOrFail();
        $pedidoDoVizinho = $escopoVizinho->orders()->firstOrFail();

        $this->actingAs($this->lojista)
            ->get(route('portal.clientes.show', $clienteDoVizinho))
            ->assertNotFound();

        $this->actingAs($this->lojista)
            ->get(route('portal.pedidos.show', $pedidoDoVizinho))
            ->assertNotFound();

        $this->actingAs($this->lojista)
            ->get(route('portal.clientes.index'))
            ->assertOk()
            ->assertDontSee((string) $clienteDoVizinho->name);
    }

    private function moeda(mixed $valor): string
    {
        return 'R$ '.number_format((float) $valor, 2, ',', '.');
    }
}
