<?php

/*
[Modulo: tests/Feature/Auth]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 0: prova o roteamento por perfil do login unico — Master no backend e todo lojista vinculado no painel.
*/

namespace Tests\Feature\Auth;

use App\Models\Reseller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LoginProfileRoutingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Regra 2 · Master vai para o backend.
     *
     * O gate `access-backend` nao olha so o `is_admin`: sem a permissao do
     * catalogo ACL sincronizada ele nega, e o Master cai no destino padrao.
     */
    public function test_master_lands_on_the_backend(): void
    {
        // `createBackendAdmin()` cria o admin com 2FA ligado; aqui o que esta em
        // prova e o destino do login, nao o desafio do segundo fator.
        $master = $this->createBackendAdmin([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        $this->post(route('login.store'), [
            'email' => $master->email,
            'password' => 'password',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('backend.dashboard', absolute: false));

        $this->assertAuthenticatedAs($master);
    }

    /**
     * Regra 2 · Master sem a permissao ACL nao e mandado para o backend.
     */
    public function test_admin_without_acl_is_not_routed_to_the_backend(): void
    {
        $admin = User::factory()->withoutTwoFactor()->admin()->create();

        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));
    }

    /**
     * Regra 2 · Parceiro Premium aprovado vai para o portal.
     *
     * A asercao para no redirect de proposito: quem renderiza `/portal` e outro
     * lote de trabalho, e o contrato da tela 0 termina no destino escolhido.
     */
    public function test_approved_reseller_is_routed_to_the_portal(): void
    {
        $reseller = Reseller::factory()->approved()->create();
        $user = User::factory()->withoutTwoFactor()->forReseller($reseller)->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('portal.dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    /**
     * Regra 3 · Reprovado e inativo entram no painel, e so nele.
     *
     * O destino deixou de ser a pagina publica de acompanhamento: eles logam no
     * proprio painel e leem ali o motivo e o caminho para regularizar. O que a
     * aprovacao concede continua fechado — o negocio do portal responde 403.
     *
     * @param  Reseller::STATUS_*  $status
     */
    #[DataProvider('statusSemAcessoAoPortal')]
    public function test_rejected_or_inactive_reseller_lands_on_the_panel_but_not_on_the_business(string $status): void
    {
        $reseller = Reseller::factory()->create(['status' => $status]);
        $user = User::factory()->withoutTwoFactor()->forReseller($reseller)->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('portal.dashboard', absolute: false));

        $this->get(route('portal.dashboard'))->assertOk();

        // A porta do negocio fica fechada mesmo com a sessao aberta.
        $this->get(route('portal.catalogo'))->assertForbidden();
        $this->get(route('portal.pedidos.index'))->assertForbidden();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function statusSemAcessoAoPortal(): array
    {
        return [
            'reprovado' => [Reseller::STATUS_REJECTED],
            'inativo' => [Reseller::STATUS_INACTIVE],
        ];
    }

    /**
     * Regra 3 · Conta bloqueada nao autentica.
     */
    public function test_blocked_user_does_not_authenticate(): void
    {
        $user = User::factory()->withoutTwoFactor()->blocked()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /**
     * Regra 2 · Pre-cadastro acompanha a propria solicitacao — dentro do painel.
     *
     * Um login, um painel: o destino do pre-cadastro passou a ser `/portal`, onde
     * o acompanhamento e o conteudo do primeiro estagio da jornada. Antes ele era
     * mandado para `/solicitacao/{protocol}`, uma pagina fora do painel — o
     * lojista terminava o cadastro com um login que o expulsava do produto.
     *
     * A rota publica continua de pe: e ela que o link do e-mail e do WhatsApp
     * abre. Ela so deixou de ser destino de quem logou.
     */
    public function test_pending_reseller_is_routed_to_the_panel(): void
    {
        $reseller = Reseller::factory()->pending()->create();
        $user = User::factory()->withoutTwoFactor()->forReseller($reseller)->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('portal.dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);

        // O painel e o acompanhamento abrem; o negocio do portal continua fechado.
        $this->get(route('portal.dashboard'))->assertOk();
        $this->get(route('site.solicitacao.status', $reseller))->assertOk();
        $this->get(route('portal.pedidos.index'))->assertForbidden();
    }

    /**
     * Regra 2 · `awaiting_info` e um pre-cadastro em curso: mesmo destino.
     *
     * O estado nasce da acao "Solicitar informacoes adicionais" do Master (3.11)
     * e e onde o painel abre o reenvio de documentos.
     */
    public function test_reseller_awaiting_information_is_routed_to_the_panel(): void
    {
        $reseller = Reseller::factory()->create(['status' => Reseller::STATUS_AWAITING_INFO]);
        $user = User::factory()->withoutTwoFactor()->forReseller($reseller)->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('portal.dashboard', absolute: false));

        $this->get(route('portal.dashboard'))->assertOk();
        $this->get(route('portal.financeiro.index'))->assertForbidden();
    }
}
