<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackendAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_user_cannot_access_backend(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('backend.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_without_acl_cannot_access_backend(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('backend.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_with_synced_acl_can_access_backend_sections(): void
    {
        $admin = $this->createBackendAdmin();

        $this->actingAs($admin)
            ->get(route('backend.dashboard'))
            ->assertOk()
            ->assertSee('Painel administrativo da base');

        $this->actingAs($admin)->get(route('backend.users.index'))->assertOk();
        $this->actingAs($admin)->get(route('backend.customers.index'))->assertOk();
        $this->actingAs($admin)->get(route('backend.products.index'))->assertOk();
        $this->actingAs($admin)->get(route('backend.orders.index'))->assertOk();
        $this->actingAs($admin)->get(route('backend.audit-logs.index'))->assertOk();
        $this->actingAs($admin)->get(route('backend.changelog.index'))->assertOk();
    }
}
