<x-layouts.app title="Clientes">
    <div class="grid gap-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Clientes</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Cadastre os clientes usados nos pedidos da sua operação.</p>
            </div>
            <a href="{{ route('customers.create') }}" class="inline-flex items-center justify-center rounded-full bg-sky-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-400">
                Novo cliente
            </a>
        </div>

        @if (session('status'))
            <div class="rounded-4 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        <form method="GET" class="flex flex-col gap-3 rounded-4 border border-slate-200 bg-white p-5 shadow-sm md:flex-row dark:border-zinc-700 dark:bg-zinc-900">
            <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por nome, empresa, e-mail ou documento" class="flex-1 rounded-3 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-950">
            <button type="submit" class="rounded-full border border-slate-200 px-5 py-3 text-sm font-semibold dark:border-zinc-700">Buscar</button>
        </form>

        <div class="overflow-hidden rounded-4 border border-slate-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-[0.24em] text-slate-500 dark:bg-zinc-950 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Cliente</th>
                        <th class="px-4 py-3">Contato</th>
                        <th class="px-4 py-3">Pedidos</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr class="border-t border-slate-200 dark:border-zinc-700">
                            <td class="px-4 py-4">
                                <div class="font-medium text-slate-900 dark:text-slate-100">{{ $customer->name }}</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">{{ $customer->company_name ?: 'Sem empresa' }}</div>
                            </td>
                            <td class="px-4 py-4 text-slate-600 dark:text-slate-300">
                                <div>{{ $customer->email ?: 'Sem e-mail' }}</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">{{ $customer->phone ?: 'Sem telefone' }}</div>
                            </td>
                            <td class="px-4 py-4 text-slate-600 dark:text-slate-300">{{ $customer->orders_count }}</td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-3">
                                    <a href="{{ route('customers.edit', $customer) }}" class="text-sm font-medium text-sky-600 dark:text-sky-300">Editar</a>
                                    <form method="POST" action="{{ route('customers.destroy', $customer) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm font-medium text-rose-600">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">Nenhum cliente cadastrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $customers->links() }}
    </div>
</x-layouts.app>
