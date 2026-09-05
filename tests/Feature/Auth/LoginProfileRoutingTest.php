<?php

/*
[Modulo: tests/Feature/Auth]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 0: prova o roteamento por perfil do login unico e fixa o ponto onde o pre-cadastro ainda para no destino errado.
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
     * Regra 3 · Reprovado e inativo nao entram no portal.
     *
     * @param  Reseller::STATUS_*  $status
     */
    #[DataProvider('statusSemAcessoAoPortal')]
    public function test_rejected_or_inactive_reseller_never_reaches_the_portal(string $status): void
    {
        $reseller = Reseller::factory()->create(['status' => $status]);
        $user = User::factory()->withoutTwoFactor()->forReseller($reseller)->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('site.solicitacao.status', $reseller, absolute: false));

        // A porta do portal fica fechada mesmo com a sessao aberta.
        $this->get(route('portal.dashboard'))->assertForbidden();
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
     * Regra 2 · Pre-cadastro acompanha a propria solicitacao.
     *
     * A lacuna que este teste registrava foi fechada: o LoginResponse passou a
     * encaminhar quem tem protocolo para `/solicitacao/{protocol}`, que e o
     * destino que a regra 2 da tela 0 promete. A regra 2 da tela 1.6 completa o
     * contrato — o pre-cadastro acessa SOMENTE o proprio acompanhamento.
     */
    public function test_pending_reseller_is_routed_to_its_own_request(): void
    {
        $reseller = Reseller::factory()->pending()->create();
        $user = User::factory()->withoutTwoFactor()->forReseller($reseller)->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('site.solicitacao.status', $reseller, absolute: false));

        $this->assertAuthenticatedAs($user);

        // O acompanhamento abre; o portal continua fechado.
        $this->get(route('site.solicitacao.status', $reseller))->assertOk();
        $this->get(route('portal.dashboard'))->assertForbidden();
    }

    /**
     * Regra 2 · `awaiting_info` e um pre-cadastro em curso: mesmo destino.
     *
     * O estado nasce da acao "Solicitar informacoes adicionais" do Master (3.11)
     * e e onde a tela 1.6 abre o reenvio de documentos.
     */
    public function test_reseller_awaiting_information_is_routed_to_its_own_request(): void
    {
        $reseller = Reseller::factory()->create(['status' => Reseller::STATUS_AWAITING_INFO]);
        $user = User::factory()->withoutTwoFactor()->forReseller($reseller)->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('site.solicitacao.status', $reseller, absolute: false));

        $this->get(route('portal.dashboard'))->assertForbidden();
    }
}
