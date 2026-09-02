<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $this->actingAs($user = User::factory()->create());

        $this->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('data-mask="phone"', false)
            ->assertSee('Documento');
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Volt::test('settings.profile')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('phone', '(11) 99999-0000')
            ->set('document', 'DOC-2026/PERFIL')
            ->call('updateProfileInformation');

        $response->assertHasNoErrors();

        $user->refresh();

        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertEquals('(11) 99999-0000', $user->phone);
        $this->assertEquals('DOC2026PERFIL', $user->document);
        $this->assertNull($user->email_verified_at);
    }

    public function test_profile_update_rejects_document_longer_than_the_supported_limit(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Volt::test('settings.profile')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('document', str_repeat('A', 31))
            ->call('updateProfileInformation');

        $response->assertHasErrors(['document']);
    }

    public function test_email_verification_status_is_unchanged_when_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Volt::test('settings.profile')
            ->set('name', 'Test User')
            ->set('email', $user->email)
            ->call('updateProfileInformation');

        $response->assertHasNoErrors();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Volt::test('settings.delete-user-form')
            ->set('password', 'password')
            ->call('deleteUser');

        $response
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertNull($user->fresh());
        $this->assertFalse(auth()->check());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Volt::test('settings.delete-user-form')
            ->set('password', 'wrong-password')
            ->call('deleteUser');

        $response->assertHasErrors(['password']);

        $this->assertNotNull($user->fresh());
    }
}
