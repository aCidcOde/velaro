<?php

namespace App\Support\Acl;

final class BackendAclCatalog
{
    public const string BACKEND_ACCESS_PERMISSION = 'backend.admin.access';

    public const string ADMIN_RESPONSIBILITY_KEY = 'backend.admin';

    public const string ACL_MANAGER_RESPONSIBILITY_KEY = 'backend.acl-manager';

    /**
     * @return array<string, array{label: string, description: string}>
     */
    public static function moduleDefinitions(): array
    {
        return [
            'users' => [
                'label' => 'Usuários',
                'description' => 'Cadastro, atualização e permissões de usuários.',
            ],
            'customers' => [
                'label' => 'Clientes',
                'description' => 'Gestão de clientes da base.',
            ],
            'products' => [
                'label' => 'Produtos',
                'description' => 'Gestão de catálogo de produtos por usuário.',
            ],
            'orders' => [
                'label' => 'Pedidos',
                'description' => 'Consulta e operação manual dos pedidos e itens.',
            ],
            'auditing' => [
                'label' => 'Auditoria',
                'description' => 'Logs do sistema, changelog e monitoramento de rotinas.',
            ],
            'agent' => [
                'label' => 'CodaFácil IA',
                'description' => 'Conversa, uploads e filas do módulo CodaFácil IA.',
            ],
            'dashboard' => [
                'label' => 'Dashboard',
                'description' => 'Acesso ao painel administrativo inicial.',
            ],
        ];
    }

    /**
     * @return array<int, array{key: string, module: string, label: string, description: string}>
     */
    public static function permissions(): array
    {
        return [
            ['key' => 'backend.admin.access', 'module' => 'dashboard', 'label' => 'Acessar backend', 'description' => 'Permite entrar no painel administrativo.'],
            ['key' => 'backend.dashboard.view', 'module' => 'dashboard', 'label' => 'Visualizar dashboard', 'description' => 'Permite visualizar o dashboard do backend.'],

            ['key' => 'backend.audit-logs.view', 'module' => 'auditing', 'label' => 'Visualizar logs de auditoria', 'description' => 'Permite acessar os logs de auditoria.'],
            ['key' => 'backend.changelog.view', 'module' => 'auditing', 'label' => 'Visualizar changelog', 'description' => 'Permite acessar o changelog do backend.'],
            ['key' => 'backend.agent-jobs.view', 'module' => 'agent', 'label' => 'Visualizar filas do agente', 'description' => 'Permite acompanhar o processamento do módulo CodaFácil IA.'],

            ['key' => 'backend.users.view', 'module' => 'users', 'label' => 'Visualizar usuários', 'description' => 'Permite listar e visualizar usuários no backend.'],
            ['key' => 'backend.users.update', 'module' => 'users', 'label' => 'Atualizar usuários', 'description' => 'Permite editar dados do usuário.'],
            ['key' => 'backend.users.permissions.manage', 'module' => 'users', 'label' => 'Gerenciar permissões de usuário', 'description' => 'Permite editar responsabilidades e permissões por usuário.'],

            ['key' => 'backend.customers.view', 'module' => 'customers', 'label' => 'Visualizar clientes', 'description' => 'Permite listar clientes no backend.'],
            ['key' => 'backend.customers.create', 'module' => 'customers', 'label' => 'Criar clientes', 'description' => 'Permite criar clientes no backend.'],
            ['key' => 'backend.customers.update', 'module' => 'customers', 'label' => 'Atualizar clientes', 'description' => 'Permite editar clientes no backend.'],

            ['key' => 'backend.products.view', 'module' => 'products', 'label' => 'Visualizar produtos', 'description' => 'Permite listar produtos no backend.'],
            ['key' => 'backend.products.create', 'module' => 'products', 'label' => 'Criar produtos', 'description' => 'Permite criar produtos no backend.'],
            ['key' => 'backend.products.update', 'module' => 'products', 'label' => 'Atualizar produtos', 'description' => 'Permite editar produtos no backend.'],

            ['key' => 'backend.orders.view', 'module' => 'orders', 'label' => 'Visualizar pedidos', 'description' => 'Permite listar e detalhar pedidos no backend.'],
            ['key' => 'backend.orders.create', 'module' => 'orders', 'label' => 'Criar pedidos', 'description' => 'Permite criar pedidos no backend.'],
            ['key' => 'backend.orders.update', 'module' => 'orders', 'label' => 'Atualizar pedidos', 'description' => 'Permite atualizar pedidos no backend.'],
            ['key' => 'backend.orders.item-status.update', 'module' => 'orders', 'label' => 'Atualizar status de item', 'description' => 'Permite alterar status manual de item de pedido.'],
        ];
    }

    /**
     * @return array<int, array{key: string, name: string, description: string, permissions: array<int, string>}>
     */
    public static function responsibilities(): array
    {
        $allPermissionKeys = array_map(static fn (array $permission): string => $permission['key'], self::permissions());

        return [
            [
                'key' => self::ADMIN_RESPONSIBILITY_KEY,
                'name' => 'Admin Backend',
                'description' => 'Acesso completo a todos os módulos do backend.',
                'permissions' => $allPermissionKeys,
            ],
            [
                'key' => self::ACL_MANAGER_RESPONSIBILITY_KEY,
                'name' => 'Gestor ACL',
                'description' => 'Permite gerenciar permissões de usuários no backend.',
                'permissions' => [
                    'backend.admin.access',
                    'backend.dashboard.view',
                    'backend.users.view',
                    'backend.users.permissions.manage',
                ],
            ],
            [
                'key' => 'backend.dashboard',
                'name' => 'Dashboard Backend',
                'description' => 'Acesso apenas ao dashboard administrativo.',
                'permissions' => [
                    'backend.admin.access',
                    'backend.dashboard.view',
                ],
            ],
            [
                'key' => 'backend.auditoria',
                'name' => 'Auditoria Backend',
                'description' => 'Acesso ao módulo de auditoria e monitoramento.',
                'permissions' => [
                    'backend.admin.access',
                    'backend.audit-logs.view',
                    'backend.changelog.view',
                    'backend.agent-jobs.view',
                ],
            ],
            [
                'key' => 'backend.usuarios',
                'name' => 'Usuários Backend',
                'description' => 'Acesso ao módulo de usuários.',
                'permissions' => [
                    'backend.admin.access',
                    'backend.users.view',
                    'backend.users.update',
                ],
            ],
            [
                'key' => 'backend.clientes',
                'name' => 'Clientes Backend',
                'description' => 'Acesso ao módulo de clientes.',
                'permissions' => [
                    'backend.admin.access',
                    'backend.customers.view',
                    'backend.customers.create',
                    'backend.customers.update',
                ],
            ],
            [
                'key' => 'backend.produtos',
                'name' => 'Produtos Backend',
                'description' => 'Acesso ao módulo de produtos.',
                'permissions' => [
                    'backend.admin.access',
                    'backend.products.view',
                    'backend.products.create',
                    'backend.products.update',
                ],
            ],
            [
                'key' => 'backend.pedidos',
                'name' => 'Pedidos Backend',
                'description' => 'Acesso ao módulo de pedidos e operações manuais.',
                'permissions' => [
                    'backend.admin.access',
                    'backend.orders.view',
                    'backend.orders.create',
                    'backend.orders.update',
                    'backend.orders.item-status.update',
                ],
            ],
        ];
    }
}
