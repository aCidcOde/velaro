<x-layouts.app title="Novo produto">
    <div class="rounded-4 border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Novo produto</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Cadastre um item reutilizável para seus pedidos.</p>

        <form method="POST" action="{{ route('products.store') }}" class="mt-8 grid gap-6">
            @include('products._form', ['product' => $product])

            <div class="flex justify-end gap-3">
                <a href="{{ route('products.index') }}" class="rounded-full border border-slate-200 px-5 py-3 text-sm font-semibold dark:border-zinc-700">Cancelar</a>
                <button type="submit" class="rounded-full bg-sky-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-400">Salvar produto</button>
            </div>
        </form>
    </div>
</x-layouts.app>
