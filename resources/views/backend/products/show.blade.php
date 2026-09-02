@extends('layouts.backend', ['title' => 'Produto'])

@section('content')
    <div class="grid gap-4 py-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ $product->name }}</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $product->user?->email ?? 'Sem conta vinculada' }}</p>
            </div>
            <a href="{{ route('backend.products.edit', $product) }}" class="rounded-full border border-slate-200 px-5 py-3 text-sm font-semibold dark:border-zinc-700">Editar</a>
        </div>

        <div class="rounded-4 border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <dl class="grid gap-4 md:grid-cols-2">
                <div>
                    <dt class="text-xs uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">SKU</dt>
                    <dd class="mt-2 text-sm text-slate-900 dark:text-slate-100">{{ $product->sku ?: 'Sem SKU' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Preço</dt>
                    <dd class="mt-2 text-sm text-slate-900 dark:text-slate-100">R$ {{ number_format((float) $product->price, 2, ',', '.') }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Status</dt>
                    <dd class="mt-2 text-sm text-slate-900 dark:text-slate-100">{{ $product->is_active ? 'Ativo' : 'Inativo' }}</dd>
                </div>
                <div class="md:col-span-2">
                    <dt class="text-xs uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Descrição</dt>
                    <dd class="mt-2 rounded-3 border border-slate-200 px-4 py-3 text-sm text-slate-900 dark:border-zinc-700 dark:text-slate-100">{{ $product->description ?: 'Sem descrição.' }}</dd>
                </div>
            </dl>
        </div>
    </div>
@endsection
