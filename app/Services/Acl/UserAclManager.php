<?php

namespace App\Services\Acl;

use App\Models\AclPermission;
use App\Models\AclUserPermissionOverride;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserAclManager
{
    public function __construct(private readonly BackendAclService $backendAclService) {}

    /**
     * @param  array<int, mixed>  $responsibilityIds
     * @param  array<int, mixed>  $permissionIds
     */
    public function syncUserAcl(User $user, array $responsibilityIds, array $permissionIds, ?int $actorId = null): void
    {
        $normalizedResponsibilityIds = $this->normalizeIds($responsibilityIds);
        $normalizedPermissionIds = $this->normalizeIds($permissionIds);

        DB::transaction(function () use ($user, $normalizedResponsibilityIds, $normalizedPermissionIds, $actorId): void {
            $timestamp = now();

            $responsibilitySyncData = [];

            foreach ($normalizedResponsibilityIds as $responsibilityId) {
                $responsibilitySyncData[$responsibilityId] = [
                    'assigned_by' => $actorId,
                    'assigned_at' => $timestamp,
                ];
            }

            $user->responsibilities()->sync($responsibilitySyncData);

            $inheritedPermissionIds = AclPermission::query()
                ->select('acl_permissions.id')
                ->join('acl_responsibility_permission', 'acl_permissions.id', '=', 'acl_responsibility_permission.permission_id')
                ->whereIn('acl_responsibility_permission.responsibility_id', $normalizedResponsibilityIds)
                ->pluck('acl_permissions.id')
                ->map(static fn ($permissionId): int => (int) $permissionId)
                ->unique()
                ->values()
                ->all();

            $inheritedLookup = array_fill_keys($inheritedPermissionIds, true);
            $selectedLookup = array_fill_keys($normalizedPermissionIds, true);

            $allowOverrides = [];

            foreach ($normalizedPermissionIds as $permissionId) {
                if (! isset($inheritedLookup[$permissionId])) {
                    $allowOverrides[] = $permissionId;
                }
            }

            $denyOverrides = [];

            foreach ($inheritedPermissionIds as $permissionId) {
                if (! isset($selectedLookup[$permissionId])) {
                    $denyOverrides[] = $permissionId;
                }
            }

            AclUserPermissionOverride::query()
                ->where('user_id', $user->id)
                ->delete();

            $rows = [];

            foreach ($allowOverrides as $permissionId) {
                $rows[] = [
                    'user_id' => $user->id,
                    'permission_id' => $permissionId,
                    'is_allowed' => true,
                    'assigned_by' => $actorId,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            foreach ($denyOverrides as $permissionId) {
                $rows[] = [
                    'user_id' => $user->id,
                    'permission_id' => $permissionId,
                    'is_allowed' => false,
                    'assigned_by' => $actorId,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            if ($rows !== []) {
                AclUserPermissionOverride::query()->insert($rows);
            }
        });

        $user->unsetRelation('responsibilities');
        $user->unsetRelation('permissionOverrides');
    }

    /**
     * @return array<int, int>
     */
    public function effectivePermissionIds(User $user): array
    {
        $effectiveKeys = $this->backendAclService->effectivePermissionKeys($user);

        if ($effectiveKeys === []) {
            return [];
        }

        return AclPermission::query()
            ->whereIn('key', $effectiveKeys)
            ->pluck('id')
            ->map(static fn ($permissionId): int => (int) $permissionId)
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $ids
     * @return array<int, int>
     */
    private function normalizeIds(array $ids): array
    {
        $normalizedIds = array_map(static fn ($id): int => (int) $id, $ids);
        $normalizedIds = array_filter($normalizedIds, static fn (int $id): bool => $id > 0);
        $normalizedIds = array_values(array_unique($normalizedIds));
        sort($normalizedIds);

        return $normalizedIds;
    }
}
