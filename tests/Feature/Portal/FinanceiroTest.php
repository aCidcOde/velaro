<?php

/*
[Modulo: tests/Feature/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Cobre a tela 2.4: KPIs, abas, custo Velaro por pedido, NF-e do lote e o isolamento entre lojistas.
*/

namespace Tests\Feature\Portal;

use App\Http\Requests\Portal\FinanceiroFiltroRequest;
use App\Models\Reseller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * O financeiro e a tela onde o **custo B2B aparece de propósito**: e aqui que o
 * lojista ve quanto paga a Velaro. O que nao pode acontecer e o numero de um
 * lojista cruzar para a tela de outro — e e isso que a metade final deste arquivo
 * persegue, montando dois revendedores com a mesma forma de dado e conferindo que
 * o segundo nunca aparece para o primeiro.
 */
class FinanceiroTest extends TestCase
{
    use RefreshDatabase;
    use SemeiaFinanceiroDoLojista;

    private Reseller $tomazelli;

    private Reseller $vizinho;

    private User $lojista;

    /** @var array<string, mixed> */
    private array $meus = [];

    /** @var array<string, mixed> */
    private array $doVizinho = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixarORelogio();

        $this->tomazelli = Reseller::factory()->approved()->create(['trade_name' => 'Tomazelli Alianças']);
        $this->vizinho = Reseller::factory()->approved()->create(['trade_name' => 'Aliança & Cia']);

        $this->lojista = User::factory()->forReseller($this->tomazelli)->create();

        $this->meus = $this->semearFinanceiro($this->tomazelli, 'VEL02412');
        $this->doVizinho = $this->semearFinanceiro($this->vizinho, 'ALC0042');
    }

    public function test_a_tela_abre_com_os_kpis_e_o_alerta_do_lote_em_aberto(): void
    {
        $resposta = $this->actingAs($this->lojista)->get(route('portal.financeiro.index'));

        $resposta->assertOk();
        $resposta->assertSee('Financeiro');
        $resposta->assertSee('Tomazelli Alianças', false);

        // Alerta do lote em cobranca: dia do vencimento + hora de corte.
        $resposta->assertSee('Lote atual vence em 28/05/2026 às 18h', false);

        // Os cinco KPIs da secao 5 da tela 2.4.
        $resposta->assertSee('Total em aberto');
        $resposta->assertSee('Pedidos no lote atual');
        $resposta->assertSee('Próximo vencimento', false);
        $resposta->assertSee('Notas fiscais emitidas');
        $resposta->assertSee('Pagamentos confirmados');

        // Em aberto = so o lote nao quitado; o quitado nao entra na conta.
        $resposta->assertSee('R$ 2.400,00', false);
        $resposta->assertSee('2 pedidos');
        $resposta->assertSee('28/05/2026');
        // Pagamento confirmado no mes corrente.
        $resposta->assertSee('R$ 1.500,00', false);
    }

    public function test_a_aba_padrao_lista_os_pedidos_do_lote_em_cobranca_com_o_custo_velaro(): void
    {
        $resposta = $this->actingAs($this->lojista)->get(route('portal.financeiro.index'));

        $resposta->assertOk();
        $resposta->assertSee('ORD-VEL02412-A');
        $resposta->assertSee('ORD-VEL02412-B');
        // `products.price` e custo B2B e no portal ELE APARECE: e a coluna
        // "Valor custo Velaro".
        $resposta->assertSee('R$ 1.400,00', false);
        $resposta->assertSee('R$ 1.000,00', false);
        $resposta->assertSee('Aguardando pagamento');
        $resposta->assertSee('24/2026');
        $resposta->assertSee('Mostrando 1 a 2 de 2 pedidos do lote 24/2026');

        // O pedido do lote quitado nao entra nesta aba.
        $resposta->assertDontSee('ORD-VEL02412-C');
    }

    public function test_a_aba_de_todos_os_pedidos_traz_tambem_os_do_lote_quitado(): void
    {
        $resposta = $this->actingAs($this->lojista)
            ->get(route('portal.financeiro.index', ['aba' => FinanceiroFiltroRequest::ABA_TODOS]));

        $resposta->assertOk();
        $resposta->assertSee('ORD-VEL02412-A');
        $resposta->assertSee('ORD-VEL02412-C');
        $resposta->assertSee('Pago');
    }

    public function test_a_aba_de_lotes_lista_os_lotes_do_lojista_com_status_e_nota(): void
    {
        $resposta = $this->actingAs($this->lojista)
            ->get(route('portal.financeiro.index', ['aba' => FinanceiroFiltroRequest::ABA_LOTES]));

        $resposta->assertOk();
        $resposta->assertSee('24/2026');
        $resposta->assertSee('23/2026');
        $resposta->assertSee('LOTE-2026-W24-VEL02412');
        $resposta->assertSee('Em aberto');
        $resposta->assertSee('Pago');
        // O lote quitado carrega a NF-e; o em aberto ainda nao.
        $resposta->assertSee('NF-e 000.024.156');
    }

    public function test_a_coluna_nf_e_so_aparece_no_pedido_que_o_rateio_da_nota_cobre(): void
    {
        $resposta = $this->actingAs($this->lojista)
            ->get(route('portal.financeiro.index', ['aba' => FinanceiroFiltroRequest::ABA_TODOS]));

        $resposta->assertOk();

        // Decisao 1.3: a nota e do lote e `invoice_items` diz qual pedido ela
        // cobre. O pedido do lote quitado baixa; os do lote em aberto, nao.
        $resposta->assertSee('Baixar NF');
        $resposta->assertSeeInOrder(['ORD-VEL02412-C', 'Baixar NF'], false);
    }

    public function test_o_cartao_de_notas_mostra_as_ultimas_emitidas_e_o_link_para_a_lista(): void
    {
        $resposta = $this->actingAs($this->lojista)->get(route('portal.financeiro.index'));

        $resposta->assertOk();
        $resposta->assertSee('NF-e 000.024.156');
        $resposta->assertSee('Maio/2026');
        $resposta->assertSee('Autorizada');
        $resposta->assertSee('Ver todas as notas fiscais emitidas →', false);
        // O link da nota leva a busca com o historico inteiro aberto: a tela de
        // notas comeca nos ultimos 90 dias, e uma nota antiga se perderia.
        // Sem `false`: o `&` da query string sai escapado no HTML, e o assert
        // precisa comparar com a mesma forma que o Blade imprimiu.
        $resposta->assertSee(route('portal.financeiro.notas', ['q' => '000.024.156', 'periodo' => '0']));
    }

    public function test_o_drawer_de_pagamento_abre_no_lote_em_cobranca(): void
    {
        $resposta = $this->actingAs($this->lojista)->get(route('portal.financeiro.index'));

        $resposta->assertOk();
        $resposta->assertSee('Pagamento à Velaro', false);
        $resposta->assertSee('Lote semanal 24/2026');
        $resposta->assertSee('15/05/2026 a 21/05/2026');
        $resposta->assertSee('Data limite para pagamento');
        $resposta->assertSee('Realizar pagamento à Velaro', false);
        $resposta->assertSee(route('portal.financeiro.pagamento', $this->meus['lote_aberto']), false);

        // Os tres meios B2B habilitados (regra 2), com o da cobranca ja emitida
        // marcado — o mesmo que a tela de pagamento vai abrir.
        $resposta->assertSee('PIX');
        $resposta->assertSee('Boleto bancário', false);
        $resposta->assertSee('Transferência bancária', false);
        $resposta->assertSee('payopt is-on', false);
        $resposta->assertSeeInOrder(['payopt is-on', 'Boleto bancário'], false);
    }

    public function test_lojista_sem_lote_em_aberto_ve_o_estado_vazio_em_vez_de_um_lote_qualquer(): void
    {
        $emDia = Reseller::factory()->approved()->create();
        $usuario = User::factory()->forReseller($emDia)->create();

        $resposta = $this->actingAs($usuario)->get(route('portal.financeiro.index'));

        $resposta->assertOk();
        $resposta->assertSee('Nenhum lote em aberto');
        $resposta->assertSee('R$ 0,00', false);
        $resposta->assertSee('Nenhum pedido neste recorte');
    }

    public function test_aba_inventada_cai_no_padrao_em_vez_de_derrubar_a_tela(): void
    {
        // Link velho ou aba inexistente devolve a tela, nao um 422: e um relatorio
        // que o lojista alcanca por favorito.
        $resposta = $this->actingAs($this->lojista)
            ->get(route('portal.financeiro.index', ['aba' => 'inventada', 'page' => 'abc']));

        $resposta->assertOk();
        $resposta->assertSee('Mostrando 1 a 2 de 2 pedidos do lote 24/2026');
    }

    public function test_pagina_absurda_e_podada_e_nao_vira_offset_gigante(): void
    {
        $resposta = $this->actingAs($this->lojista)
            ->get(route('portal.financeiro.index', ['page' => 999999999]));

        $resposta->assertOk();
    }

    public function test_a_tela_nunca_mostra_lote_pedido_ou_nota_de_outro_lojista(): void
    {
        foreach ([null, FinanceiroFiltroRequest::ABA_TODOS, FinanceiroFiltroRequest::ABA_LOTES] as $aba) {
            $resposta = $this->actingAs($this->lojista)
                ->get(route('portal.financeiro.index', $aba === null ? [] : ['aba' => $aba]));

            $resposta->assertOk();

            $resposta->assertDontSee('ORD-ALC0042-A');
            $resposta->assertDontSee('ORD-ALC0042-C');
            $resposta->assertDontSee('LOTE-2026-W24-ALC0042');
            $resposta->assertDontSee('NF-e 000.099.001');
            // O total do vizinho e um numero que so existe na base dele.
            $resposta->assertDontSee('R$ 7.777,00', false);
        }
    }

    public function test_o_kpi_de_total_em_aberto_soma_so_os_lotes_do_proprio_lojista(): void
    {
        // O vizinho tem lote em aberto de R$ 7.777,00; se o escopo vazasse, o KPI
        // deixaria de bater com o lote listado logo abaixo dele.
        $resposta = $this->actingAs($this->lojista)->get(route('portal.financeiro.index'));

        $resposta->assertOk();
        $resposta->assertSee('R$ 2.400,00', false);
        $resposta->assertDontSee('R$ 10.177,00', false);
    }

    public function test_quem_nao_e_revendedor_aprovado_nao_entra_no_financeiro(): void
    {
        // 403 aqui e a resposta certa: a negativa e sobre o ambiente inteiro, nao
        // sobre a existencia de um registro (ver a nota de ResellerScope).
        // Visitante sem sessao volta para o login (o `auth`, dois degraus antes).
        $this->get(route('portal.financeiro.index'))->assertRedirect(route('login'));

        $pendente = Reseller::factory()->pending()->create();
        $semAprovacao = User::factory()->forReseller($pendente)->create();

        $this->actingAs($semAprovacao)->get(route('portal.financeiro.index'))->assertForbidden();
    }
}
