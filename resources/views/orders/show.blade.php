<x-layouts.app title="Detalhe do pedido">
    <div class="grid gap-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm text-slate-500 dark:text-slate-400">Pedido</p>
                <h1 class="text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ $order->public_number }}</h1>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('orders.edit', $order) }}" class="rounded-full border border-slate-200 px-5 py-3 text-sm font-semibold dark:border-zinc-700">Editar</a>
                <a href="{{ route('orders.index') }}" class="rounded-full bg-sky-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-400">Voltar</a>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[0.85fr_1.15fr]">
            <section class="rounded-4 border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Resumo</h2>
                <dl class="mt-5 grid gap-4 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500 dark:text-slate-400">Cliente</dt>
                        <dd class="font-medium text-slate-900 dark:text-slate-100">{{ $order->customer?->name ?? 'Sem cliente' }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500 dark:text-slate-400">Status</dt>
                        <dd class="font-medium text-slate-900 dark:text-slate-100">{{ ucfirst($order->status) }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500 dark:text-slate-400">Referência</dt>
                        <dd class="font-medium text-slate-900 dark:text-slate-100">{{ $order->reference ?: 'Sem referência' }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500 dark:text-slate-400">Total</dt>
                        <dd class="font-medium text-slate-900 dark:text-slate-100">R$ {{ number_format((float) $order->total_amount, 2, ',', '.') }}</dd>
                    </div>
                    <div class="grid gap-2">
                        <dt class="text-slate-500 dark:text-slate-400">Observações</dt>
                        <dd class="rounded-3 border border-slate-200 px-4 py-3 text-slate-700 dark:border-zinc-700 dark:text-slate-200">{{ $order->notes ?: 'Sem observações.' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-4 border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Itens do pedido</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="text-xs uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                            <tr>
                                <th class="px-3 py-3">Produto</th>
                                <th class="px-3 py-3">Quantidade</th>
                                <th class="px-3 py-3">Unitário</th>
                                <th class="px-3 py-3">Total</th>
                                <th class="px-3 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr class="border-t border-slate-200 dark:border-zinc-700">
                                    <td class="px-3 py-4 text-slate-900 dark:text-slate-100">{{ $item->product?->name ?? 'Produto removido' }}</td>
                                    <td class="px-3 py-4 text-slate-600 dark:text-slate-300">{{ $item->quantity }}</td>
                                    <td class="px-3 py-4 text-slate-600 dark:text-slate-300">R$ {{ number_format((float) $item->unit_price, 2, ',', '.') }}</td>
                                    <td class="px-3 py-4 text-slate-600 dark:text-slate-300">R$ {{ number_format((float) $item->total_price, 2, ',', '.') }}</td>
                                    <td class="px-3 py-4 text-slate-600 dark:text-slate-300">{{ ucfirst($item->status) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-layouts.app>
