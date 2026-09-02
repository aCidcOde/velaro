<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen antialiased text-[#f5f7fb]" style="background: radial-gradient(circle at 18% 18%, rgba(14,165,233,0.15), transparent 38%), radial-gradient(circle at 82% 8%, rgba(56,189,248,0.12), transparent 42%), linear-gradient(135deg, #0b0e15 0%, #0f141f 55%, #0a0f1c 100%);">
        <flux:header container class="border-b border-white/10 bg-[#0d1118]/90 dark:border-white/10 dark:bg-[#0d1118]/90 backdrop-blur">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <a href="{{ route('dashboard') }}" class="ms-2 me-5 flex items-center space-x-2 rtl:space-x-reverse lg:ms-0" wire:navigate>
                <x-app-logo />
            </a>

            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:navbar.item>
                @if (auth()->check() && auth()->user()->is_admin)
                    <flux:navbar.item icon="shield-check" :href="route('backend.dashboard')" wire:navigate>
                        Admin
                    </flux:navbar.item>
                @endif
            </flux:navbar>

            <flux:spacer />

            <!-- Desktop User Menu -->
            <flux:dropdown position="top" align="end">
                <flux:profile
                    class="cursor-pointer"
                    :initials="auth()->user()->initials()"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                        @if (auth()->user()->is_agent)
                        <flux:menu.item :href="route('agent.dashboard')" icon="sparkles" wire:navigate>Agente</flux:menu.item>
                        @endif
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full" data-test="logout-button">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar stashable sticky class="lg:hidden border-e border-white/10 bg-[#0d1118]/90 dark:border-white/10 dark:bg-[#0d1118]/90 backdrop-blur">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="ms-1 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                <x-app-logo />
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group :heading="__('Platform')">
                    <flux:navlist.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                    </flux:navlist.item>
                    @if (auth()->check() && auth()->user()->is_admin)
                        <flux:navlist.item icon="shield-check" :href="route('backend.dashboard')" wire:navigate>
                        Admin
                        </flux:navlist.item>
                    @endif
                    @if (auth()->check() && auth()->user()->is_agent)
                        <flux:navlist.item icon="sparkles" :href="route('agent.dashboard')" wire:navigate>
                        Agente
                        </flux:navlist.item>
                    @endif
                </flux:navlist.group>
            </flux:navlist>

            <flux:spacer />
        </flux:sidebar>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
