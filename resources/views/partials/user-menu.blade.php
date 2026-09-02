@php($variant = $variant ?? 'public')
@php($user = auth()->user())

@if ($variant === 'dashboard-mobile')
    <div class="grid gap-2 pt-2">
        <a href="{{ route('profile.edit') }}" class="rounded-2xl bg-white/75 px-4 py-3 text-sm font-medium text-[#173a9b] dark:bg-white/10 dark:text-white">Configurações</a>
        @if ($user?->is_agent)
            <a href="{{ route('agent.dashboard') }}" class="rounded-2xl bg-white/75 px-4 py-3 text-sm font-medium text-[#173a9b] dark:bg-white/10 dark:text-white">CodaFácil IA</a>
        @endif
        @if ($user?->is_admin)
            <a href="{{ route('backend.dashboard') }}" class="rounded-2xl bg-[#1e40af] px-4 py-3 text-sm font-semibold text-white dark:bg-white dark:text-[#173a9b]">Admin</a>
        @endif
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full rounded-2xl bg-rose-50 px-4 py-3 text-left text-sm font-semibold text-rose-600 dark:bg-rose-500/10 dark:text-rose-300">Sair</button>
        </form>
    </div>
@elseif ($variant === 'backend-mobile')
    <div class="grid gap-2 pt-2">
        <a href="{{ route('dashboard') }}" class="rounded-2xl bg-white/75 px-4 py-3 text-sm font-medium text-[#173a9b] dark:bg-white/10 dark:text-white">Voltar ao front</a>
        @if ($user?->is_agent)
            <a href="{{ route('agent.dashboard') }}" class="rounded-2xl bg-white/75 px-4 py-3 text-sm font-medium text-[#173a9b] dark:bg-white/10 dark:text-white">CodaFácil IA</a>
        @endif
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full rounded-2xl bg-rose-50 px-4 py-3 text-left text-sm font-semibold text-rose-600 dark:bg-rose-500/10 dark:text-rose-300">Sair</button>
        </form>
    </div>
@elseif ($variant === 'dashboard' || $variant === 'backend')
    <details class="group relative [&_summary::-webkit-details-marker]:hidden">
        <summary class="flex cursor-pointer list-none items-center gap-3 rounded-full border border-white/50 bg-white/80 px-3 py-2 text-left shadow-sm transition hover:bg-white dark:border-white/10 dark:bg-white/10 dark:hover:bg-white/14">
            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#1e40af] text-xs font-semibold text-white dark:bg-white dark:text-[#173a9b]">
                {{ $user?->initials() }}
            </span>
            <span class="hidden min-w-0 sm:block">
                <span class="block truncate text-sm font-semibold text-[#173a9b] dark:text-white">{{ $user?->name }}</span>
                <span class="block truncate text-xs text-blue-700/70 dark:text-blue-100/65">{{ $user?->email }}</span>
            </span>
            <span class="hidden text-blue-400 transition group-open:rotate-180 sm:inline dark:text-blue-200">v</span>
        </summary>
        <div class="absolute right-0 z-50 mt-3 w-64 overflow-hidden rounded-[1.5rem] border border-white/45 bg-white/90 p-2 shadow-[0_24px_70px_-44px_rgba(37,99,235,0.4)] backdrop-blur dark:border-white/10 dark:bg-[#16306f]/92 dark:shadow-[0_32px_80px_-44px_rgba(2,6,23,0.92)]">
            @if ($variant === 'backend')
                <a href="{{ route('dashboard') }}" class="block rounded-2xl px-4 py-3 text-sm font-medium text-[#173a9b] transition hover:bg-white dark:text-white dark:hover:bg-white/10">Voltar ao front</a>
                <a href="{{ route('profile.edit') }}" class="block rounded-2xl px-4 py-3 text-sm font-medium text-[#173a9b] transition hover:bg-white dark:text-white dark:hover:bg-white/10">Perfil</a>
            @else
                <a href="{{ route('profile.edit') }}" class="block rounded-2xl px-4 py-3 text-sm font-medium text-[#173a9b] transition hover:bg-white dark:text-white dark:hover:bg-white/10">Configurações</a>
            @endif
            @if ($user?->is_agent)
                <a href="{{ route('agent.dashboard') }}" class="block rounded-2xl px-4 py-3 text-sm font-medium text-[#173a9b] transition hover:bg-white dark:text-white dark:hover:bg-white/10">CodaFácil IA</a>
            @endif
            @if ($variant === 'dashboard' && $user?->is_admin)
                <a href="{{ route('backend.dashboard') }}" class="block rounded-2xl px-4 py-3 text-sm font-semibold text-[#173a9b] transition hover:bg-white dark:text-white dark:hover:bg-white/10">Admin</a>
            @endif
            <div class="my-2 h-px bg-blue-100 dark:bg-white/10"></div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full rounded-2xl px-4 py-3 text-left text-sm font-semibold text-rose-600 transition hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-500/10">
                    Sair
                </button>
            </form>
        </div>
    </details>
@elseif ($variant === 'agent')
    <details class="group relative [&_summary::-webkit-details-marker]:hidden">
        <summary class="flex cursor-pointer list-none items-center gap-3 rounded-full border border-slate-200/80 bg-white/90 px-3 py-2 text-sm text-slate-700 shadow-sm transition hover:bg-white dark:border-white/10 dark:bg-white/10 dark:text-white dark:hover:bg-white/14">
            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#1e40af] text-xs font-semibold text-white dark:bg-white dark:text-[#173a9b]">
                {{ $user?->initials() }}
            </span>
            <span class="hidden sm:inline">{{ $user?->name }}</span>
            <span class="hidden text-blue-400 transition group-open:rotate-180 sm:inline dark:text-blue-200">v</span>
        </summary>

        <div class="absolute right-0 z-50 mt-3 w-60 overflow-hidden rounded-[1.5rem] border border-white/45 bg-white/90 p-2 shadow-[0_24px_70px_-44px_rgba(37,99,235,0.4)] backdrop-blur dark:border-white/10 dark:bg-[#16306f]/92 dark:shadow-[0_32px_80px_-44px_rgba(2,6,23,0.92)]">
            <a href="{{ route('dashboard') }}" class="block rounded-2xl px-4 py-3 text-sm font-medium text-[#173a9b] transition hover:bg-white dark:text-white dark:hover:bg-white/10">Dashboard</a>
            <a href="{{ route('orders.index') }}" class="block rounded-2xl px-4 py-3 text-sm font-medium text-[#173a9b] transition hover:bg-white dark:text-white dark:hover:bg-white/10">Pedidos</a>
            <a href="{{ route('profile.edit') }}" class="block rounded-2xl px-4 py-3 text-sm font-medium text-[#173a9b] transition hover:bg-white dark:text-white dark:hover:bg-white/10">Configurações</a>
            @if ($user?->is_admin)
                <a href="{{ route('backend.dashboard') }}" class="block rounded-2xl px-4 py-3 text-sm font-medium text-[#173a9b] transition hover:bg-white dark:text-white dark:hover:bg-white/10">Admin</a>
            @endif
            <div class="my-2 h-px bg-blue-100 dark:bg-white/10"></div>
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit" class="w-full rounded-2xl px-4 py-3 text-left text-sm font-semibold text-rose-600 transition hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-500/10">
                    Sair
                </button>
            </form>
        </div>
    </details>
@else
    <details class="group relative [&_summary::-webkit-details-marker]:hidden">
        <summary class="flex cursor-pointer list-none items-center gap-3 rounded-full border border-white/45 bg-white/18 px-3 py-2 text-left text-sm shadow-sm transition hover:bg-white/22 dark:border-white/10 dark:bg-white/8 dark:hover:bg-white/12">
            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-[#173a9b] font-semibold dark:bg-white dark:text-[#173a9b]">
                {{ $user?->initials() }}
            </span>
            <span class="hidden md:block">
                <span class="block font-medium text-white">{{ $user?->name }}</span>
                <span class="block text-xs text-blue-100/70">{{ $user?->email }}</span>
            </span>
            <span class="hidden text-blue-100/70 transition group-open:rotate-180 md:inline">v</span>
        </summary>
        <div class="absolute right-0 z-50 mt-3 w-60 overflow-hidden rounded-[1.5rem] border border-white/20 bg-[#2147b3]/95 p-2 shadow-[0_28px_80px_-44px_rgba(15,23,42,0.55)] backdrop-blur dark:border-white/10 dark:bg-[#16306f]/92">
            <a href="{{ route('dashboard') }}" class="block rounded-2xl px-4 py-3 text-sm font-medium text-white transition hover:bg-white/10">Dashboard</a>
            <a href="{{ route('profile.edit') }}" class="block rounded-2xl px-4 py-3 text-sm font-medium text-white transition hover:bg-white/10">Configurações</a>
            @if ($user?->is_agent)
                <a href="{{ route('agent.dashboard') }}" class="block rounded-2xl px-4 py-3 text-sm font-medium text-white transition hover:bg-white/10">CodaFácil IA</a>
            @endif
            @if ($user?->is_admin)
                <a href="{{ route('backend.dashboard') }}" class="block rounded-2xl px-4 py-3 text-sm font-medium text-white transition hover:bg-white/10">Admin</a>
            @endif
        </div>
    </details>
@endif
