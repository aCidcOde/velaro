<div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-end">
    <nav class="hidden flex-wrap items-center gap-2 lg:flex">
        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'bg-[#1e40af] text-white dark:bg-white dark:text-[#173a9b]' : 'text-[#284191] hover:bg-white/60 hover:text-[#173a9b] dark:text-blue-100/90 dark:hover:bg-white/10 dark:hover:text-white' }} rounded-full px-4 py-2 text-sm font-semibold transition">
            Dashboard
        </a>
        <a href="{{ route('customers.index') }}" class="{{ request()->routeIs('customers.*') ? 'bg-[#1e40af] text-white dark:bg-white dark:text-[#173a9b]' : 'text-[#284191] hover:bg-white/60 hover:text-[#173a9b] dark:text-blue-100/90 dark:hover:bg-white/10 dark:hover:text-white' }} rounded-full px-4 py-2 text-sm font-semibold transition">
            Clientes
        </a>
        <a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.*') ? 'bg-[#1e40af] text-white dark:bg-white dark:text-[#173a9b]' : 'text-[#284191] hover:bg-white/60 hover:text-[#173a9b] dark:text-blue-100/90 dark:hover:bg-white/10 dark:hover:text-white' }} rounded-full px-4 py-2 text-sm font-semibold transition">
            Produtos
        </a>
        <a href="{{ route('orders.index') }}" class="{{ request()->routeIs('orders.*') && !request()->routeIs('orders.create') ? 'bg-[#1e40af] text-white dark:bg-white dark:text-[#173a9b]' : 'text-[#284191] hover:bg-white/60 hover:text-[#173a9b] dark:text-blue-100/90 dark:hover:bg-white/10 dark:hover:text-white' }} rounded-full px-4 py-2 text-sm font-semibold transition">
            Pedidos
        </a>
        <a href="{{ route('orders.create') }}" class="{{ request()->routeIs('orders.create') ? 'bg-white text-[#173a9b]' : 'bg-[#1e40af] text-white hover:bg-[#173a9b] dark:bg-white dark:text-[#173a9b] dark:hover:bg-blue-50' }} rounded-full px-5 py-2 text-sm font-semibold shadow-lg shadow-blue-900/15 transition">
            Novo pedido
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
        @include('partials.user-menu', ['variant' => 'dashboard'])
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
            <a href="{{ route('dashboard') }}" class="rounded-2xl px-4 py-3 text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-[#1e40af] text-white dark:bg-white dark:text-[#173a9b]' : 'bg-white/75 text-[#173a9b] dark:bg-white/10 dark:text-white' }}">
                Dashboard
            </a>
            <a href="{{ route('customers.index') }}" class="rounded-2xl px-4 py-3 text-sm font-medium {{ request()->routeIs('customers.*') ? 'bg-[#1e40af] text-white dark:bg-white dark:text-[#173a9b]' : 'bg-white/75 text-[#173a9b] dark:bg-white/10 dark:text-white' }}">
                Clientes
            </a>
            <a href="{{ route('products.index') }}" class="rounded-2xl px-4 py-3 text-sm font-medium {{ request()->routeIs('products.*') ? 'bg-[#1e40af] text-white dark:bg-white dark:text-[#173a9b]' : 'bg-white/75 text-[#173a9b] dark:bg-white/10 dark:text-white' }}">
                Produtos
            </a>
            <a href="{{ route('orders.index') }}" class="rounded-2xl px-4 py-3 text-sm font-medium {{ request()->routeIs('orders.*') && !request()->routeIs('orders.create') ? 'bg-[#1e40af] text-white dark:bg-white dark:text-[#173a9b]' : 'bg-white/75 text-[#173a9b] dark:bg-white/10 dark:text-white' }}">
                Pedidos
            </a>
            <a href="{{ route('orders.create') }}" class="rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-[#173a9b] dark:bg-white dark:text-[#173a9b]">
                Novo pedido
            </a>
            @include('partials.user-menu', ['variant' => 'dashboard-mobile'])
        </div>
    </details>
</div>
