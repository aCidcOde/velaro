<?php

namespace Tests\Feature;

use App\Models\AclPermission;
use App\Models\AclResponsibility;
use App\Models\AclUserPermissionOverride;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackendUserAclManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_generic_user_flags_and_document(): void
    {
        $admin = $this->createBackendAdmin();
        $user = User::factory()->create([
            'is_admin' => false,
            'is_agent' => false,
            'is_blocked' => false,
        ]);

        $response = $this->actingAs($admin)->put(route('backend.users.update', $user), [
            'name' => 'Operador Base',
            'email' => 'operador@example.com',
            'phone' => '(11) 97777-1111',
            'document' => 'DOC-OPER-001',
            'is_admin' => '1',
            'is_agent' => '1',
            'is_blocked' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('user_success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Operador Base',
            'email' => 'operador@example.com',
            'phone' => '(11) 97777-1111',
            'document' => 'DOCOPER001',
            'is_admin' => true,
            'is_agent' => true,
            'is_blocked' => false,
        ]);
    }

    public function test_admin_can_assign_acl_responsibilities_and_permission_overrides(): void
    {
        $target = User::factory()->create();
        $admin = $this->createBackendAdmin();

        $productResponsibility = AclResponsibility::query()
            ->where('key', 'backend.produtos')
            ->firstOrFail();

        $permissionIds = AclPermission::query()
            ->whereIn('key', [
                'backend.admin.access',
                'backend.products.view',
                'backend.products.create',
                'backend.products.update',
                'backend.orders.view',
            ])
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $response = $this->actingAs($admin)->put(route('backend.users.acl.update', $target), [
            'responsibility_ids' => [$productResponsibility->id],
            'permission_ids' => $permissionIds,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('user_success');

        $target->refresh();

        $this->assertTrue($target->responsibilities()->whereKey($productResponsibility->id)->exists());
        $this->assertTrue($target->hasBackendPermission('backend.products.view'));
        $this->assertTrue($target->hasBackendPermission('backend.products.create'));
        $this->assertTrue($target->hasBackendPermission('backend.orders.view'));
        $this->assertFalse($target->hasBackendPermission('backend.users.view'));

        $ordersViewPermissionId = AclPermission::query()
            ->where('key', 'backend.orders.view')
            ->value('id');

        $this->assertDatabaseHas((new AclUserPermissionOverride)->getTable(), [
            'user_id' => $target->id,
            'permission_id' => $ordersViewPermissionId,
            'is_allowed' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('backend.users.show', ['user' => $target, 'tab' => 'permissoes']))
            ->assertOk()
            ->assertSee('Produtos Backend')
            ->assertSee('Pedidos');
    }
}
