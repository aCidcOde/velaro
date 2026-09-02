@extends('layouts.backend', ['title' => 'Logs de Auditoria'])

@section('content')
    <div class="grid gap-4 py-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Logs de auditoria</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Rastreio administrativo das principais alterações do template.</p>
        </div>

        <form method="GET" class="rounded-4 border border-slate-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por ação, tipo de alvo ou usuário" class="w-full rounded-3 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-950">
        </form>

        <div class="overflow-hidden rounded-4 border border-slate-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-[0.24em] text-slate-500 dark:bg-zinc-950 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Ação</th>
                        <th class="px-4 py-3">Ator</th>
                        <th class="px-4 py-3">Alvo</th>
                        <th class="px-4 py-3">Data</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr class="border-t border-slate-200 dark:border-zinc-700">
                            <td class="px-4 py-4 text-slate-900 dark:text-slate-100">{{ $log->action }}</td>
                            <td class="px-4 py-4 text-slate-600 dark:text-slate-300">{{ $log->actor?->email ?? 'Sistema' }}</td>
                            <td class="px-4 py-4 text-slate-600 dark:text-slate-300">{{ class_basename((string) $log->target_type) }} #{{ $log->target_id ?: 'n/a' }}</td>
                            <td class="px-4 py-4 text-slate-600 dark:text-slate-300">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">Nenhum log registrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $logs->links() }}
    </div>
@endsection
