<?php

/*
[Modulo: tests/Feature/Site]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Regras 4 e 5 da tela 1.6: o reenvio de documentos so existe em Aguardando informacoes e devolve a solicitacao para analise.
*/

namespace Tests\Feature\Site;

use App\Models\Reseller;
use App\Models\ResellerDocument;
use App\Models\ResellerStatusEvent;
use App\Models\User;
use App\Services\Site\ResellerDocumentStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A contraparte da acao "Solicitar informacoes adicionais" do Painel Master
 * (tela 3.11), que ate aqui nao tinha resposta possivel do outro lado: o Master
 * pedia o documento e o lojista nao tinha por onde enviar.
 *
 * O endereco e um so — `POST /solicitacao/{protocolo}/documentos`, como o doc da
 * tela declara — e serve os dois lugares onde o lojista ve o pedido: o painel,
 * onde ele entra logado, e o link transacional do e-mail e do WhatsApp.
 */
class ResellerDocumentResubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(ResellerDocumentStorage::DISK);
    }

    public function test_reenvio_grava_o_documento_e_devolve_a_solicitacao_para_analise(): void
    {
        $reseller = Reseller::factory()->create(['status' => Reseller::STATUS_AWAITING_INFO]);
        $lojista = User::factory()->forReseller($reseller)->create();

        $resposta = $this->actingAs($lojista)->post(
            route('site.solicitacao.documentos', ['reseller' => $reseller->protocol]),
            [ResellerDocument::TYPE_ARTICLES_OF_INCORPORATION => UploadedFile::fake()->create('contrato.pdf', 120, 'application/pdf')],
        );

        $resposta->assertRedirect();
        $resposta->assertSessionHas('status');

        $documento = ResellerDocument::where('reseller_id', $reseller->id)
            ->where('type', ResellerDocument::TYPE_ARTICLES_OF_INCORPORATION)
            ->firstOrFail();

        $this->assertSame('contrato.pdf', $documento->original_name);
        $this->assertSame(ResellerDocumentStorage::DISK, $documento->disk);
        Storage::disk(ResellerDocumentStorage::DISK)->assertExists($documento->path);

        // Regra 5: volta para `pending` e o reenvio entra na linha do tempo.
        $this->assertSame(Reseller::STATUS_PENDING, $reseller->refresh()->status);

        $evento = ResellerStatusEvent::where('reseller_id', $reseller->id)
            ->where('to_status', Reseller::STATUS_PENDING)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(Reseller::STATUS_AWAITING_INFO, $evento->from_status);
        $this->assertNull($evento->actor_id);
        $this->assertNotNull($evento->note);
    }

    public function test_reenvio_aceita_os_tres_tipos_de_documento_de_uma_vez(): void
    {
        $reseller = Reseller::factory()->create(['status' => Reseller::STATUS_AWAITING_INFO]);
        $lojista = User::factory()->forReseller($reseller)->create();

        $this->actingAs($lojista)->post(
            route('site.solicitacao.documentos', ['reseller' => $reseller->protocol]),
            [
                ResellerDocument::TYPE_ARTICLES_OF_INCORPORATION => UploadedFile::fake()->create('contrato.pdf', 50, 'application/pdf'),
                ResellerDocument::TYPE_PARTNER_ID_DOCUMENT => UploadedFile::fake()->image('socio.jpg'),
                ResellerDocument::TYPE_CNPJ_CARD => UploadedFile::fake()->create('cnpj.pdf', 50, 'application/pdf'),
            ],
        )->assertSessionHasNoErrors();

        $this->assertSame(3, ResellerDocument::where('reseller_id', $reseller->id)->count());
    }

    public function test_envio_sem_nenhum_arquivo_e_recusado(): void
    {
        // Sem isto o botao devolveria a solicitacao para a fila sem anexar nada, e
        // a equipe reabriria a analise para reler o mesmo material.
        $reseller = Reseller::factory()->create(['status' => Reseller::STATUS_AWAITING_INFO]);
        $lojista = User::factory()->forReseller($reseller)->create();

        $this->actingAs($lojista)
            ->post(route('site.solicitacao.documentos', ['reseller' => $reseller->protocol]), [])
            ->assertSessionHasErrors(ResellerDocument::TYPE_ARTICLES_OF_INCORPORATION);

        $this->assertSame(Reseller::STATUS_AWAITING_INFO, $reseller->refresh()->status);
        $this->assertSame(0, ResellerDocument::where('reseller_id', $reseller->id)->count());
    }

    public function test_arquivo_de_tipo_ou_tamanho_indevido_e_recusado(): void
    {
        $reseller = Reseller::factory()->create(['status' => Reseller::STATUS_AWAITING_INFO]);
        $lojista = User::factory()->forReseller($reseller)->create();

        $this->actingAs($lojista)->post(
            route('site.solicitacao.documentos', ['reseller' => $reseller->protocol]),
            [ResellerDocument::TYPE_CNPJ_CARD => UploadedFile::fake()->create('planilha.xlsx', 20)],
        )->assertSessionHasErrors(ResellerDocument::TYPE_CNPJ_CARD);

        $this->actingAs($lojista)->post(
            route('site.solicitacao.documentos', ['reseller' => $reseller->protocol]),
            [ResellerDocument::TYPE_CNPJ_CARD => UploadedFile::fake()->create('gigante.pdf', 6144, 'application/pdf')],
        )->assertSessionHasErrors(ResellerDocument::TYPE_CNPJ_CARD);

        $this->assertSame(0, ResellerDocument::where('reseller_id', $reseller->id)->count());
    }

    /**
     * Regra 4 · fora de `awaiting_info` o bloco nao aparece — e esconder o
     * formulario nao pode ser a unica tranca.
     */
    public function test_fora_de_awaiting_info_o_reenvio_responde_403(): void
    {
        foreach ([Reseller::STATUS_PENDING, Reseller::STATUS_APPROVED, Reseller::STATUS_REJECTED] as $status) {
            $reseller = Reseller::factory()->create(['status' => $status]);
            $lojista = User::factory()->forReseller($reseller)->create();

            $this->actingAs($lojista)->post(
                route('site.solicitacao.documentos', ['reseller' => $reseller->protocol]),
                [ResellerDocument::TYPE_CNPJ_CARD => UploadedFile::fake()->create('cnpj.pdf', 50, 'application/pdf')],
            )->assertForbidden();

            $this->assertSame(0, ResellerDocument::where('reseller_id', $reseller->id)->count());
        }
    }

    /**
     * O reenvio herda o mesmo gate do acompanhamento: a solicitacao e de quem tem
     * o vinculo, e nao de quem descobriu o protocolo.
     */
    public function test_solicitacao_de_outro_lojista_nao_aceita_reenvio(): void
    {
        $reseller = Reseller::factory()->create(['status' => Reseller::STATUS_AWAITING_INFO]);
        $intruso = User::factory()->forReseller(Reseller::factory()->approved()->create())->create();

        $this->actingAs($intruso)->post(
            route('site.solicitacao.documentos', ['reseller' => $reseller->protocol]),
            [ResellerDocument::TYPE_CNPJ_CARD => UploadedFile::fake()->create('cnpj.pdf', 50, 'application/pdf')],
        )->assertForbidden();

        $this->assertSame(Reseller::STATUS_AWAITING_INFO, $reseller->refresh()->status);
        $this->assertSame(0, ResellerDocument::where('reseller_id', $reseller->id)->count());
    }

    /**
     * A tela publica 1.6 mostra o mesmo bloco: o lojista que chegou pelo link do
     * e-mail nao precisa entrar no painel para responder.
     */
    public function test_tela_publica_de_status_tambem_abre_o_reenvio(): void
    {
        $reseller = Reseller::factory()->create(['status' => Reseller::STATUS_AWAITING_INFO]);

        ResellerStatusEvent::create([
            'reseller_id' => $reseller->id,
            'from_status' => Reseller::STATUS_PENDING,
            'to_status' => Reseller::STATUS_AWAITING_INFO,
            'note' => 'Cartão CNPJ ilegível.',
        ]);

        $lojista = User::factory()->forReseller($reseller)->create();

        $this->actingAs($lojista)
            ->get(route('site.solicitacao.status', ['reseller' => $reseller->protocol]))
            ->assertOk()
            ->assertSee('Reenvio de documentos')
            ->assertSee('Cartão CNPJ ilegível.');
    }
}
