<?php

namespace Tests\Feature\Auth;

use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200)
            ->assertSee('Documento')
            ->assertDontSee('data-mask="cpf"', false);
    }

    public function test_new_users_can_register(): void
    {
        Mail::fake();

        $response = $this->post(route('register.store'), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'phone' => '(11) 99999-0000',
            'document' => 'DOC-2026/BASE',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('verification.notice', absolute: false));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'phone' => '(11) 99999-0000',
            'document' => 'DOC2026BASE',
        ]);

        Mail::assertSent(WelcomeMail::class, 'test@example.com');
        Mail::assertSent(WelcomeMail::class, function (WelcomeMail $mail): bool {
            $html = $mail->render();

            return (str_contains($html, 'data:image/webp;base64,') || str_contains($html, 'data:image/png;base64,'))
                && str_contains($html, 'alt="'.config('app.name').'"');
        });
    }

    public function test_registration_requires_phone_and_document(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors(['phone', 'document']);
    }

    public function test_registration_rejects_document_longer_than_the_supported_limit(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'phone' => '(11) 99999-0000',
            'document' => str_repeat('A', 31),
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors(['document']);
    }

    public function test_registration_keeps_old_input_when_email_is_already_taken(): void
    {
        User::factory()->create([
            'email' => 'cliente@example.com',
        ]);

        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Cliente Duplicado',
            'email' => 'cliente@example.com',
            'phone' => '(11) 98888-7777',
            'document' => 'DOC-CLIENTE-01',
            'password' => 'SenhaSecreta123',
            'password_confirmation' => 'SenhaSecreta123',
        ]);

        $response->assertRedirect(route('register'))
            ->assertSessionHasErrors(['email']);

        $this->followRedirects($response)
            ->assertOk()
            ->assertSee('value="Cliente Duplicado"', false)
            ->assertSee('value="cliente@example.com"', false)
            ->assertSee('value="(11) 98888-7777"', false)
            ->assertSee('value="DOC-CLIENTE-01"', false)
            ->assertDontSee('SenhaSecreta123', false);
    }

    public function test_registration_keeps_old_input_when_document_is_invalid(): void
    {
        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Cliente Documento',
            'email' => 'documento@example.com',
            'phone' => '(11) 97777-6666',
            'document' => str_repeat('B', 31),
            'password' => 'OutraSenha123',
            'password_confirmation' => 'OutraSenha123',
        ]);

        $response->assertRedirect(route('register'))
            ->assertSessionHasErrors(['document']);

        $this->followRedirects($response)
            ->assertOk()
            ->assertSee('value="Cliente Documento"', false)
            ->assertSee('value="documento@example.com"', false)
            ->assertSee('value="(11) 97777-6666"', false)
            ->assertSee('value="'.str_repeat('B', 31).'"', false)
            ->assertDontSee('OutraSenha123', false);
    }
}
