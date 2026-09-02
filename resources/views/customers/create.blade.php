<x-layouts.app title="Novo cliente">
    <div class="rounded-4 border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Novo cliente</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Cadastre um cliente base para futuros pedidos.</p>

        <form method="POST" action="{{ route('customers.store') }}" class="mt-8 grid gap-6">
            @include('customers._form', ['customer' => $customer])

            <div class="flex justify-end gap-3">
                <a href="{{ route('customers.index') }}" class="rounded-full border border-slate-200 px-5 py-3 text-sm font-semibold dark:border-zinc-700">Cancelar</a>
                <button type="submit" class="rounded-full bg-sky-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-400">Salvar cliente</button>
            </div>
        </form>
    </div>
</x-layouts.app>
