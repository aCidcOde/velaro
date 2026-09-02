<?php

/*
[Modulo: tests/Feature/Auth]
@Author: André Gomes ( @acidcode )
@since 2026-02-22
Valida o fluxo de login social com Google (redirect e callback).
*/

namespace Tests\Feature\Auth;

use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        try {
            Mockery::close();
        } finally {
            parent::tearDown();
        }
    }

    public function test_guest_is_redirected_to_google_oauth_screen(): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('scopes')
            ->once()
            ->with(['openid', 'profile', 'email'])
            ->andReturnSelf();
        $provider->shouldReceive('redirect')
            ->once()
            ->andReturn(redirect('https://accounts.google.com/o/oauth2/v2/auth'));

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $response = $this->get(route('auth.google.redirect'));

        $response->assertRedirect('https://accounts.google.com/o/oauth2/v2/auth');
    }

    public function test_callback_logs_in_user_linked_by_google_id(): void
    {
        $user = User::factory()->withoutTwoFactor()->create([
            'email' => 'google-user@example.com',
            'google_id' => 'google-123',
        ]);

        $provider = Mockery::mock();
        $provider->shouldReceive('user')
            ->once()
            ->andReturn($this->fakeGoogleUser('google-123', 'google-user@example.com', 'Google User'));

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_callback_rejects_existing_user_found_only_by_email(): void
    {
        $user = User::factory()->withoutTwoFactor()->create([
            'email' => 'existente@example.com',
            'google_id' => null,
        ]);

        $provider = Mockery::mock();
        $provider->shouldReceive('user')
            ->once()
            ->andReturn($this->fakeGoogleUser('google-456', 'existente@example.com', 'Usuário Existente'));

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');
        $this->assertGuest();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'google_id' => null,
        ]);
    }

    public function test_callback_creates_new_user_and_sends_welcome_mail(): void
    {
        Mail::fake();

        $provider = Mockery::mock();
        $provider->shouldReceive('user')
            ->once()
            ->andReturn($this->fakeGoogleUser('google-789', 'novo-google@example.com', 'Novo Google'));

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => 'novo-google@example.com',
            'google_id' => 'google-789',
        ]);

        Mail::assertSent(WelcomeMail::class, 1);
        Mail::assertSent(WelcomeMail::class, function (WelcomeMail $mail): bool {
            return $mail->hasTo('novo-google@example.com');
        });
    }

    private function fakeGoogleUser(string $id, string $email, string $name): SocialiteUser
    {
        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn($id);
        $googleUser->shouldReceive('getEmail')->andReturn($email);
        $googleUser->shouldReceive('getName')->andReturn($name);
        $googleUser->shouldReceive('getNickname')->andReturnNull();

        return $googleUser;
    }
}
