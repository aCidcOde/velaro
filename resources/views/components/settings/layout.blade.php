@php
    $items = [
        [
            'route' => 'profile.edit',
            'match' => 'profile.edit',
            'label' => __('Perfil'),
            'icon' => 'ti ti-user',
        ],
        [
            'route' => 'user-password.edit',
            'match' => 'user-password.edit',
            'label' => __('Senha'),
            'icon' => 'ti ti-lock',
        ],
    ];

    if (Laravel\Fortify\Features::canManageTwoFactorAuthentication()) {
        $items[] = [
            'route' => 'two-factor.show',
            'match' => 'two-factor.show',
            'label' => __('Autenticação em duas etapas'),
            'icon' => 'ti ti-shield-lock',
        ];
    }

    $items[] = [
        'route' => 'appearance.edit',
        'match' => 'appearance.edit',
        'label' => __('Aparência'),
        'icon' => 'ti ti-brush',
    ];
@endphp

<div class="panel-settings-grid">
    <aside class="panel-settings-nav">
        <nav class="panel-settings-nav__list">
            @foreach ($items as $item)
                @php($active = request()->routeIs($item['match']))
                <a
                    href="{{ route($item['route']) }}"
                    class="panel-settings-link {{ $active ? 'is-active' : '' }}"
                    wire:navigate
                >
                    <i class="{{ $item['icon'] }} text-lg"></i>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </aside>

    <section class="panel-settings-card">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $heading ?? '' }}</h2>
        <p class="mt-2 text-sm leading-7 text-gray-500 dark:text-gray-400">{{ $subheading ?? '' }}</p>

        <div class="mt-6">
            {{ $slot }}
        </div>
    </section>
</div>
