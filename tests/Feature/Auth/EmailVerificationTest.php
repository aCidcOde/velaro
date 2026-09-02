<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertStatus(200)
            ->assertSee('Reenviar e-mail de verificação')
            ->assertSee('Sair');
    }

    public function test_email_can_be_verified_without_being_logged_in(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.guest.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $response = $this->get($verificationUrl);

        Event::assertDispatched(Verified::class);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_can_be_verified_behind_an_https_terminating_proxy_without_being_logged_in(): void
    {
        URL::forceRootUrl('https://base-saas.test');
        URL::forceScheme('https');

        try {
            $user = User::factory()->unverified()->create();

            Event::fake();

            $verificationUrl = URL::temporarySignedRoute(
                'verification.guest.verify',
                now()->addMinutes(60),
                ['id' => $user->id, 'hash' => sha1($user->email)],
            );

            $proxiedVerificationUrl = str_replace('https://', 'http://', $verificationUrl);

            $response = $this
                ->withServerVariables([
                    'REMOTE_ADDR' => '10.0.0.1',
                    'HTTP_HOST' => 'base-saas.test',
                    'HTTP_X_FORWARDED_PROTO' => 'https',
                    'HTTP_X_FORWARDED_PORT' => '443',
                ])
                ->get($proxiedVerificationUrl);

            Event::assertDispatched(Verified::class);

            $this->assertTrue($user->fresh()->hasVerifiedEmail());
            $this->assertAuthenticatedAs($user);
            $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
        } finally {
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme(parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'http');
        }
    }

    public function test_verification_email_can_be_resent(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect();

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_expired_verification_link_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.guest.verify',
            now()->subMinute(),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->get($verificationUrl)->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.guest.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')],
        );

        $this->get($verificationUrl)->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_already_verified_user_visiting_verification_link_is_redirected_without_firing_event_again(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.guest.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->get($verificationUrl)
            ->assertRedirect(route('dashboard', absolute: false).'?verified=1');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertAuthenticatedAs($user);
        Event::assertNotDispatched(Verified::class);
    }

    public function test_unverified_user_is_redirected_from_dashboard_and_settings(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_blocked_user_cannot_verify_email_from_guest_link(): void
    {
        $user = User::factory()->unverified()->blocked()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.guest.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->get($verificationUrl)
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
