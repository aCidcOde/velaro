@php($ordersActive = request()->routeIs('backend.orders.*'))
@php($auditoriaActive = request()->routeIs('backend.audit-logs.*') || request()->routeIs('backend.changelog.*'))

<div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-end">
    <nav class="hidden flex-wrap items-center gap-2 lg:flex">
        <a href="{{ route('backend.dashboard') }}" class="{{ request()->routeIs('backend.dashboard') ? 'bg-[#1e40af] text-white dark:bg-white dark:text-[#173a9b]' : 'text-[#284191] hover:bg-white/60 hover:text-[#173a9b] dark:text-blue-100/90 dark:hover:bg-white/10 dark:hover:text-white' }} rounded-full px-4 py-2 text-sm font-semibold transition">
            Dashboard
        </a>
        <a href="{{ route('backend.users.index') }}" class="{{ request()->routeIs('backend.users.*') ? 'bg-[#1e40af] text-white dark:bg-white dark:text-[#173a9b]' : 'text-[#284191] hover:bg-white/60 hover:text-[#173a9b] dark:text-blue-100/90 dark:hover:bg-white/10 dark:hover:text-white' }} rounded-full px-4 py-2 text-sm font-semibold transition">
            Usuários
        </a>
        <a href="{{ route('backend.customers.index') }}" class="{{ request()->routeIs('backend.customers.*') ? 'bg-[#1e40af] text-white dark:bg-white dark:text-[#173a9b]' : 'text-[#284191] hover:bg-white/60 hover:text-[#173a9b] dark:text-blue-100/90 dark:hover:bg-white/10 dark:hover:text-white' }} rounded-full px-4 py-2 text-sm font-semibold transition">
            Clientes
        </a>
        <a href="{{ route('backend.products.index') }}" class="{{ request()->routeIs('backend.products.*') ? 'bg-[#1e40af] text-white dark:bg-white dark:text-[#173a9b]' : 'text-[#284191] hover:bg-white/60 hover:text-[#173a9b] dark:text-blue-100/90 dark:hover:bg-white/10 dark:hover:text-white' }} rounded-full px-4 py-2 text-sm font-semibold transition">
            Produtos
        </a>
        <a href="{{ route('backend.orders.index') }}" class="{{ $ordersActive ? 'bg-[#1e40af] text-white dark:bg-white dark:text-[#173a9b]' : 'text-[#284191] hover:bg-white/60 hover:text-[#173a9b] dark:text-blue-100/90 dark:hover:bg-white/10 dark:hover:text-white' }} rounded-full px-4 py-2 text-sm font-semibold transition">
            Pedidos
        </a>
        <a href="{{ route('backend.audit-logs.index') }}" class="{{ $auditoriaActive ? 'bg-[#1e40af] text-white dark:bg-white dark:text-[#173a9b]' : 'text-[#284191] hover:bg-white/60 hover:text-[#173a9b] dark:text-blue-100/90 dark:hover:bg-white/10 dark:hover:text-white' }} rounded-full px-4 py-2 text-sm font-semibold transition">
            Auditoria
        </a>
    </nav>

    <div class="hidden items-center gap-3 lg:flex">
        <div class="flex items-center gap-2">
            <button type="button" onclick="toggleTheme('dark')" class="rounded-full border border-white/60 bg-white/80 px-3 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-[#173a9b] transition hover:bg-white dark:hidden">
                Escuro
            </button>
            <button type="button" onclick="toggleTheme('light')" class="hidden rounded-full border border-white/20 bg-white/10 px-3 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-white transition hover:bg-white/20 dark:inline-flex">
                Claro
            </button>
        </div>
        @include('partials.user-menu', ['variant' => 'backend'])
    </div>

    <details class="group lg:hidden [&_summary::-webkit-details-marker]:hidden">
        <summary class="flex cursor-pointer list-none items-center justify-between rounded-2xl border border-white/45 bg-white/70 px-4 py-3 text-sm font-semibold text-[#173a9b] dark:border-white/10 dark:bg-white/10 dark:text-white">
            Navegação
            <span class="text-blue-400 transition group-open:rotate-180 dark:text-blue-200">v</span>
        </summary>
        <div class="mt-3 grid gap-2 rounded-[1.75rem] border border-white/45 bg-white/80 p-3 shadow-[0_24px_70px_-44px_rgba(37,99,235,0.38)] backdrop-blur dark:border-white/10 dark:bg-[#1b3276]/80">
            <div class="flex gap-2">
                <button type="button" onclick="toggleTheme('dark')" class="flex-1 rounded-full border border-white/60 bg-white/80 px-3 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-[#173a9b] transition hover:bg-white dark:hidden">
                    Escuro
                </button>
                <button type="button" onclick="toggleTheme('light')" class="hidden flex-1 rounded-full border border-white/20 bg-white/10 px-3 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-white transition hover:bg-white/20 dark:inline-flex">
                    Claro
                </button>
            </div>
            <a href="{{ route('backend.dashboard') }}" class="rounded-2xl px-4 py-3 text-sm font-medium {{ request()->routeIs('backend.dashboard') ? 'bg-[#1e40af] text-white dark:bg-white dark:text-[#173a9b]' : 'bg-white/75 text-[#173a9b] dark:bg-white/10 dark:text-white' }}">
                Dashboard
            </a>
            <a href="{{ route('backend.users.index') }}" class="rounded-2xl px-4 py-3 text-sm font-medium {{ request()->routeIs('backend.users.*') ? 'bg-[#1e40af] text-white dark:bg-white dark:text-[#173a9b]' : 'bg-white/75 text-[#173a9b] dark:bg-white/10 dark:text-white' }}">
                Usuários
            </a>
            <a href="{{ route('backend.customers.index') }}" class="rounded-2xl px-4 py-3 text-sm font-medium {{ request()->routeIs('backend.customers.*') ? 'bg-[#1e40af] text-white dark:bg-white dark:text-[#173a9b]' : 'bg-white/75 text-[#173a9b] dark:bg-white/10 dark:text-white' }}">
                Clientes
            </a>
            <a href="{{ route('backend.products.index') }}" class="rounded-2xl px-4 py-3 text-sm font-medium {{ request()->routeIs('backend.products.*') ? 'bg-[#1e40af] text-white dark:bg-white dark:text-[#173a9b]' : 'bg-white/75 text-[#173a9b] dark:bg-white/10 dark:text-white' }}">
                Produtos
            </a>
            <a href="{{ route('backend.orders.index') }}" class="rounded-2xl px-4 py-3 text-sm font-medium {{ $ordersActive ? 'bg-[#1e40af] text-white dark:bg-white dark:text-[#173a9b]' : 'bg-white/75 text-[#173a9b] dark:bg-white/10 dark:text-white' }}">
                Pedidos
            </a>
            <a href="{{ route('backend.audit-logs.index') }}" class="rounded-2xl px-4 py-3 text-sm font-medium {{ $auditoriaActive ? 'bg-[#1e40af] text-white dark:bg-white dark:text-[#173a9b]' : 'bg-white/75 text-[#173a9b] dark:bg-white/10 dark:text-white' }}">
                Auditoria
            </a>
            @include('partials.user-menu', ['variant' => 'backend-mobile'])
        </div>
    </details>
</div>
