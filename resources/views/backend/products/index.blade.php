@extends('layouts.backend', ['title' => 'Produtos'])

@section('content')
    <div class="grid gap-4 py-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Produtos</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Visão global do catálogo da base.</p>
        </div>

        <form method="GET" class="rounded-4 border border-slate-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <input type="text" name="search" value="{{ $search }}" placeholder="Buscar produtos" class="w-full rounded-3 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-950">
        </form>

        <div class="overflow-hidden rounded-4 border border-slate-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-[0.24em] text-slate-500 dark:bg-zinc-950 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Produto</th>
                        <th class="px-4 py-3">Conta</th>
                        <th class="px-4 py-3">Preço</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr class="border-t border-slate-200 dark:border-zinc-700">
                            <td class="px-4 py-4">
                                <div class="font-medium text-slate-900 dark:text-slate-100">{{ $product->name }}</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">{{ $product->sku ?: 'Sem SKU' }}</div>
                            </td>
                            <td class="px-4 py-4 text-slate-600 dark:text-slate-300">{{ $product->user?->email ?? 'Sem conta' }}</td>
                            <td class="px-4 py-4 text-slate-600 dark:text-slate-300">R$ {{ number_format((float) $product->price, 2, ',', '.') }}</td>
                            <td class="px-4 py-4 text-slate-600 dark:text-slate-300">{{ $product->is_active ? 'Ativo' : 'Inativo' }}</td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-3">
                                    <a href="{{ route('backend.products.show', $product) }}" class="text-sm font-medium text-sky-600 dark:text-sky-300">Ver</a>
                                    <a href="{{ route('backend.products.edit', $product) }}" class="text-sm font-medium text-slate-600 dark:text-slate-300">Editar</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">Nenhum produto encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $products->links() }}
    </div>
@endsection
