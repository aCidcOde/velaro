@extends('layouts.backend', ['title' => 'Cliente'])

@section('content')
    <div class="grid gap-4 py-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ $customer->name }}</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $customer->user?->email ?? 'Sem conta vinculada' }}</p>
            </div>
            <a href="{{ route('backend.customers.edit', $customer) }}" class="rounded-full border border-slate-200 px-5 py-3 text-sm font-semibold dark:border-zinc-700">Editar</a>
        </div>

        <div class="rounded-4 border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <dl class="grid gap-4 md:grid-cols-2">
                <div>
                    <dt class="text-xs uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Empresa</dt>
                    <dd class="mt-2 text-sm text-slate-900 dark:text-slate-100">{{ $customer->company_name ?: 'Sem empresa' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Documento</dt>
                    <dd class="mt-2 text-sm text-slate-900 dark:text-slate-100">{{ $customer->document ?: 'Sem documento' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">E-mail</dt>
                    <dd class="mt-2 text-sm text-slate-900 dark:text-slate-100">{{ $customer->email ?: 'Sem e-mail' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Telefone</dt>
                    <dd class="mt-2 text-sm text-slate-900 dark:text-slate-100">{{ $customer->phone ?: 'Sem telefone' }}</dd>
                </div>
                <div class="md:col-span-2">
                    <dt class="text-xs uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Observações</dt>
                    <dd class="mt-2 rounded-3 border border-slate-200 px-4 py-3 text-sm text-slate-900 dark:border-zinc-700 dark:text-slate-100">{{ $customer->notes ?: 'Sem observações.' }}</dd>
                </div>
            </dl>
        </div>
    </div>
@endsection
