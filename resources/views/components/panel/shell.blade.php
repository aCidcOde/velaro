@props([
    'title' => null,
    'eyebrow' => null,
    'description' => null,
    'brandContext' => null,
    'brandHref' => '/',
    'searchPlaceholder' => 'Search or type command...',
    'navigationGroups' => [],
    'sidebarFooterAction' => null,
    'notifications' => [],
])

@php
    $initialTheme = request()->cookie('theme');
    $resolvedInitialTheme = in_array($initialTheme, ['dark', 'light'], true) ? $initialTheme : null;
    $user = auth()->user();
    $title = $title ?? config('app.name', 'CodaFácil');
    $eyebrow = $eyebrow ?? 'Painel';
@endphp

<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    @if ($resolvedInitialTheme === 'dark') class="dark" @endif
    @if ($resolvedInitialTheme !== null) data-bs-theme="{{ $resolvedInitialTheme }}" @endif
>
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>{{ $title }} | {{ config('app.name', 'CodaFácil') }}</title>

        @include('partials.favicons')
        @include('partials.google-tag')
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
        @vite(['resources/css/panel.css', 'resources/js/panel.js', 'resources/js/app.js'])
        @include('partials.theme-init')
        @livewireStyles
    </head>
    <body class="panel-interface antialiased">
        <div class="panel-shell" data-panel-shell>
            <div class="panel-overlay" data-panel-overlay></div>

            <aside class="panel-sidebar">
                <div class="panel-sidebar__header">
                    <a href="{{ $brandHref }}" class="panel-brand">
                        <span class="panel-brand__mark">
                            <img src="{{ asset('logo.webp') }}" alt="{{ config('app.name', 'CodaFácil') }}" />
                        </span>

                        <span class="panel-brand__copy">
                            @if ($brandContext)
                                <span class="panel-brand__eyebrow">{{ $brandContext }}</span>
                            @endif

                            <span class="panel-brand__name">{{ config('app.name', 'CodaFácil') }}</span>
                        </span>
                    </a>

                    <button type="button" class="panel-icon-btn lg:hidden" data-panel-sidebar-toggle aria-label="Fechar menu">
                        <i class="ti ti-x"></i>
                    </button>
                </div>

                <div class="panel-sidebar__body custom-scrollbar">
                    @foreach ($navigationGroups as $group)
                        <section class="panel-nav-group">
                            @if (! empty($group['heading']))
                                <h2 class="panel-nav-group__title">{{ $group['heading'] }}</h2>
                            @endif

                            <ul class="panel-nav-list">
                                @foreach ($group['items'] as $item)
                                    @php
                                        $hasChildren = ! empty($item['children']);
                                        $isOpen = (bool) ($item['open'] ?? false);
                                        $isActive = (bool) ($item['active'] ?? false);
                                    @endphp

                                    <li class="panel-nav-item {{ $isOpen ? 'is-open' : '' }}" @if ($hasChildren) data-panel-group @endif>
                                        @if ($hasChildren)
                                            <button
                                                type="button"
                                                class="panel-nav-link {{ $isActive ? 'panel-nav-link--active' : '' }}"
                                                data-panel-group-toggle
                                                aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                                            >
                                                <span class="panel-nav-link__icon">
                                                    <i class="{{ $item['icon'] }}"></i>
                                                </span>
                                                <span class="panel-nav-link__text">{{ $item['label'] }}</span>

                                                @if (! empty($item['badge']))
                                                    <span class="panel-nav-link__badge">{{ $item['badge'] }}</span>
                                                @endif

                                                <span class="panel-nav-link__caret">
                                                    <i class="ti ti-chevron-down"></i>
                                                </span>
                                            </button>

                                            <ul class="panel-subnav {{ $isOpen ? '' : 'hidden' }}" data-panel-group-panel>
                                                @foreach ($item['children'] as $child)
                                                    <li>
                                                        <a href="{{ $child['route'] }}" class="panel-subnav-link {{ $child['active'] ? 'panel-subnav-link--active' : '' }}">
                                                            {{ $child['label'] }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <a href="{{ $item['route'] }}" class="panel-nav-link {{ $isActive ? 'panel-nav-link--active' : '' }}">
                                                <span class="panel-nav-link__icon">
                                                    <i class="{{ $item['icon'] }}"></i>
                                                </span>
                                                <span class="panel-nav-link__text">{{ $item['label'] }}</span>

                                                @if (! empty($item['badge']))
                                                    <span class="panel-nav-link__badge">{{ $item['badge'] }}</span>
                                                @endif
                                            </a>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endforeach
                </div>

                @if ($sidebarFooterAction)
                    <div class="panel-sidebar__footer">
                        <a href="{{ $sidebarFooterAction['route'] }}" class="panel-btn panel-btn--primary w-full">
                            @if (! empty($sidebarFooterAction['icon']))
                                <i class="{{ $sidebarFooterAction['icon'] }}"></i>
                            @endif

                            <span>{{ $sidebarFooterAction['label'] }}</span>
                        </a>
                    </div>
                @endif
            </aside>

            <div class="panel-main">
                <header class="panel-header">
                    <div class="panel-header__inner">
                        <div class="panel-header__left">
                            <button type="button" class="panel-icon-btn lg:hidden" data-panel-sidebar-toggle aria-label="Abrir menu">
                                <i class="ti ti-menu-2"></i>
                            </button>

                            <div class="panel-search-shell">
                                <span class="panel-search__icon">
                                    <i class="ti ti-search text-lg"></i>
                                </span>
                                <input
                                    type="text"
                                    class="panel-search"
                                    placeholder="{{ $searchPlaceholder }}"
                                    data-panel-search-input
                                />
                                <span class="panel-search__shortcut">
                                    <span>⌘</span>
                                    <span>K</span>
                                </span>
                            </div>
                        </div>

                        <div class="panel-header__right">
                            <button type="button" class="panel-icon-btn" data-panel-theme-switch aria-label="Alternar tema">
                                <span class="dark:hidden"><i class="ti ti-moon text-xl"></i></span>
                                <span class="hidden dark:inline"><i class="ti ti-sun text-xl"></i></span>
                            </button>

                            <div class="panel-popover-root">
                                <button type="button" class="panel-icon-btn relative" data-panel-popover-toggle="notifications" aria-label="Notificações">
                                    <span class="absolute right-2 top-2 flex h-2 w-2 rounded-full bg-orange-400"></span>
                                    <i class="ti ti-bell text-xl"></i>
                                </button>

                                <div class="panel-popover panel-popover--wide" data-panel-popover="notifications">
                                    <div class="panel-popover__header">
                                        <h2 class="panel-popover__title">Atualizações</h2>
                                    </div>

                                    <div class="panel-popover__list custom-scrollbar">
                                        @forelse ($notifications as $notification)
                                            <a href="{{ $notification['route'] }}" class="panel-popover__item">
                                                <span class="panel-popover__item-icon">
                                                    <i class="{{ $notification['icon'] }}"></i>
                                                </span>

                                                <span class="panel-popover__item-copy">
                                                    <span class="panel-popover__item-title">{{ $notification['title'] }}</span>
                                                    <span class="panel-popover__item-text">{{ $notification['text'] }}</span>
                                                </span>
                                            </a>
                                        @empty
                                            <div class="panel-empty">Nenhuma atualização por enquanto.</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>

                            <div class="panel-popover-root">
                                <button type="button" class="panel-user-trigger" data-panel-popover-toggle="user" aria-label="Abrir menu do usuário">
                                    <span class="panel-user-trigger__avatar">{{ $user?->initials() }}</span>
                                    <span class="panel-user-trigger__copy">
                                        <span class="panel-user-trigger__name">{{ $user?->name }}</span>
                                        <span class="panel-user-trigger__meta">{{ $user?->email }}</span>
                                    </span>
                                    <i class="ti ti-chevron-down text-gray-400"></i>
                                </button>

                                <div class="panel-popover" data-panel-popover="user">
                                    <div class="panel-popover__header">
                                        <div>
                                            <h2 class="panel-popover__title">{{ $user?->name }}</h2>
                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $user?->email }}</p>
                                        </div>
                                    </div>

                                    <div class="panel-popover__list">
                                        <a href="{{ route('profile.edit') }}" class="panel-popover__item">
                                            <span class="panel-popover__item-icon"><i class="ti ti-settings"></i></span>
                                            <span class="panel-popover__item-copy">
                                                <span class="panel-popover__item-title">Configurações</span>
                                                <span class="panel-popover__item-text">Preferências de conta, senha e autenticação.</span>
                                            </span>
                                        </a>

                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="panel-popover__item w-full text-left" data-test="logout-button">
                                                <span class="panel-popover__item-icon"><i class="ti ti-logout"></i></span>
                                                <span class="panel-popover__item-copy">
                                                    <span class="panel-popover__item-title">Sair</span>
                                                    <span class="panel-popover__item-text">Encerrar a sessão atual com segurança.</span>
                                                </span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                <main class="panel-page">
                    <div class="panel-page__container">
                        <header class="panel-page__header">
                            <div>
                                <p class="panel-page__eyebrow">{{ $eyebrow }}</p>
                                <h1 class="panel-page__title">{{ $title }}</h1>
                                @if (isset($description) && filled($description))
                                    <p class="panel-page__copy">{{ $description }}</p>
                                @endif
                            </div>
                        </header>

                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>

        @livewireScripts
        @fluxScripts
        @stack('scripts')
    </body>
</html>
