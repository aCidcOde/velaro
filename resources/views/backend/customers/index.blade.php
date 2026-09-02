@extends('layouts.backend', ['title' => 'Clientes'])

@section('content')
    <div class="grid gap-4 py-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Clientes</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Visão global dos cadastros da base.</p>
        </div>

        <form method="GET" class="rounded-4 border border-slate-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <input type="text" name="search" value="{{ $search }}" placeholder="Buscar clientes" class="w-full rounded-3 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-950">
        </form>

        <div class="overflow-hidden rounded-4 border border-slate-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-[0.24em] text-slate-500 dark:bg-zinc-950 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Cliente</th>
                        <th class="px-4 py-3">Conta</th>
                        <th class="px-4 py-3">Pedidos</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr class="border-t border-slate-200 dark:border-zinc-700">
                            <td class="px-4 py-4">
                                <div class="font-medium text-slate-900 dark:text-slate-100">{{ $customer->name }}</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">{{ $customer->company_name ?: $customer->email ?: 'Sem complemento' }}</div>
                            </td>
                            <td class="px-4 py-4 text-slate-600 dark:text-slate-300">{{ $customer->user?->email ?? 'Sem conta' }}</td>
                            <td class="px-4 py-4 text-slate-600 dark:text-slate-300">{{ $customer->orders_count }}</td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-3">
                                    <a href="{{ route('backend.customers.show', $customer) }}" class="text-sm font-medium text-sky-600 dark:text-sky-300">Ver</a>
                                    <a href="{{ route('backend.customers.edit', $customer) }}" class="text-sm font-medium text-slate-600 dark:text-slate-300">Editar</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">Nenhum cliente encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $customers->links() }}
    </div>
@endsection
