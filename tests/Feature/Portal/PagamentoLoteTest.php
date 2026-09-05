<?php

/*
[Modulo: tests/Feature/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Cobre o pagamento do lote: conferencia, meios B2B, payload Pix e o 404 do lote de outro lojista.
*/

namespace Tests\Feature\Portal;

use App\Models\Payment;
use App\Models\Reseller;
use App\Models\User;
use App\Support\PixBrCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A tela **exibe** a cobranca; ela nao a processa. Os testes seguem essa
 * fronteira: conferem que os dados que ja existem aparecem, que o que nao esta
 * configurado vira aviso de pendencia em vez de numero de fachada, e que o lote de
 * outro lojista responde exatamente como um lote inexistente.
 */
class PagamentoLoteTest extends TestCase
{
    use RefreshDatabase;
    use SemeiaFinanceiroDoLojista;

    /** Chave Pix de teste — nunca ha padrao para chave no `config`. */
    private const CHAVE_PIX = 'a1b2c3d4-5e6f-7890-abcd-ef1234567890';

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

        $this->tomazelli = Reseller::factory()->approved()->create([
            'trade_name' => 'Tomazelli Alianças',
            'legal_name' => 'Tomazelli Alianças Ltda',
            'cnpj' => '12.345.678/0001-90',
            'code' => 'VEL-02412',
        ]);
        $this->vizinho = Reseller::factory()->approved()->create(['trade_name' => 'Aliança & Cia']);

        $this->lojista = User::factory()->forReseller($this->tomazelli)->create();

        $this->meus = $this->semearFinanceiro($this->tomazelli, 'VEL02412');
        $this->doVizinho = $this->semearFinanceiro($this->vizinho, 'ALC0042');
    }

    public function test_a_tela_confere_o_lote_e_lista_os_pedidos_que_o_compoem(): void
    {
        $resposta = $this->actingAs($this->lojista)
            ->get(route('portal.financeiro.pagamento', $this->meus['lote_aberto']));

        $resposta->assertOk();
        $resposta->assertSee('Pagamento do lote à Velaro', false);
        $resposta->assertSee('Lote semanal 24/2026');
        $resposta->assertSee('15/05/2026 a 21/05/2026');
        $resposta->assertSee('Este lote vence em 28/05/2026 às 18h', false);

        // ① Confira o lote: a conta fecha porque os pedidos nao tem desconto.
        $resposta->assertSee('Subtotal (custos Velaro)');
        $resposta->assertSee('Acréscimos por atraso', false);
        $resposta->assertSee('R$ 2.400,00', false);

        // ② Pedidos incluidos no lote.
        $resposta->assertSee('Pedidos incluídos no lote (2)', false);
        $resposta->assertSee('ORD-VEL02412-A');
        $resposta->assertSee('ORD-VEL02412-B');
        $resposta->assertSee('R$ 1.400,00', false);
        $resposta->assertSee('Aguardando compensação', false);
        // Pedido de outro lote nao entra na fatura deste.
        $resposta->assertDontSee('ORD-VEL02412-C');

        // ③ Os tres meios B2B habilitados.
        $resposta->assertSee('Escolha a forma de pagamento');
        $resposta->assertSee('PIX');
        $resposta->assertSee('Boleto bancário', false);
        $resposta->assertSee('Transferência bancária', false);
    }

    public function test_a_esteira_do_topo_reflete_o_estado_do_lote(): void
    {
        $emAberto = $this->actingAs($this->lojista)
            ->get(route('portal.financeiro.pagamento', $this->meus['lote_aberto']));

        $emAberto->assertOk();
        $emAberto->assertSee('Lote fechado');
        $emAberto->assertSee('Você está aqui', false);
        $emAberto->assertSee('step step--now', false);

        $quitado = $this->actingAs($this->lojista)
            ->get(route('portal.financeiro.pagamento', $this->meus['lote_pago']));

        $quitado->assertOk();
        $quitado->assertSee('Este lote está quitado', false);
        $quitado->assertSee('Pedidos liberados');
        $quitado->assertDontSee('Você está aqui', false);
    }

    public function test_o_pix_mostra_um_payload_valido_quando_a_chave_esta_configurada(): void
    {
        config()->set('velaro-financeiro.beneficiario.pix_chave', self::CHAVE_PIX);

        $resposta = $this->actingAs($this->lojista)->get(route('portal.financeiro.pagamento', [
            $this->meus['lote_aberto'],
            'metodo' => Payment::METHOD_PIX,
        ]));

        $resposta->assertOk();
        $resposta->assertSee('Pix copia e cola');
        $resposta->assertSee('br.gov.bcb.pix', false);
        $resposta->assertSee(self::CHAVE_PIX, false);
        // O identificador do BR Code e o codigo do proprio lote.
        $resposta->assertSee('LOTE-2026-W24-VEL02412');
        // QR de verdade, renderizado em SVG a partir do payload — nao um
        // placeholder. O `rect` de fundo branco e do writer, nao dos icones do
        // design system (que sao `viewBox="0 0 24 24"` e nao tem fundo).
        $resposta->assertSee('class="qrbox"', false);
        $resposta->assertSee('<rect x="0" y="0" width="220" height="220" fill="#ffffff"/>', false);
    }

    public function test_sem_chave_configurada_a_tela_avisa_em_vez_de_inventar_um_codigo(): void
    {
        // Um payload de fachada faria o app do banco recusar o pagamento com a
        // culpa caindo no financeiro da Velaro.
        config()->set('velaro-financeiro.beneficiario.pix_chave', null);

        $resposta = $this->actingAs($this->lojista)->get(route('portal.financeiro.pagamento', [
            $this->meus['lote_aberto'],
            'metodo' => Payment::METHOD_PIX,
        ]));

        $resposta->assertOk();
        $resposta->assertSee('chave Pix da Velaro ainda não está configurada', false);
        $resposta->assertDontSee('br.gov.bcb.pix', false);
    }

    public function test_o_payload_pix_segue_o_padrao_emv_e_fecha_o_crc(): void
    {
        $payload = PixBrCode::payload(self::CHAVE_PIX, 'Velaro Alianças Ltda', 'São Paulo', 2400.00, 'LOTE-2026-W24-VEL02412');

        $this->assertNotNull($payload);
        $this->assertStringStartsWith('000201', $payload);
        $this->assertStringContainsString('0014br.gov.bcb.pix', $payload);
        $this->assertStringContainsString('5303986', $payload);
        $this->assertStringContainsString('54072400.00', $payload);
        $this->assertStringContainsString('5802BR', $payload);
        // Acento nao entra no payload: o tamanho declarado no campo tem de bater
        // com o numero de bytes que o leitor conta.
        $this->assertStringContainsString('VELARO ALIANCAS LTD', $payload);
        $this->assertStringContainsString('SAO PAULO', $payload);
        $this->assertStringNotContainsString('ç', $payload);

        // CRC16/CCITT-FALSE do proprio payload, recalculado do zero.
        $corpo = substr($payload, 0, -4);
        $this->assertSame($this->crc16($corpo), substr($payload, -4));

        // Cada campo declara o proprio tamanho: 59 e o nome, com no maximo 25.
        $this->assertMatchesRegularExpression('/59(\d{2})/', $payload);

        $this->assertNull(PixBrCode::payload(null, 'Velaro', 'SAO PAULO', 10.0, 'X'));
        $this->assertNull(PixBrCode::payload('   ', 'Velaro', 'SAO PAULO', 10.0, 'X'));
    }

    public function test_o_boleto_usa_a_linha_digitavel_que_o_faturamento_gravou(): void
    {
        $resposta = $this->actingAs($this->lojista)->get(route('portal.financeiro.pagamento', [
            $this->meus['lote_aberto'],
            'metodo' => Payment::METHOD_BOLETO,
        ]));

        $resposta->assertOk();
        $resposta->assertSee('Linha digitável', false);
        $resposta->assertSee('00190000090123456789012345678');
        $resposta->assertSee('28/05/2026');
        $resposta->assertSee('Tomazelli Alianças Ltda', false);
        $resposta->assertSee('12.345.678/0001-90');
        $resposta->assertSee('R$ 2.400,00', false);
    }

    public function test_lote_sem_cobranca_emitida_nao_inventa_boleto(): void
    {
        $this->meus['cobranca_aberta']->delete();

        $resposta = $this->actingAs($this->lojista)->get(route('portal.financeiro.pagamento', [
            $this->meus['lote_aberto'],
            'metodo' => Payment::METHOD_BOLETO,
        ]));

        $resposta->assertOk();
        $resposta->assertSee('boleto deste lote ainda não foi emitido', false);
        $resposta->assertSee('Ainda não há cobrança registrada para este lote', false);
        $resposta->assertDontSee('00190000090123456789012345678');
    }

    public function test_a_transferencia_so_mostra_conta_quando_ha_conta_configurada(): void
    {
        $semConta = $this->actingAs($this->lojista)->get(route('portal.financeiro.pagamento', [
            $this->meus['lote_aberto'],
            'metodo' => Payment::METHOD_BANK_TRANSFER,
        ]));

        $semConta->assertOk();
        $semConta->assertSee('dados bancários da Velaro ainda não estão configurados', false);
        // A identificacao obrigatoria continua util mesmo sem conta.
        $semConta->assertSee('Lote 24/2026 · cód. revendedor VEL-02412', false);

        config()->set('velaro-financeiro.beneficiario.banco_codigo', '341');
        config()->set('velaro-financeiro.beneficiario.banco_nome', 'Itaú Unibanco');
        config()->set('velaro-financeiro.beneficiario.agencia', '1234');
        config()->set('velaro-financeiro.beneficiario.conta', '56789-0');

        $comConta = $this->actingAs($this->lojista)->get(route('portal.financeiro.pagamento', [
            $this->meus['lote_aberto'],
            'metodo' => Payment::METHOD_BANK_TRANSFER,
        ]));

        $comConta->assertOk();
        $comConta->assertSee('341 · Itaú Unibanco', false);
        $comConta->assertSee('1234');
        $comConta->assertSee('56789-0');
        $comConta->assertDontSee('dados bancários da Velaro ainda não estão configurados', false);
    }

    public function test_sem_metodo_na_url_a_tela_abre_no_meio_da_cobranca_existente(): void
    {
        // A cobranca do lote em aberto e um boleto; a tela abre nele, e nao no Pix
        // — o meio que o lojista precisa ver e o que ja foi emitido.
        $resposta = $this->actingAs($this->lojista)
            ->get(route('portal.financeiro.pagamento', $this->meus['lote_aberto']));

        $resposta->assertOk();
        $resposta->assertSee('Linha digitável', false);
    }

    public function test_metodo_inventado_cai_no_padrao_em_vez_de_derrubar_a_tela(): void
    {
        $resposta = $this->actingAs($this->lojista)->get(route('portal.financeiro.pagamento', [
            $this->meus['lote_aberto'],
            'metodo' => 'cartao-de-credito',
            'page' => 'abc',
        ]));

        $resposta->assertOk();
        $resposta->assertSee('Linha digitável', false);
    }

    public function test_o_comprovante_aparece_quando_o_financeiro_ja_anexou(): void
    {
        config()->set('velaro-financeiro.notas.disco', 'public');

        $resposta = $this->actingAs($this->lojista)
            ->get(route('portal.financeiro.pagamento', $this->meus['lote_pago']));

        $resposta->assertOk();
        $resposta->assertSee('VEL02412.pdf');
        $resposta->assertSee('/storage/comprovantes/VEL02412.pdf', false);
    }

    public function test_sem_comprovante_a_tela_manda_pelo_chamado_e_nao_finge_um_upload(): void
    {
        // Nao ha rota de escrita no Portal para isso: o caminho honesto e o
        // chamado de Financeiro, que existe.
        $resposta = $this->actingAs($this->lojista)
            ->get(route('portal.financeiro.pagamento', $this->meus['lote_aberto']));

        $resposta->assertOk();
        $resposta->assertSee('Nenhum comprovante anexado a este lote', false);
        $resposta->assertSee(route('portal.suporte.create'), false);
    }

    public function test_o_lote_de_outro_lojista_responde_404_e_nao_403(): void
    {
        // 403 diria "existe, mas nao e seu" — e o `{batch}` e um id sequencial,
        // entao percorrer a faixa mediria a base do concorrente.
        $resposta = $this->actingAs($this->lojista)
            ->get(route('portal.financeiro.pagamento', $this->doVizinho['lote_aberto']));

        $resposta->assertNotFound();
        $resposta->assertDontSee('7.777,00', false);
    }

    public function test_o_lote_de_outro_lojista_e_o_inexistente_respondem_exatamente_igual(): void
    {
        $doVizinho = $this->actingAs($this->lojista)
            ->get(route('portal.financeiro.pagamento', $this->doVizinho['lote_aberto']->getKey()));

        $inexistente = $this->actingAs($this->lojista)
            ->get(route('portal.financeiro.pagamento', 999999));

        $this->assertSame(404, $doVizinho->status());
        $this->assertSame($inexistente->status(), $doVizinho->status());
        $this->assertSame($inexistente->getContent(), $doVizinho->getContent());
    }

    public function test_o_lote_do_proprio_lojista_atravessa_o_escopo(): void
    {
        foreach (['lote_aberto', 'lote_pago'] as $chave) {
            $this->actingAs($this->lojista)
                ->get(route('portal.financeiro.pagamento', $this->meus[$chave]))
                ->assertOk();
        }
    }

    /**
     * CRC16/CCITT-FALSE independente do da implementacao — se as duas
     * concordarem, o digito do campo 63 esta certo.
     */
    private function crc16(string $payload): string
    {
        $crc = 0xFFFF;

        foreach (str_split($payload) as $caractere) {
            $crc ^= ord($caractere) << 8;

            for ($bit = 0; $bit < 8; $bit++) {
                $crc = ($crc & 0x8000) !== 0 ? (($crc << 1) ^ 0x1021) & 0xFFFF : ($crc << 1) & 0xFFFF;
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
