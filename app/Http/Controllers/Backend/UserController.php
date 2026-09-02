<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\UserAclUpdateRequest;
use App\Http\Requests\Backend\UserUpdateRequest;
use App\Models\AclPermission;
use App\Models\AclResponsibility;
use App\Models\User;
use App\Services\Acl\UserAclManager;
use App\Services\AdminAuditLogger;
use App\Support\Acl\BackendAclCatalog;
use App\Support\UserAccessRevoker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim();
        $status = $request->string('status')->trim()->toString();
        $allowedStatuses = ['active', 'blocked', 'agent', 'admin'];

        if (! in_array($status, $allowedStatuses, true)) {
            $status = '';
        }

        $searchLike = '%'.Str::lower($search->toString()).'%';

        $users = User::query()
            ->withCount('orders')
            ->when($search->isNotEmpty(), function ($query) use ($searchLike): void {
                $query->where(function ($subQuery) use ($searchLike): void {
                    $subQuery->whereRaw('LOWER(name) LIKE ?', [$searchLike])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$searchLike]);
                });
            })
            ->when($status !== '', function ($query) use ($status): void {
                if ($status === 'blocked') {
                    $query->where('is_blocked', true);

                    return;
                }

                if ($status === 'active') {
                    $query->has('orders');

                    return;
                }

                if ($status === 'agent') {
                    $query->where('is_agent', true);

                    return;
                }

                $query->where('is_admin', true);
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('backend.users.index', [
            'users' => $users,
            'search' => $search->toString(),
            'status' => $status,
        ]);
    }

    public function show(Request $request, User $user): View
    {
        $canUpdateUser = (bool) $request->user()?->hasBackendPermission('backend.users.update');
        $canManageAcl = (bool) $request->user()?->hasBackendPermission('backend.users.permissions.manage');

        $availableTabs = ['dados', 'pedidos'];

        if ($canManageAcl) {
            $availableTabs[] = 'permissoes';
        }

        $tab = $request->string('tab', 'dados')->toString();

        if (! in_array($tab, $availableTabs, true)) {
            $tab = 'dados';
        }

        $orders = $user->orders()
            ->latest()
            ->paginate(10, ['*'], 'orders')
            ->withQueryString();

        $aclData = null;

        if ($canManageAcl) {
            $aclData = $this->buildAclData($user);
        }

        return view('backend.users.show', [
            'user' => $user,
            'tab' => $tab,
            'availableTabs' => $availableTabs,
            'orders' => $orders,
            'customersCount' => $user->customers()->count(),
            'productsCount' => $user->products()->count(),
            'canUpdateUser' => $canUpdateUser,
            'canManageAcl' => $canManageAcl,
            'aclData' => $aclData,
        ]);
    }

    public function update(UserUpdateRequest $request, User $user, UserAccessRevoker $userAccessRevoker): RedirectResponse
    {
        $data = $request->validated();

        $data['is_admin'] = $request->boolean('is_admin');
        $data['is_agent'] = $request->boolean('is_agent');
        $data['is_blocked'] = $data['is_admin'] ? false : $request->boolean('is_blocked');

        $wasBlocked = $user->isBlocked();
        $shouldRevokeAccess = $data['is_blocked'] && ! $wasBlocked;

        $before = Arr::only($user->getAttributes(), array_keys($data));

        $user->forceFill($data)->save();

        if ($shouldRevokeAccess) {
            $userAccessRevoker->revoke($user);
        }

        $user->refresh();

        $after = Arr::only($user->getAttributes(), array_keys($data));

        app(AdminAuditLogger::class)->log('user.updated', $user, $before, $after);

        return back()->with('user_success', __('Dados do usuario atualizados.'));
    }

    public function updateAcl(UserAclUpdateRequest $request, User $user, UserAclManager $userAclManager): RedirectResponse
    {
        $data = $request->validated();

        $before = [
            'responsibility_ids' => $user->responsibilities()
                ->pluck('acl_responsibilities.id')
                ->map(static fn ($id): int => (int) $id)
                ->sort()
                ->values()
                ->all(),
            'effective_permissions' => $user->effectiveBackendPermissions(),
        ];

        $userAclManager->syncUserAcl(
            $user,
            $data['responsibility_ids'] ?? [],
            $data['permission_ids'] ?? [],
            auth()->id(),
        );

        $user->refresh();

        $after = [
            'responsibility_ids' => $user->responsibilities()
                ->pluck('acl_responsibilities.id')
                ->map(static fn ($id): int => (int) $id)
                ->sort()
                ->values()
                ->all(),
            'effective_permissions' => $user->effectiveBackendPermissions(),
        ];

        app(AdminAuditLogger::class)->log('user.acl.updated', $user, $before, $after);

        return back()->with('user_success', __('Permissões atualizadas com sucesso.'));
    }

    /**
     * @return array{
     *     responsibilities: array<int, array{id: int, key: string, name: string, description: string, permission_count: int, selected: bool}>,
     *     modules: array<int, array{key: string, label: string, description: string, permissions: array<int, array{id: int, key: string, label: string, description: string, selected: bool}>}>,
     *     selected_permission_ids: array<int, int>,
     *     selected_responsibility_ids: array<int, int>
     * }
     */
    private function buildAclData(User $user): array
    {
        $moduleDefinitions = BackendAclCatalog::moduleDefinitions();
        $moduleOrder = array_flip(array_keys($moduleDefinitions));

        $permissionCollection = AclPermission::query()
            ->orderBy('module')
            ->orderBy('key')
            ->get();

        $selectedResponsibilityIds = $user->responsibilities()
            ->pluck('acl_responsibilities.id')
            ->map(static fn ($id): int => (int) $id)
            ->sort()
            ->values()
            ->all();
        $selectedPermissionKeys = array_fill_keys($user->effectiveBackendPermissions(), true);
        $selectedPermissionIds = $permissionCollection
            ->filter(static fn (AclPermission $permission): bool => isset($selectedPermissionKeys[$permission->key]))
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->sort()
            ->values()
            ->all();

        $responsibilityOrder = [];

        foreach (BackendAclCatalog::responsibilities() as $index => $responsibilityDefinition) {
            $responsibilityOrder[$responsibilityDefinition['key']] = $index;
        }

        $responsibilities = AclResponsibility::query()
            ->with('permissions:id,key')
            ->get()
            ->sortBy(static function (AclResponsibility $responsibility) use ($responsibilityOrder): int {
                return $responsibilityOrder[$responsibility->key] ?? 9_999;
            })
            ->map(static function (AclResponsibility $responsibility) use ($selectedResponsibilityIds): array {
                return [
                    'id' => $responsibility->id,
                    'key' => $responsibility->key,
                    'name' => $responsibility->name,
                    'description' => (string) ($responsibility->description ?? ''),
                    'permission_count' => $responsibility->permissions->count(),
                    'selected' => in_array($responsibility->id, $selectedResponsibilityIds, true),
                ];
            })
            ->values()
            ->all();

        $permissionsByModule = [];

        foreach ($moduleDefinitions as $moduleKey => $moduleDefinition) {
            $permissionsByModule[$moduleKey] = [
                'key' => $moduleKey,
                'label' => $moduleDefinition['label'],
                'description' => $moduleDefinition['description'],
                'permissions' => [],
            ];
        }

        foreach ($permissionCollection as $permission) {
            if (! isset($permissionsByModule[$permission->module])) {
                $permissionsByModule[$permission->module] = [
                    'key' => $permission->module,
                    'label' => Str::headline($permission->module),
                    'description' => '',
                    'permissions' => [],
                ];
            }

            $permissionsByModule[$permission->module]['permissions'][] = [
                'id' => (int) $permission->id,
                'key' => $permission->key,
                'label' => $permission->label,
                'description' => (string) ($permission->description ?? ''),
                'selected' => isset($selectedPermissionKeys[$permission->key]),
            ];
        }

        $modules = collect($permissionsByModule)
            ->sortBy(static function (array $moduleData) use ($moduleOrder): int {
                return $moduleOrder[$moduleData['key']] ?? 9_999;
            })
            ->filter(static fn (array $moduleData): bool => $moduleData['permissions'] !== [])
            ->values()
            ->all();

        return [
            'responsibilities' => $responsibilities,
            'modules' => $modules,
            'selected_permission_ids' => $selectedPermissionIds,
            'selected_responsibility_ids' => $selectedResponsibilityIds,
        ];
    }
}
