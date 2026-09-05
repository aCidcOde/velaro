<?php

/*
[Modulo: tests/Feature/Site]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Cobre o lote cadastro-solicitacao: formulario, gravacao com prova de LGPD e as tres telas de acompanhamento.
*/

namespace Tests\Feature\Site;

use App\Http\Middleware\EnsureCanTrackReseller;
use App\Models\Reseller;
use App\Models\ResellerConsent;
use App\Models\ResellerDocument;
use App\Models\ResellerStatusEvent;
use App\Models\ResellerVerification;
use App\Models\User;
use App\Services\Site\ResellerRegistrationService;
use App\Services\Site\ResellerStatusService;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResellerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // As rotas do Velaro ainda não entram pelo bootstrap: a integração faz isso
        // quando todos os lotes fecharem. Aqui elas são carregadas só para o teste.
        Route::middleware('web')->group(base_path('routes/velaro.php'));
        Route::getRoutes()->refreshNameLookups();
        Route::getRoutes()->refreshActionLookups();

        $this->withoutVite();
    }

    public function test_formulario_de_cadastro_mostra_os_campos_da_tela(): void
    {
        $this->get(route('site.cadastro'))
            ->assertOk()
            ->assertSee('CADASTRO COMO LOJISTA')
            ->assertSee('name="legal_name"', false)
            ->assertSee('name="cnpj"', false)
            ->assertSee('name="contact_cpf"', false)
            ->assertSee('name="postal_code"', false)
            ->assertSee('name="articles_of_incorporation"', false)
            ->assertSee('name="partner_id_document"', false)
            ->assertSee('name="cnpj_card"', false)
            ->assertSee('name="accept_business"', false)
            ->assertSee('name="accept_verification"', false)
            ->assertSee('name="accept_terms"', false);
    }

    public function test_cadastro_valido_cria_o_revendedor_com_documentos_aceites_e_triagem_pendente(): void
    {
        Storage::fake(ResellerRegistrationService::DOCUMENT_DISK);
        Notification::fake();

        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.45'])
            ->withHeaders(['User-Agent' => 'VelaroTest/1.0'])
            ->post(route('site.cadastro.store'), $this->payload());

        $reseller = Reseller::firstOrFail();

        $response->assertRedirect(route('site.solicitacao.enviada', ['reseller' => $reseller->protocol]));

        $this->assertSame(Reseller::STATUS_PENDING, $reseller->status);
        $this->assertSame('11.222.333/0001-81', $reseller->cnpj);
        $this->assertSame('529.982.247-25', $reseller->contact_cpf);
        $this->assertSame('RS', $reseller->state);
        $this->assertMatchesRegularExpression('/^VEL-\d{4}-\d{4}$/', (string) $reseller->protocol);

        $this->assertSame(3, ResellerDocument::where('reseller_id', $reseller->id)->count());
        $this->assertEqualsCanonicalizing(
            ['articles_of_incorporation', 'partner_id_document', 'cnpj_card'],
            ResellerDocument::where('reseller_id', $reseller->id)->orderBy('type')->pluck('type')->all()
        );

        foreach (ResellerDocument::where('reseller_id', $reseller->id)->get() as $document) {
            $this->assertSame(ResellerRegistrationService::DOCUMENT_DISK, $document->disk);
            Storage::disk(ResellerRegistrationService::DOCUMENT_DISK)->assertExists($document->path);
        }

        // Regra 2 da tela 1.4: cada aceite guarda IP, agente e versão do texto.
        $consents = ResellerConsent::where('reseller_id', $reseller->id)->get();
        $this->assertSame(4, $consents->count());

        foreach ($consents as $consent) {
            $this->assertTrue($consent->granted);
            $this->assertSame('203.0.113.45', $consent->ip_address);
            $this->assertSame('VelaroTest/1.0', $consent->user_agent);
            $this->assertSame(ResellerRegistrationService::CONSENT_DOCUMENT_VERSION, $consent->document_version);
            $this->assertNotNull($consent->granted_at);
        }

        $this->assertEqualsCanonicalizing([
            ResellerConsent::TYPE_BUSINESS_DECLARATION,
            ResellerConsent::TYPE_AUTOMATED_VERIFICATION,
            ResellerConsent::TYPE_TERMS,
            ResellerConsent::TYPE_PRIVACY_POLICY,
        ], $consents->pluck('type')->all());

        $verification = ResellerVerification::where('reseller_id', $reseller->id)->firstOrFail();
        $this->assertSame(ResellerVerification::STATUS_PENDING, $verification->status);
        $this->assertNull($verification->checked_at);
        $this->assertNull($verification->cnpj_valido);

        $event = ResellerStatusEvent::where('reseller_id', $reseller->id)->firstOrFail();
        $this->assertNull($event->from_status);
        $this->assertSame(Reseller::STATUS_PENDING, $event->to_status);

        // O vínculo users.reseller_id só nasce na aprovação (regra 1 da tela 1.7).
        $user = User::where('email', 'contato@tomazelli.com.br')->firstOrFail();
        $this->assertNull($user->reseller_id);
        $this->assertNotSame('SenhaForte123', $user->password);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_formulario_volta_com_os_erros_e_com_o_que_ja_foi_digitado(): void
    {
        Storage::fake(ResellerRegistrationService::DOCUMENT_DISK);

        $this->from(route('site.cadastro'))
            ->post(route('site.cadastro.store'), $this->payload(['cnpj' => '11.222.333/0001-99']))
            ->assertRedirect(route('site.cadastro'));

        $this->get(route('site.cadastro'))
            ->assertOk()
            ->assertSee('o cadastro ainda não foi enviado', false)
            ->assertSee('Informe um CNPJ válido.')
            ->assertSee('value="Tomazelli Alianças Ltda."', false);
    }

    public function test_cnaes_informados_sao_gravados_sem_veredito_de_compatibilidade(): void
    {
        Storage::fake(ResellerRegistrationService::DOCUMENT_DISK);

        $this->post(route('site.cadastro.store'), $this->payload([
            'cnaes' => [
                ['code' => '4783-1/02', 'description' => 'Comércio varejista de artigos de joalheria', 'is_primary' => 1],
            ],
        ]))->assertRedirect();

        $cnae = Reseller::firstOrFail()->cnaes()->firstOrFail();

        $this->assertSame('4783-1/02', $cnae->code);
        $this->assertTrue($cnae->is_primary);
        $this->assertNull($cnae->compatible);
    }

    public function test_cnpj_invalido_cpf_invalido_e_aceites_ausentes_barram_o_cadastro(): void
    {
        Storage::fake(ResellerRegistrationService::DOCUMENT_DISK);

        $this->post(route('site.cadastro.store'), $this->payload([
            'cnpj' => '11.222.333/0001-99',
            'contact_cpf' => '111.111.111-11',
            'accept_terms' => null,
        ]))->assertSessionHasErrors(['cnpj', 'contact_cpf', 'accept_terms']);

        $this->assertSame(0, Reseller::count());
        $this->assertSame(0, User::where('email', 'contato@tomazelli.com.br')->count());
    }

    public function test_documentos_obrigatorios_faltando_barram_o_cadastro(): void
    {
        Storage::fake(ResellerRegistrationService::DOCUMENT_DISK);

        $payload = $this->payload();
        unset($payload['articles_of_incorporation'], $payload['cnpj_card']);

        $this->post(route('site.cadastro.store'), $payload)
            ->assertSessionHasErrors(['articles_of_incorporation', 'cnpj_card']);

        $this->assertSame(0, Reseller::count());
    }

    public function test_cnpj_duplicado_e_email_ja_usado_sao_recusados(): void
    {
        Storage::fake(ResellerRegistrationService::DOCUMENT_DISK);

        Reseller::factory()->create(['cnpj' => '11.222.333/0001-81']);
        User::factory()->create(['email' => 'contato@tomazelli.com.br']);

        $this->post(route('site.cadastro.store'), $this->payload())
            ->assertSessionHasErrors(['cnpj', 'email']);
    }

    public function test_tela_de_solicitacao_enviada_mostra_protocolo_e_resumo(): void
    {
        $reseller = Reseller::factory()->pending()->create([
            'protocol' => 'VEL-2026-0148',
            'legal_name' => 'Tomazelli Alianças Ltda.',
            'contact_source' => 'site',
        ]);

        $this->withSession([EnsureCanTrackReseller::SESSION_KEY => ['VEL-2026-0148']])
            ->get(route('site.solicitacao.enviada', ['reseller' => $reseller->protocol]))
            ->assertOk()
            ->assertSee('SOLICITAÇÃO ENVIADA')
            ->assertSee('VEL-2026-0148')
            ->assertSee('Tomazelli Alianças Ltda.')
            ->assertSee('Site');
    }

    public function test_tela_de_status_monta_linha_do_tempo_e_resultado_da_verificacao(): void
    {
        $reseller = Reseller::factory()->pending()->create([
            'protocol' => 'VEL-2026-0149',
            'contact_source' => 'indicacao_lojista',
        ]);

        ResellerStatusEvent::create([
            'reseller_id' => $reseller->id,
            'from_status' => null,
            'to_status' => Reseller::STATUS_PENDING,
            'note' => 'Cadastro recebido pelo site.',
        ]);

        ResellerVerification::create([
            'reseller_id' => $reseller->id,
            'status' => ResellerVerification::STATUS_PENDING,
            'cnpj_valido' => true,
            'documentacao_enviada' => true,
        ]);

        $dono = User::factory()->create(['email' => $reseller->email]);

        $this->actingAs($dono)
            ->get(route('site.solicitacao.status', ['reseller' => $reseller->protocol]))
            ->assertOk()
            ->assertSee('STATUS DA SUA SOLICITAÇÃO')
            ->assertSee('VEL-2026-0149')
            ->assertSee('Cadastro recebido')
            ->assertSee('Indicação de lojista parceiro')
            ->assertSee('Consulta de CNPJ')
            ->assertSee('Compatibilidade com o segmento')
            ->assertSee('Em validação automática');
    }

    public function test_tela_de_aprovado_so_abre_para_cadastro_aprovado(): void
    {
        $pendente = Reseller::factory()->pending()->create(['protocol' => 'VEL-2026-0150']);

        $this->get(route('site.solicitacao.aprovado', ['reseller' => $pendente->protocol]))
            ->assertRedirect(route('site.solicitacao.status', ['reseller' => 'VEL-2026-0150']));

        $aprovado = Reseller::factory()->approved()->create(['protocol' => 'VEL-2026-0151']);

        $this->get(route('site.solicitacao.aprovado', ['reseller' => $aprovado->protocol]))
            ->assertOk()
            ->assertSee('CADASTRO APROVADO!')
            ->assertSee('Acessar minha plataforma');
    }

    public function test_quem_enviou_o_formulario_abre_o_acompanhamento_na_mesma_sessao(): void
    {
        Storage::fake(ResellerRegistrationService::DOCUMENT_DISK);
        Notification::fake();

        $this->post(route('site.cadastro.store'), $this->payload())
            ->assertRedirect();

        $reseller = Reseller::firstOrFail();

        $this->get(route('site.solicitacao.enviada', ['reseller' => $reseller->protocol]))
            ->assertOk()
            ->assertSee((string) $reseller->protocol);

        $this->get(route('site.solicitacao.status', ['reseller' => $reseller->protocol]))
            ->assertOk();
    }

    /**
     * Regra 2 da tela 1.6: o pré-cadastro vê somente a própria solicitação. O
     * protocolo é sequencial, então sem gate a tela vira uma lista pública de
     * razão social, CNPJ, e-mail e WhatsApp de todo mundo que se cadastrou.
     */
    public function test_visitante_sem_vinculo_nao_le_a_solicitacao_de_outro(): void
    {
        $reseller = Reseller::factory()->pending()->create([
            'protocol' => 'VEL-2026-0152',
            'legal_name' => 'Tomazelli Alianças Ltda.',
            'email' => 'contato@tomazelli.com.br',
        ]);

        foreach (['site.solicitacao.enviada', 'site.solicitacao.status'] as $rota) {
            $this->get(route($rota, ['reseller' => $reseller->protocol]))
                ->assertRedirect(route('login'))
                ->assertSessionMissing('errors');
        }

        $estranho = User::factory()->create(['email' => 'outro@exemplo.com.br']);

        $this->actingAs($estranho)
            ->get(route('site.solicitacao.status', ['reseller' => $reseller->protocol]))
            ->assertForbidden()
            ->assertDontSee('Tomazelli Alianças Ltda.');
    }

    public function test_master_acompanha_qualquer_solicitacao(): void
    {
        $reseller = Reseller::factory()->pending()->create(['protocol' => 'VEL-2026-0153']);
        $master = $this->createBackendAdmin();

        $this->actingAs($master)
            ->get(route('site.solicitacao.status', ['reseller' => $reseller->protocol]))
            ->assertOk();
    }

    /**
     * `documentacao_enviada` nasce verdadeira no envio (os arquivos chegaram). A
     * tela 1.6 não pode traduzir isso em "Análise complementar de documentos:
     * Concluído" antes de a triagem rodar — o veredito vem do `result` do job.
     */
    public function test_analise_de_documentos_fica_pendente_ate_a_triagem_concluir(): void
    {
        $reseller = Reseller::factory()->pending()->create(['protocol' => 'VEL-2026-0154']);

        $verification = ResellerVerification::create([
            'reseller_id' => $reseller->id,
            'status' => ResellerVerification::STATUS_PENDING,
            'documentacao_enviada' => true,
        ]);

        $checks = app(ResellerStatusService::class)->verificationChecks($reseller);
        $documentos = array_values(array_filter(
            $checks,
            static fn (array $check): bool => $check['label'] === 'Análise complementar de documentos'
        ));

        $this->assertSame('wait', $documentos[0]['state']);
        $this->assertSame('Em processamento', $documentos[0]['note']);

        $verification->update(['result' => ['documents_reviewed' => true], 'checked_at' => now()]);

        $checks = app(ResellerStatusService::class)->verificationChecks($reseller->refresh());
        $documentos = array_values(array_filter(
            $checks,
            static fn (array $check): bool => $check['label'] === 'Análise complementar de documentos'
        ));

        $this->assertSame('ok', $documentos[0]['state']);
    }

    /**
     * `resellers.cnpj` tem índice único simples: o soft delete não libera o
     * número. A validação precisa acusar isso, não deixar o INSERT estourar.
     */
    public function test_cnpj_de_revendedor_excluido_continua_bloqueado(): void
    {
        Storage::fake(ResellerRegistrationService::DOCUMENT_DISK);

        $antigo = Reseller::factory()->create(['cnpj' => '11.222.333/0001-81']);
        $antigo->delete();

        $this->post(route('site.cadastro.store'), $this->payload())
            ->assertSessionHasErrors(['cnpj']);
    }

    public function test_protocolo_segue_o_formato_sequencial_por_ano(): void
    {
        $service = app(ResellerRegistrationService::class);

        $this->assertSame('VEL-'.now()->format('Y').'-0001', $service->nextProtocol());

        Reseller::factory()->create(['protocol' => 'VEL-'.now()->format('Y').'-0148']);

        $this->assertSame('VEL-'.now()->format('Y').'-0149', $service->nextProtocol());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        $payload = [
            'legal_name' => 'Tomazelli Alianças Ltda.',
            'trade_name' => 'Tomazelli Alianças',
            'cnpj' => '11222333000181',
            'state_registration' => '123.456.789.111',
            'contact_name' => 'Edemar Tomazelli',
            'contact_cpf' => '52998224725',
            'postal_code' => '95010000',
            'street' => 'Rua XV de Novembro',
            'street_number' => '1234',
            'address_complement' => 'Sala 8',
            'district' => 'Centro',
            'city' => 'Caxias do Sul',
            'state' => 'rs',
            'email' => 'contato@tomazelli.com.br',
            'whatsapp' => '(54) 99999-8888',
            'contact_source' => 'site',
            'password' => 'SenhaForte123',
            'password_confirmation' => 'SenhaForte123',
            'notes' => 'Loja com duas unidades na serra gaúcha.',
            'articles_of_incorporation' => UploadedFile::fake()->create('contrato-social.pdf', 120, 'application/pdf'),
            'partner_id_document' => UploadedFile::fake()->create('documento-socio.pdf', 90, 'application/pdf'),
            'cnpj_card' => UploadedFile::fake()->create('cartao-cnpj.pdf', 60, 'application/pdf'),
            'accept_business' => '1',
            'accept_verification' => '1',
            'accept_terms' => '1',
        ];

        foreach ($overrides as $key => $value) {
            if ($value === null) {
                unset($payload[$key]);

                continue;
            }

            $payload[$key] = $value;
        }

        return $payload;
    }
}
