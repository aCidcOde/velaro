@extends('layouts.backend', ['title' => 'Pedido'])

@section('content')
    <div class="grid gap-4 py-4">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ $order->public_number }}</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $order->user?->email ?? 'Sem conta' }}</p>
            </div>
            <form method="POST" action="{{ route('backend.orders.destroy', $order) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-full border border-rose-200 px-5 py-3 text-sm font-semibold text-rose-600">Excluir pedido</button>
            </form>
        </div>

        @if (session('status'))
            <div class="rounded-4 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        <div class="grid gap-4 xl:grid-cols-[0.7fr_1.3fr]">
            <section class="rounded-4 border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Resumo</h2>
                <dl class="mt-4 grid gap-4 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500 dark:text-slate-400">Cliente</dt>
                        <dd class="font-medium text-slate-900 dark:text-slate-100">{{ $order->customer?->name ?? 'Sem cliente' }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500 dark:text-slate-400">Status</dt>
                        <dd class="font-medium text-slate-900 dark:text-slate-100">{{ ucfirst($order->status) }}</dd>
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
                @if ($order->user === null)
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Histórico preservado</h2>
                    <p class="mt-4 text-sm text-slate-600 dark:text-slate-300">A conta responsável foi excluída. O histórico e os itens permanecem disponíveis para consulta. A edição está indisponível.</p>
                    <div class="mt-6 grid gap-4 text-sm">
                        @forelse ($order->items as $item)
                            <dl class="grid min-w-0 gap-1 wrap-anywhere">
                                <dt class="font-medium text-slate-900 dark:text-slate-100">{{ $item->product?->name ?? 'Produto indisponível' }}</dt>
                                <dd class="text-slate-600 dark:text-slate-300">{{ $item->quantity }} × R$ {{ number_format((float) $item->unit_price, 2, ',', '.') }} · Total: R$ {{ number_format((float) $item->total_price, 2, ',', '.') }}</dd>
                            </dl>
                        @empty
                            <div class="text-slate-600 dark:text-slate-300">Nenhum item registrado.</div>
                        @endforelse
                    </div>
                @else
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Atualizar pedido</h2>
                    <form method="POST" action="{{ route('backend.orders.update', $order) }}" class="mt-6 grid gap-6">
                        @include('orders._form', ['order' => $order, 'customers' => $order->user->customers()->orderBy('name')->get(), 'products' => $order->user->products()->orderBy('name')->get(), 'method' => 'PUT'])

                        <div class="flex justify-end">
                            <button type="submit" class="rounded-full bg-sky-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-400">Salvar alterações</button>
                        </div>
                    </form>
                @endif
            </section>
        </div>
    </div>
@endsection
