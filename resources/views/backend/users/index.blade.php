@extends('layouts.backend', ['title' => 'Usuários'])

@section('content')
    <div class="grid gap-4 py-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Usuários</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Gestão de acesso, perfil e permissões ACL.</p>
        </div>

        <form method="GET" class="grid gap-3 rounded-4 border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-[1fr_220px_auto] dark:border-zinc-700 dark:bg-zinc-900">
            <input type="text" name="search" value="{{ $search }}" placeholder="Buscar usuários" class="rounded-3 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-950">
            <select name="status" class="rounded-3 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-950">
                <option value="">Todos os filtros</option>
                <option value="active" @selected($status === 'active')>Ativos</option>
                <option value="blocked" @selected($status === 'blocked')>Bloqueados</option>
                <option value="agent" @selected($status === 'agent')>Agentes</option>
                <option value="admin" @selected($status === 'admin')>Admins</option>
            </select>
            <button type="submit" class="rounded-full border border-slate-200 px-5 py-3 text-sm font-semibold dark:border-zinc-700">Filtrar</button>
        </form>

        <div class="overflow-hidden rounded-4 border border-slate-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-[0.24em] text-slate-500 dark:bg-zinc-950 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Usuário</th>
                        <th class="px-4 py-3">Pedidos</th>
                        <th class="px-4 py-3">Perfis</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr class="border-t border-slate-200 dark:border-zinc-700">
                            <td class="px-4 py-4">
                                <div class="font-medium text-slate-900 dark:text-slate-100">{{ $user->name }}</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">{{ $user->email }}</div>
                            </td>
                            <td class="px-4 py-4 text-slate-600 dark:text-slate-300">{{ $user->orders_count }}</td>
                            <td class="px-4 py-4 text-slate-600 dark:text-slate-300">
                                {{ $user->is_admin ? 'Admin' : 'Cliente' }}
                                @if ($user->is_agent)
                                    , Agente
                                @endif
                                @if ($user->is_blocked)
                                    , Bloqueado
                                @endif
                            </td>
                            <td class="px-4 py-4 text-right">
                                <a href="{{ route('backend.users.show', $user) }}" class="text-sm font-medium text-sky-600 dark:text-sky-300">Gerenciar</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">Nenhum usuário encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $users->links() }}
    </div>
@endsection
