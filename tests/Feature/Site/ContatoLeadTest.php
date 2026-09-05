<?php

/*
[Modulo: tests/Feature/Site]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Cobre a tela 1.8: lead com aceite LGPD, sem criar revendedor, usuario, chamado nem preco B2B.
*/

namespace Tests\Feature\Site;

use App\Models\ContactLead;
use App\Models\Setting;
use App\Models\User;
use App\Services\Site\ContactLeadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ContatoLeadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->semearCanais();
    }

    /**
     * As rotas do site nascem do bootstrap, dentro do grupo `web`. Sem ele nao ha
     * sessao (o aviso de envio some), nao ha CSRF no POST aberto na internet e
     * `$errors` nao chega na view — todo @error da tela morre em erro 500.
     */
    public function test_as_rotas_do_site_andam_no_grupo_web(): void
    {
        foreach (['site.contato', 'site.contato.store'] as $nome) {
            $rota = Route::getRoutes()->getByName($nome);

            $this->assertNotNull($rota, "A rota {$nome} precisa nascer do bootstrap.");
            $this->assertContains('web', $rota->gatherMiddleware(), "A rota {$nome} precisa do grupo web.");
        }
    }

    public function test_a_tela_mostra_os_canais_diretos_lidos_de_settings(): void
    {
        $resposta = $this->get(route('site.contato'));

        $resposta->assertOk();
        $resposta->assertSee('FALE CONOSCO');
        $resposta->assertSee('+55 (16) 99487-7800');
        $resposta->assertSee('vendas@velaro.com.br');
        $resposta->assertSee('Segunda a sexta, das 8h às 18h', false);
        $resposta->assertSee('Contato não é chamado.', false);

        // A célula do WhatsApp tem chave própria em `settings` — não é o telefone
        // comercial repetido no HTML.
        $resposta->assertSee('+55 (16) 98888-1200');
    }

    public function test_sem_a_chave_de_whatsapp_a_celula_cai_no_telefone_comercial(): void
    {
        Setting::query()->where('key', 'contact.whatsapp')->delete();

        $resposta = $this->get(route('site.contato'));

        $resposta->assertOk();
        $resposta->assertDontSee('+55 (16) 98888-1200');
        $resposta->assertSee('+55 (16) 99487-7800');
    }

    public function test_o_aviso_de_envio_volta_na_tela(): void
    {
        // Prova que a rota anda com sessão: sem o grupo `web` o flash se perde.
        $this->post(route('site.contato.store'), $this->formulario());

        $this->get(route('site.contato'))
            ->assertSee('Mensagem enviada.', false);
    }

    public function test_a_rota_publica_nao_renderiza_preco_b2b(): void
    {
        $resposta = $this->get(route('site.contato'));

        $resposta->assertOk();
        $resposta->assertDontSee('R$');
        $resposta->assertDontSee('price');
    }

    public function test_a_pagina_de_partida_e_o_assunto_chegam_pela_url(): void
    {
        $resposta = $this->get(route('site.contato', ['origem' => 'catalogo', 'assunto' => 'prazo-producao']));

        $resposta->assertOk();
        $resposta->assertSee('name="origin" value="catalogo"', false);
        $resposta->assertSee('<option value="prazo-producao" selected>', false);
    }

    public function test_pagina_de_partida_desconhecida_cai_no_default(): void
    {
        $resposta = $this->get(route('site.contato', ['origem' => 'backend']));

        $resposta->assertOk();
        $resposta->assertSee('name="origin" value="contato"', false);
    }

    public function test_o_envio_grava_o_lead_com_o_aceite_lgpd(): void
    {
        $resposta = $this->post(route('site.contato.store'), $this->formulario());

        $resposta->assertRedirect(route('site.contato'));
        $resposta->assertSessionHas('status');

        $lead = ContactLead::sole();

        $this->assertSame('Rafael Nogueira', $lead->name);
        $this->assertSame('rafael@joalherianogueira.com.br', $lead->email);
        $this->assertSame('Joalheria Nogueira', $lead->company);
        $this->assertSame('Condições comerciais e catálogo', $lead->subject);
        $this->assertSame('catalogo', $lead->origin);
        $this->assertSame(ContactLeadService::STATUS_NEW, $lead->status);
        $this->assertNull($lead->handled_by);
        $this->assertNull($lead->handled_at);

        // A prova do aceite fica no proprio lead: data, versao do texto, IP e user agent.
        $this->assertNotNull($lead->consent_granted_at);
        $this->assertSame(ContactLeadService::CONSENT_DOCUMENT_VERSION, $lead->consent_document_version);
        $this->assertNotNull($lead->consent_ip_address);
    }

    public function test_o_telefone_entra_na_mascara_da_tela(): void
    {
        $this->post(route('site.contato.store'), $this->formulario(['phone' => '+55 16 99887-1234']));

        $this->assertSame('(16) 99887-1234', ContactLead::sole()->phone);
    }

    public function test_sem_o_aceite_lgpd_nao_ha_lead(): void
    {
        $resposta = $this->post(route('site.contato.store'), $this->formulario(['consent' => null]));

        $resposta->assertSessionHasErrors('consent');
        $this->assertDatabaseCount('contact_leads', 0);
    }

    public function test_o_lead_nao_cria_revendedor_usuario_nem_chamado(): void
    {
        $usuariosAntes = User::count();

        $this->post(route('site.contato.store'), $this->formulario());

        $this->assertDatabaseCount('contact_leads', 1);
        $this->assertDatabaseCount('resellers', 0);
        $this->assertDatabaseCount('support_tickets', 0);
        $this->assertSame($usuariosAntes, User::count());
    }

    public function test_assunto_e_pagina_de_partida_fora_da_lista_sao_recusados(): void
    {
        $this->post(route('site.contato.store'), $this->formulario(['subject' => 'preco-b2b']))
            ->assertSessionHasErrors('subject');

        $this->post(route('site.contato.store'), $this->formulario(['origin' => 'backend']))
            ->assertSessionHasErrors('origin');

        $this->assertDatabaseCount('contact_leads', 0);
    }

    public function test_a_mensagem_para_em_mil_caracteres(): void
    {
        $this->post(route('site.contato.store'), $this->formulario(['message' => str_repeat('a', 1001)]))
            ->assertSessionHasErrors('message');

        $this->assertDatabaseCount('contact_leads', 0);
    }

    public function test_sem_pagina_de_partida_o_lead_nasce_na_propria_tela(): void
    {
        $this->post(route('site.contato.store'), $this->formulario(['origin' => null]));

        $this->assertSame(ContactLeadService::DEFAULT_ORIGIN, ContactLead::sole()->origin);
    }

    public function test_formulario_aberto_na_internet_anda_com_throttle(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('site.contato.store'), $this->formulario())->assertRedirect();
        }

        $this->post(route('site.contato.store'), $this->formulario())->assertStatus(429);
    }

    /**
     * @param  array<string, string|null>  $trocas
     * @return array<string, string>
     */
    private function formulario(array $trocas = []): array
    {
        $dados = array_merge([
            'name' => 'Rafael Nogueira',
            'email' => 'rafael@joalherianogueira.com.br',
            'phone' => '(16) 99887-1234',
            'company' => 'Joalheria Nogueira',
            'subject' => 'condicoes-comerciais',
            'message' => 'Gostaria de conhecer as condições comerciais para revenda.',
            'consent' => '1',
            'origin' => 'catalogo',
        ], $trocas);

        return array_filter($dados, fn (?string $valor): bool => $valor !== null);
    }

    private function semearCanais(): void
    {
        $canais = [
            'contact.telefone' => '+55 (16) 99487-7800',
            // Número diferente de propósito: prova que a barra lê a chave própria.
            'contact.whatsapp' => '+55 (16) 98888-1200',
            'contact.email' => 'vendas@velaro.com.br',
            'contact.horario' => 'Segunda a sexta, das 8h às 18h',
        ];

        foreach ($canais as $chave => $valor) {
            Setting::factory()->publicSetting()->create([
                'group' => 'contact',
                'key' => $chave,
                'value' => $valor,
            ]);
        }
    }
}
