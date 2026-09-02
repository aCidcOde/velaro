<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AppearanceSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_theme_can_be_switched(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'theme_preference' => 'dark']);

        $this->actingAs($user);

        Livewire::test('settings.appearance')
            ->set('theme', 'light')
            ->call('saveTheme')
            ->assertHasNoErrors();

        $this->assertSame('light', $user->fresh()->theme_preference);
    }
}
