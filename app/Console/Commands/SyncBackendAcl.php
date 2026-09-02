<?php

namespace App\Console\Commands;

use App\Models\AclPermission;
use App\Models\AclResponsibility;
use App\Models\User;
use App\Support\Acl\BackendAclCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class SyncBackendAcl extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'acl:sync-backend';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza catálogo ACL do backend e aplica backfill para admins.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $timestamp = now();
        $permissionDefinitions = BackendAclCatalog::permissions();

        $permissionRows = array_map(static fn (array $permission): array => [
            'key' => $permission['key'],
            'module' => $permission['module'],
            'label' => $permission['label'],
            'description' => $permission['description'],
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ], $permissionDefinitions);

        AclPermission::query()->upsert(
            $permissionRows,
            ['key'],
            ['module', 'label', 'description', 'updated_at'],
        );

        $permissionIdsByKey = AclPermission::query()
            ->pluck('id', 'key')
            ->mapWithKeys(static fn ($id, $key): array => [$key => (int) $id]);

        $responsibilityDefinitions = BackendAclCatalog::responsibilities();
        $responsibilityRows = array_map(static fn (array $responsibility): array => [
            'key' => $responsibility['key'],
            'name' => $responsibility['name'],
            'description' => $responsibility['description'],
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ], $responsibilityDefinitions);

        AclResponsibility::query()->upsert(
            $responsibilityRows,
            ['key'],
            ['name', 'description', 'updated_at'],
        );

        $responsibilitiesByKey = AclResponsibility::query()
            ->whereIn('key', Arr::pluck($responsibilityDefinitions, 'key'))
            ->get()
            ->keyBy('key');

        foreach ($responsibilityDefinitions as $definition) {
            /** @var AclResponsibility|null $responsibility */
            $responsibility = $responsibilitiesByKey->get($definition['key']);

            if (! $responsibility instanceof AclResponsibility) {
                continue;
            }

            $permissionIds = array_values(array_filter(
                array_map(static fn (string $permissionKey): ?int => $permissionIdsByKey->get($permissionKey), $definition['permissions']),
            ));

            $responsibility->permissions()->sync($permissionIds);
        }

        /** @var AclResponsibility|null $adminResponsibility */
        $adminResponsibility = $responsibilitiesByKey->get(BackendAclCatalog::ADMIN_RESPONSIBILITY_KEY);
        /** @var AclResponsibility|null $aclManagerResponsibility */
        $aclManagerResponsibility = $responsibilitiesByKey->get(BackendAclCatalog::ACL_MANAGER_RESPONSIBILITY_KEY);

        if ($adminResponsibility instanceof AclResponsibility && $aclManagerResponsibility instanceof AclResponsibility) {
            User::query()
                ->where('is_admin', true)
                ->orderBy('id')
                ->chunkById(200, function ($users) use ($adminResponsibility, $aclManagerResponsibility, $timestamp): void {
                    foreach ($users as $user) {
                        $user->responsibilities()->syncWithoutDetaching([
                            $adminResponsibility->id => [
                                'assigned_by' => null,
                                'assigned_at' => $timestamp,
                            ],
                            $aclManagerResponsibility->id => [
                                'assigned_by' => null,
                                'assigned_at' => $timestamp,
                            ],
                        ]);
                    }
                });
        }

        $this->info('ACL do backend sincronizada com sucesso.');

        return self::SUCCESS;
    }
}
