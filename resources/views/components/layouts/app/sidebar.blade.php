@props(['title' => null])

@php
    $user = auth()->user();
    $operationsOpen = request()->routeIs('dashboard')
        || request()->routeIs('customers.*')
        || request()->routeIs('products.*')
        || request()->routeIs('orders.*');
    $settingsOpen = request()->routeIs('profile.edit')
        || request()->routeIs('user-password.edit')
        || request()->routeIs('two-factor.show')
        || request()->routeIs('appearance.edit');

    $navigationGroups = [
        [
            'heading' => 'Menu',
            'items' => [
                [
                    'label' => 'Operação',
                    'icon' => 'ti ti-layout-dashboard',
                    'active' => $operationsOpen,
                    'open' => $operationsOpen,
                    'children' => [
                        ['label' => 'Dashboard', 'route' => route('dashboard'), 'active' => request()->routeIs('dashboard')],
                        ['label' => 'Clientes', 'route' => route('customers.index'), 'active' => request()->routeIs('customers.*')],
                        ['label' => 'Produtos', 'route' => route('products.index'), 'active' => request()->routeIs('products.*')],
                        ['label' => 'Pedidos', 'route' => route('orders.index'), 'active' => request()->routeIs('orders.*')],
                    ],
                ],
                [
                    'label' => 'Configurações',
                    'icon' => 'ti ti-settings',
                    'active' => $settingsOpen,
                    'open' => $settingsOpen,
                    'children' => [
                        ['label' => 'Perfil', 'route' => route('profile.edit'), 'active' => request()->routeIs('profile.edit')],
                        ['label' => 'Senha', 'route' => route('user-password.edit'), 'active' => request()->routeIs('user-password.edit')],
                        ['label' => '2FA', 'route' => route('two-factor.show'), 'active' => request()->routeIs('two-factor.show')],
                        ['label' => 'Aparência', 'route' => route('appearance.edit'), 'active' => request()->routeIs('appearance.edit')],
                    ],
                ],
            ],
        ],
        [
            'heading' => 'Workspace',
            'items' => array_values(array_filter([
                $user?->is_agent ? [
                    'label' => 'Agente',
                    'route' => route('agent.dashboard'),
                    'icon' => 'ti ti-sparkles',
                    'active' => request()->routeIs('agent.*'),
                ] : null,
                $user?->is_admin ? [
                    'label' => 'Backend',
                    'route' => route('backend.dashboard'),
                    'icon' => 'ti ti-shield-lock',
                    'badge' => 'Admin',
                    'active' => request()->routeIs('backend.*'),
                ] : null,
            ])),
        ],
    ];

    $notifications = array_values(array_filter([
        [
            'title' => 'Pedidos em andamento',
            'text' => 'Acompanhe itens recentes e destrave o fluxo operacional mais rápido.',
            'route' => route('orders.index'),
            'icon' => 'ti ti-package',
        ],
        [
            'title' => 'Clientes ativos',
            'text' => 'Revise cadastros e mantenha a base pronta para novos pedidos.',
            'route' => route('customers.index'),
            'icon' => 'ti ti-users',
        ],
        $user?->is_admin ? [
            'title' => 'Área administrativa',
            'text' => 'Acesse governança, auditoria e visão consolidada da operação.',
            'route' => route('backend.dashboard'),
            'icon' => 'ti ti-shield-lock',
        ] : null,
    ]));
@endphp

<x-panel.shell
    :title="$title ?? 'Dashboard'"
    eyebrow="Operação do Cliente"
    brand-context="Painel do Cliente"
    :brand-href="route('dashboard')"
    search-placeholder="Busque clientes, produtos ou pedidos..."
    :navigation-groups="$navigationGroups"
    :sidebar-footer-action="['label' => 'Novo pedido', 'route' => route('orders.create'), 'icon' => 'ti ti-plus']"
    :notifications="$notifications"
>
    {{ $slot }}
</x-panel.shell>
