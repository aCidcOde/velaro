@props(['title' => null])

@php
    $user = auth()->user();
    $managementOpen = request()->routeIs('backend.dashboard')
        || request()->routeIs('backend.users.*')
        || request()->routeIs('backend.customers.*')
        || request()->routeIs('backend.products.*')
        || request()->routeIs('backend.orders.*')
        || request()->routeIs('backend.audit-logs.*')
        || request()->routeIs('backend.changelog.*');
    $workspaceOpen = request()->routeIs('profile.edit')
        || request()->routeIs('user-password.edit')
        || request()->routeIs('two-factor.show')
        || request()->routeIs('appearance.edit');

    $navigationGroups = [
        [
            'heading' => 'Gestão',
            'items' => [
                [
                    'label' => 'Governança',
                    'icon' => 'ti ti-layout-dashboard',
                    'active' => $managementOpen,
                    'open' => $managementOpen,
                    'children' => [
                        ['label' => 'Dashboard', 'route' => route('backend.dashboard'), 'active' => request()->routeIs('backend.dashboard')],
                        ['label' => 'Usuários', 'route' => route('backend.users.index'), 'active' => request()->routeIs('backend.users.*')],
                        ['label' => 'Clientes', 'route' => route('backend.customers.index'), 'active' => request()->routeIs('backend.customers.*')],
                        ['label' => 'Produtos', 'route' => route('backend.products.index'), 'active' => request()->routeIs('backend.products.*')],
                        ['label' => 'Pedidos', 'route' => route('backend.orders.index'), 'active' => request()->routeIs('backend.orders.*')],
                        ['label' => 'Auditoria', 'route' => route('backend.audit-logs.index'), 'active' => request()->routeIs('backend.audit-logs.*')],
                        ['label' => 'Changelog', 'route' => route('backend.changelog.index'), 'active' => request()->routeIs('backend.changelog.*')],
                    ],
                ],
            ],
        ],
        [
            'heading' => 'Workspace',
            'items' => array_values(array_filter([
                [
                    'label' => 'Front do cliente',
                    'route' => route('dashboard'),
                    'icon' => 'ti ti-layout-columns',
                    'active' => request()->routeIs('dashboard'),
                ],
                [
                    'label' => 'Configurações',
                    'icon' => 'ti ti-settings',
                    'active' => $workspaceOpen,
                    'open' => $workspaceOpen,
                    'children' => [
                        ['label' => 'Perfil', 'route' => route('profile.edit'), 'active' => request()->routeIs('profile.edit')],
                        ['label' => 'Senha', 'route' => route('user-password.edit'), 'active' => request()->routeIs('user-password.edit')],
                        ['label' => '2FA', 'route' => route('two-factor.show'), 'active' => request()->routeIs('two-factor.show')],
                        ['label' => 'Aparência', 'route' => route('appearance.edit'), 'active' => request()->routeIs('appearance.edit')],
                    ],
                ],
                $user?->is_agent ? [
                    'label' => 'Agente',
                    'route' => route('agent.dashboard'),
                    'icon' => 'ti ti-sparkles',
                    'active' => request()->routeIs('agent.*'),
                ] : null,
            ])),
        ],
    ];

    $notifications = [
        [
            'title' => 'Auditoria ativa',
            'text' => 'Revise ações recentes e mantenha governança visível para o time.',
            'route' => route('backend.audit-logs.index'),
            'icon' => 'ti ti-shield-search',
        ],
        [
            'title' => 'Pedidos da base',
            'text' => 'Acompanhe o consolidado de pedidos e a saúde operacional do scaffold.',
            'route' => route('backend.orders.index'),
            'icon' => 'ti ti-package',
        ],
        [
            'title' => 'Usuários e acessos',
            'text' => 'Controle contas, permissões e readiness administrativa num só lugar.',
            'route' => route('backend.users.index'),
            'icon' => 'ti ti-users-group',
        ],
    ];
@endphp

<x-panel.shell
    :title="$title ?? 'Backend'"
    eyebrow="Painel Administrativo"
    brand-context="Backend"
    :brand-href="route('backend.dashboard')"
    search-placeholder="Busque usuários, clientes, pedidos ou auditoria..."
    :navigation-groups="$navigationGroups"
    :sidebar-footer-action="['label' => 'Voltar ao front', 'route' => route('dashboard'), 'icon' => 'ti ti-arrow-left']"
    :notifications="$notifications"
>
    {{ $slot }}
</x-panel.shell>
