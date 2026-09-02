<x-layouts.app title="Produtos">
    <div class="grid gap-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Produtos</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Mantenha um catálogo simples para composição dos pedidos.</p>
            </div>
            <a href="{{ route('products.create') }}" class="inline-flex items-center justify-center rounded-full bg-sky-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-400">
                Novo produto
            </a>
        </div>

        @if (session('status'))
            <div class="rounded-4 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        <form method="GET" class="flex flex-col gap-3 rounded-4 border border-slate-200 bg-white p-5 shadow-sm md:flex-row dark:border-zinc-700 dark:bg-zinc-900">
            <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por nome, SKU ou descrição" class="flex-1 rounded-3 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-950">
            <button type="submit" class="rounded-full border border-slate-200 px-5 py-3 text-sm font-semibold dark:border-zinc-700">Buscar</button>
        </form>

        <div class="overflow-hidden rounded-4 border border-slate-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-[0.24em] text-slate-500 dark:bg-zinc-950 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Produto</th>
                        <th class="px-4 py-3">SKU</th>
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
                                <div class="text-xs text-slate-500 dark:text-slate-400">{{ Str::limit($product->description, 60) ?: 'Sem descrição' }}</div>
                            </td>
                            <td class="px-4 py-4 text-slate-600 dark:text-slate-300">{{ $product->sku ?: 'Sem SKU' }}</td>
                            <td class="px-4 py-4 text-slate-600 dark:text-slate-300">R$ {{ number_format((float) $product->price, 2, ',', '.') }}</td>
                            <td class="px-4 py-4 text-slate-600 dark:text-slate-300">{{ $product->is_active ? 'Ativo' : 'Inativo' }}</td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-3">
                                    <a href="{{ route('products.edit', $product) }}" class="text-sm font-medium text-sky-600 dark:text-sky-300">Editar</a>
                                    <form method="POST" action="{{ route('products.destroy', $product) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm font-medium text-rose-600">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">Nenhum produto cadastrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $products->links() }}
    </div>
</x-layouts.app>
