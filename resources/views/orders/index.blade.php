<x-layouts.app title="Pedidos">
    <div class="grid gap-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Pedidos</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Acompanhe o pipeline comercial e operacional básico do template.</p>
            </div>
            <a href="{{ route('orders.create') }}" class="inline-flex items-center justify-center rounded-full bg-sky-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-400">
                Novo pedido
            </a>
        </div>

        @if (session('status'))
            <div class="rounded-4 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        <form method="GET" class="grid gap-3 rounded-4 border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-[1fr_220px_auto] dark:border-zinc-700 dark:bg-zinc-900">
            <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por número, referência ou cliente" class="rounded-3 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-950">
            <select name="status" class="rounded-3 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-950">
                <option value="">Todos os status</option>
                @foreach (['draft' => 'Rascunho', 'submitted' => 'Enviado', 'processing' => 'Processando', 'completed' => 'Concluído', 'canceled' => 'Cancelado'] as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-full border border-slate-200 px-5 py-3 text-sm font-semibold dark:border-zinc-700">Filtrar</button>
        </form>

        <div class="overflow-hidden rounded-4 border border-slate-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-[0.24em] text-slate-500 dark:bg-zinc-950 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Pedido</th>
                        <th class="px-4 py-3">Cliente</th>
                        <th class="px-4 py-3">Itens</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Total</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr class="border-t border-slate-200 dark:border-zinc-700">
                            <td class="px-4 py-4">
                                <div class="font-medium text-slate-900 dark:text-slate-100">{{ $order->public_number }}</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">{{ $order->reference ?: 'Sem referência' }}</div>
                            </td>
                            <td class="px-4 py-4 text-slate-600 dark:text-slate-300">{{ $order->customer?->name ?? 'Sem cliente' }}</td>
                            <td class="px-4 py-4 text-slate-600 dark:text-slate-300">{{ $order->items->count() }}</td>
                            <td class="px-4 py-4 text-slate-600 dark:text-slate-300">{{ ucfirst($order->status) }}</td>
                            <td class="px-4 py-4 text-slate-600 dark:text-slate-300">R$ {{ number_format((float) $order->total_amount, 2, ',', '.') }}</td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-3">
                                    <a href="{{ route('orders.show', $order) }}" class="text-sm font-medium text-sky-600 dark:text-sky-300">Ver</a>
                                    <a href="{{ route('orders.edit', $order) }}" class="text-sm font-medium text-slate-600 dark:text-slate-300">Editar</a>
                                    <form method="POST" action="{{ route('orders.destroy', $order) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm font-medium text-rose-600">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">Nenhum pedido cadastrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $orders->links() }}
    </div>
</x-layouts.app>
