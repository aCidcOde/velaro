<?php

/*
[Modulo: tests/Feature/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Cobre a tela de notas do Portal: filtro, rateio por pedido, download e o isolamento das NF-e entre lojistas.
*/

namespace Tests\Feature\Portal;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Reseller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * `invoices` **nao tem `reseller_id`**: a nota pende do lote (decisao 1.3), e e o
 * lote que tem dono. Isso torna esta a tela mais facil de vazar do modulo inteiro
 * — basta uma consulta que esqueca a subconsulta dos lotes. Por isso o isolamento
 * aqui e perseguido pelos dois lados: a lista e a busca direta pelo numero da nota
 * do vizinho.
 */
class NotasFiscaisTest extends TestCase
{
    use RefreshDatabase;
    use SemeiaFinanceiroDoLojista;

    private Reseller $tomazelli;

    private User $lojista;

    /** @var array<string, mixed> */
    private array $meus = [];

    /** @var array<string, mixed> */
    private array $doVizinho = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixarORelogio();

        $this->tomazelli = Reseller::factory()->approved()->create([
            'trade_name' => 'Tomazelli Alianças',
            'legal_name' => 'Tomazelli Alianças Ltda',
            'cnpj' => '12.345.678/0001-90',
            'state_registration' => '123.456.789.112',
            'street' => 'Rua das Alianças',
            'street_number' => '123',
            'district' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
        ]);

        $vizinho = Reseller::factory()->approved()->create(['trade_name' => 'Aliança & Cia']);

        $this->lojista = User::factory()->forReseller($this->tomazelli)->create();

        $this->meus = $this->semearFinanceiro($this->tomazelli, 'VEL02412');
        $this->doVizinho = $this->semearFinanceiro($vizinho, 'ALC0042');
    }

    /**
     * A celula da tabela, e nao o numero solto.
     *
     * O KPI "Última emissão" tambem imprime o numero da ultima nota, e ele nao
     * segue o filtro — e um indicador da conta, nao da consulta. Sem ancorar na
     * marcacao da linha, todo `assertDontSee` de filtro passaria a esbarrar no KPI.
     */
    private function linhaDaNota(string $numero): string
    {
        return '<strong style="color:var(--ink)">NF-e '.$numero.'</strong>';
    }

    private function assertMostraNaTabela(TestResponse $resposta, string $numero): void
    {
        $resposta->assertSee($this->linhaDaNota($numero), false);
    }

    private function assertNaoMostraNaTabela(TestResponse $resposta, string $numero): void
    {
        $resposta->assertDontSee($this->linhaDaNota($numero), false);
    }

    public function test_a_tela_lista_as_notas_emitidas_contra_a_loja(): void
    {
        $resposta = $this->actingAs($this->lojista)->get(route('portal.financeiro.notas'));

        $resposta->assertOk();
        $resposta->assertSee('Notas fiscais emitidas');
        $resposta->assertSee('Tomazelli Alianças', false);
        $resposta->assertSee('NF-e 000.024.156');
        $resposta->assertSee('20/05/2026');
        $resposta->assertSee('Maio/2026');
        $resposta->assertSee('R$ 1.500,00', false);
        $resposta->assertSee('Autorizada');
        // O lote e o pedido que a nota cobre — o rateio de `invoice_items`.
        $resposta->assertSee('23/2026');
        $resposta->assertSee('ORD-VEL02412-C');
    }

    public function test_os_kpis_do_topo_contam_o_mes_corrente_e_a_ultima_emissao(): void
    {
        $resposta = $this->actingAs($this->lojista)->get(route('portal.financeiro.notas'));

        $resposta->assertOk();
        $resposta->assertSee('Notas emitidas');
        $resposta->assertSee('Valor total faturado');
        $resposta->assertSee('Última emissão', false);
        $resposta->assertSee('Notas canceladas');
        $resposta->assertSee('NF-e 000.024.156');
        $resposta->assertSee('R$ 1.500,00', false);
    }

    public function test_o_resumo_por_competencia_e_os_dados_fiscais_do_destinatario(): void
    {
        $resposta = $this->actingAs($this->lojista)->get(route('portal.financeiro.notas'));

        $resposta->assertOk();
        $resposta->assertSee('Resumo por competência', false);
        $resposta->assertSee('1 nota · lote 23/2026', false);

        // O destinatario e o cadastro do proprio revendedor — a tela nao guarda
        // uma segunda copia dos dados fiscais.
        $resposta->assertSee('Dados do destinatário', false);
        $resposta->assertSee('Tomazelli Alianças Ltda', false);
        $resposta->assertSee('12.345.678/0001-90');
        $resposta->assertSee('123.456.789.112');
        $resposta->assertSee('Rua das Alianças 123 - Centro - São Paulo / SP', false);
    }

    public function test_a_busca_encontra_por_numero_da_nota_por_lote_e_por_pedido(): void
    {
        foreach (['000.024.156', 'LOTE-2026-W23-VEL02412', 'ORD-VEL02412-C'] as $termo) {
            $resposta = $this->actingAs($this->lojista)
                ->get(route('portal.financeiro.notas', ['q' => $termo]));

            $resposta->assertOk();
            $resposta->assertSee('NF-e 000.024.156', false, "a busca por {$termo} nao encontrou a nota");
        }
    }

    public function test_a_busca_que_nao_casa_devolve_a_lista_vazia_e_nao_um_erro(): void
    {
        $resposta = $this->actingAs($this->lojista)
            ->get(route('portal.financeiro.notas', ['q' => 'inexistente-000']));

        $resposta->assertOk();
        $resposta->assertSee('Nenhuma nota fiscal neste recorte');
        $this->assertNaoMostraNaTabela($resposta, '000.024.156');
    }

    public function test_a_aba_de_canceladas_separa_a_nota_cancelada_e_troca_a_acao(): void
    {
        $cancelada = Invoice::factory()->create([
            'batch_id' => $this->meus['lote_pago']->getKey(),
            'series' => '1',
            'number' => '000.023.788',
            'amount' => 398.00,
            'status' => Invoice::STATUS_CANCELED,
            'issued_at' => '2026-05-12 10:00:00',
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $cancelada->getKey(),
            'order_id' => $this->meus['pedido_c']->getKey(),
            'amount' => 398.00,
        ]);

        $todas = $this->actingAs($this->lojista)->get(route('portal.financeiro.notas'));
        $todas->assertOk();
        $this->assertMostraNaTabela($todas, '000.024.156');
        $this->assertMostraNaTabela($todas, '000.023.788');

        $soCanceladas = $this->actingAs($this->lojista)
            ->get(route('portal.financeiro.notas', ['aba' => Invoice::STATUS_CANCELED]));

        $soCanceladas->assertOk();
        $this->assertMostraNaTabela($soCanceladas, '000.023.788');
        $soCanceladas->assertSee('Cancelada');
        // Nota cancelada nao se baixa: a acao vira "ver motivo".
        $soCanceladas->assertSee('Ver motivo');
        $this->assertNaoMostraNaTabela($soCanceladas, '000.024.156');

        $soAutorizadas = $this->actingAs($this->lojista)
            ->get(route('portal.financeiro.notas', ['aba' => Invoice::STATUS_AUTHORIZED]));

        $soAutorizadas->assertOk();
        $this->assertMostraNaTabela($soAutorizadas, '000.024.156');
        $this->assertNaoMostraNaTabela($soAutorizadas, '000.023.788');
    }

    public function test_o_filtro_de_competencia_recorta_o_mes(): void
    {
        $deAbril = Invoice::factory()->create([
            'batch_id' => $this->meus['lote_pago']->getKey(),
            'series' => '1',
            'number' => '000.023.902',
            'amount' => 1948.00,
            'status' => Invoice::STATUS_AUTHORIZED,
            'issued_at' => '2026-04-29 10:00:00',
        ]);

        $maio = $this->actingAs($this->lojista)
            ->get(route('portal.financeiro.notas', ['competencia' => '2026-05', 'periodo' => '0']));

        $maio->assertOk();
        $this->assertMostraNaTabela($maio, '000.024.156');
        $this->assertNaoMostraNaTabela($maio, (string) $deAbril->number);

        $abril = $this->actingAs($this->lojista)
            ->get(route('portal.financeiro.notas', ['competencia' => '2026-04', 'periodo' => '0']));

        $abril->assertOk();
        $this->assertMostraNaTabela($abril, '000.023.902');
        $this->assertNaoMostraNaTabela($abril, '000.024.156');
    }

    public function test_o_filtro_de_periodo_deixa_de_fora_a_nota_antiga(): void
    {
        Invoice::factory()->create([
            'batch_id' => $this->meus['lote_pago']->getKey(),
            'series' => '1',
            'number' => '000.011.111',
            'status' => Invoice::STATUS_AUTHORIZED,
            'issued_at' => '2025-05-20 10:00:00',
        ]);

        $recentes = $this->actingAs($this->lojista)->get(route('portal.financeiro.notas'));
        $recentes->assertOk();
        $this->assertNaoMostraNaTabela($recentes, '000.011.111');

        $tudo = $this->actingAs($this->lojista)
            ->get(route('portal.financeiro.notas', ['periodo' => '0']));
        $tudo->assertOk();
        $this->assertMostraNaTabela($tudo, '000.011.111');
    }

    public function test_filtro_invalido_devolve_a_lista_em_vez_de_recusar_a_pagina(): void
    {
        // A tela e um relatorio alcancado por link salvo: valor torto vira ausencia
        // de filtro, nao 422.
        $resposta = $this->actingAs($this->lojista)->get(route('portal.financeiro.notas', [
            'aba' => 'inventada',
            'periodo' => 'sempre',
            'competencia' => '2026-13',
            'por_pagina' => 999,
            'page' => 'abc',
        ]));

        $resposta->assertOk();
        $this->assertMostraNaTabela($resposta, '000.024.156');
    }

    public function test_o_download_aparece_quando_o_documento_esta_em_disco_publico(): void
    {
        config()->set('velaro-financeiro.notas.disco', 'public');

        $resposta = $this->actingAs($this->lojista)->get(route('portal.financeiro.notas'));

        $resposta->assertOk();
        $resposta->assertSee('Baixar NF');
        $resposta->assertSee('Baixar XML');
        $resposta->assertSee('/storage/notas/VEL02412.pdf', false);
    }

    public function test_em_disco_privado_a_acao_some_em_vez_de_prometer_um_arquivo(): void
    {
        // Nao ha rota de download no Portal: sem URL publica nao ha o que oferecer,
        // e um botao que leva a 404 seria pior que a acao indisponivel.
        config()->set('velaro-financeiro.notas.disco', 'local');

        $resposta = $this->actingAs($this->lojista)->get(route('portal.financeiro.notas'));

        $resposta->assertOk();
        $resposta->assertDontSee('/storage/notas/VEL02412.pdf', false);
        $resposta->assertSee('ainda não está publicado para download', false);
    }

    public function test_a_nota_de_outro_lojista_nao_aparece_nem_pela_busca_direta(): void
    {
        $doVizinho = $this->doVizinho['nota'];

        $lista = $this->actingAs($this->lojista)->get(route('portal.financeiro.notas', ['periodo' => '0']));
        $lista->assertOk();
        $lista->assertDontSee('NF-e 000.099.001');
        $lista->assertDontSee('ORD-ALC0042-C');
        $lista->assertDontSee('R$ 5.555,00', false);

        // Busca pelo numero exato da nota do concorrente: o numero da NF-e e
        // sequencial, entao acertar o do vizinho e trivial. A resposta e a lista
        // vazia, nunca a nota.
        $busca = $this->actingAs($this->lojista)
            ->get(route('portal.financeiro.notas', ['q' => (string) $doVizinho->number, 'periodo' => '0']));

        $busca->assertOk();
        $busca->assertSee('Nenhuma nota fiscal neste recorte');
        $busca->assertDontSee('NF-e 000.099.001');

        // E pelo pedido do vizinho, que chegaria pelo rateio se o escopo falhasse.
        $porPedido = $this->actingAs($this->lojista)
            ->get(route('portal.financeiro.notas', ['q' => 'ORD-ALC0042-C', 'periodo' => '0']));

        $porPedido->assertOk();
        $porPedido->assertSee('Nenhuma nota fiscal neste recorte');
    }

    public function test_os_contadores_das_abas_nao_somam_nota_de_outro_lojista(): void
    {
        $resposta = $this->actingAs($this->lojista)
            ->get(route('portal.financeiro.notas', ['periodo' => '0']));

        $resposta->assertOk();
        // Uma nota minha; a do vizinho existe na base e nao entra na conta.
        $resposta->assertSee('Todas as notas (1)');
        $resposta->assertSee('Canceladas (0)');
    }
}
