<?php

namespace App\Services\Acl;

use App\Models\AclPermission;
use App\Models\AclResponsibility;
use App\Models\AclUserPermissionOverride;
use App\Models\User;
use App\Support\Acl\BackendAclCatalog;
use Illuminate\Database\Eloquent\Collection;

class BackendAclService
{
    /**
     * @return array<int, string>
     */
    public function effectivePermissionKeys(User $user): array
    {
        $user->loadMissing([
            'responsibilities.permissions:id,key',
            'permissionOverrides.permission:id,key',
        ]);

        /** @var Collection<int, AclResponsibility> $responsibilities */
        $responsibilities = $user->responsibilities;
        /** @var Collection<int, AclUserPermissionOverride> $permissionOverrides */
        $permissionOverrides = $user->permissionOverrides;
        $allowedPermissions = [];

        foreach ($responsibilities as $responsibility) {
            /** @var Collection<int, AclPermission> $permissions */
            $permissions = $responsibility->permissions;

            foreach ($permissions as $permission) {
                $allowedPermissions[$permission->key] = true;
            }
        }

        foreach ($permissionOverrides as $override) {
            $permission = $override->permission;
            $permissionKey = $permission instanceof AclPermission ? $permission->key : null;

            if (! is_string($permissionKey) || $permissionKey === '') {
                continue;
            }

            if ($override->is_allowed) {
                $allowedPermissions[$permissionKey] = true;

                continue;
            }

            unset($allowedPermissions[$permissionKey]);
        }

        $keys = array_keys($allowedPermissions);
        sort($keys);

        return $keys;
    }

    public function userHasPermission(User $user, string $permissionKey): bool
    {
        return in_array($permissionKey, $this->effectivePermissionKeys($user), true);
    }

    public function canAccessBackend(User $user): bool
    {
        if (! $user->is_admin) {
            return false;
        }

        return $this->userHasPermission($user, BackendAclCatalog::BACKEND_ACCESS_PERMISSION);
    }
}
