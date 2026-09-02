<?php

namespace Tests\Feature;

use App\Mail\ContactMessageMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_can_be_rendered(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Base pronta para criar novos sistemas');

        $this->get(route('public.about'))
            ->assertOk()
            ->assertSee('Uma fundação única para vários produtos.');

        $this->get(route('public.services'))
            ->assertOk()
            ->assertSee('Módulos prontos para reaproveitar');

        $this->get(route('public.contact'))
            ->assertOk()
            ->assertSee('Fale com a operação do template');
    }

    public function test_contact_form_sends_a_message(): void
    {
        Mail::fake();

        $response = $this->post(route('public.contact.store'), [
            'name' => 'Equipe Comercial',
            'email' => 'contato@example.com',
            'phone' => '(11) 93333-2222',
            'message' => 'Precisamos adaptar esta base para um novo produto.',
        ]);

        $response->assertRedirect(route('public.contact'))
            ->assertSessionHas('status');

        Mail::assertSent(ContactMessageMail::class, function (ContactMessageMail $mail): bool {
            return $mail->name === 'Equipe Comercial'
                && $mail->email === 'contato@example.com'
                && $mail->phone === '(11) 93333-2222';
        });
    }
}
