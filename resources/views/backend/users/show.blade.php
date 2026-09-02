@extends('layouts.backend', ['title' => 'Usuário'])

@section('content')
    <div class="grid gap-4 py-4">
        <div class="flex flex-col gap-2">
            <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ $user->name }}</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ $user->email }}</p>
        </div>

        @if (session('user_success'))
            <div class="rounded-4 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('user_success') }}</div>
        @endif

        <div class="grid gap-4 xl:grid-cols-[0.7fr_1.3fr]">
            <section class="rounded-4 border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Dados do usuário</h2>
                <div class="mt-4 grid gap-4">
                    <div class="rounded-3 border border-slate-200 px-4 py-3 text-sm dark:border-zinc-700">
                        <div class="text-xs uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Clientes</div>
                        <div class="mt-2 text-slate-900 dark:text-slate-100">{{ $customersCount }}</div>
                    </div>
                    <div class="rounded-3 border border-slate-200 px-4 py-3 text-sm dark:border-zinc-700">
                        <div class="text-xs uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Produtos</div>
                        <div class="mt-2 text-slate-900 dark:text-slate-100">{{ $productsCount }}</div>
                    </div>
                </div>

                @if ($canUpdateUser)
                    <form method="POST" action="{{ route('backend.users.update', $user) }}" class="mt-6 grid gap-4">
                        @csrf
                        @method('PUT')
                        <label class="grid gap-2 text-sm">
                            <span>Nome</span>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="rounded-3 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-950">
                        </label>
                        <label class="grid gap-2 text-sm">
                            <span>E-mail</span>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="rounded-3 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-950">
                        </label>
                        <label class="grid gap-2 text-sm">
                            <span>Telefone</span>
                            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="rounded-3 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-950">
                        </label>
                        <label class="grid gap-2 text-sm">
                            <span>Documento</span>
                            <input type="text" name="document" value="{{ old('document', $user->document) }}" class="rounded-3 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-950">
                        </label>
                        <label class="flex items-center gap-3 rounded-3 border border-slate-200 px-4 py-3 text-sm dark:border-zinc-700">
                            <input type="checkbox" name="is_admin" value="1" {{ old('is_admin', $user->is_admin) ? 'checked' : '' }}>
                            <span>Administrador</span>
                        </label>
                        <label class="flex items-center gap-3 rounded-3 border border-slate-200 px-4 py-3 text-sm dark:border-zinc-700">
                            <input type="checkbox" name="is_agent" value="1" {{ old('is_agent', $user->is_agent) ? 'checked' : '' }}>
                            <span>Acesso ao agente</span>
                        </label>
                        @if (! $user->is_admin)
                            <label class="flex items-center gap-3 rounded-3 border border-slate-200 px-4 py-3 text-sm dark:border-zinc-700">
                                <input type="checkbox" name="is_blocked" value="1" {{ old('is_blocked', $user->is_blocked) ? 'checked' : '' }}>
                                <span>Usuário bloqueado</span>
                            </label>
                        @endif
                        <div class="flex justify-end">
                            <button type="submit" class="rounded-full bg-sky-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-400">Salvar dados</button>
                        </div>
                    </form>
                @endif
            </section>

            <section class="grid gap-4">
                <div class="rounded-4 border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Pedidos do usuário</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="text-xs uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                                <tr>
                                    <th class="px-3 py-3">Pedido</th>
                                    <th class="px-3 py-3">Status</th>
                                    <th class="px-3 py-3">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($orders as $order)
                                    <tr class="border-t border-slate-200 dark:border-zinc-700">
                                        <td class="px-3 py-4">
                                            <a href="{{ route('backend.orders.show', $order) }}" class="font-medium text-slate-900 dark:text-slate-100">{{ $order->public_number }}</a>
                                        </td>
                                        <td class="px-3 py-4 text-slate-600 dark:text-slate-300">{{ ucfirst($order->status) }}</td>
                                        <td class="px-3 py-4 text-slate-600 dark:text-slate-300">R$ {{ number_format((float) $order->total_amount, 2, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-3 py-8 text-center text-slate-500 dark:text-slate-400">Nenhum pedido encontrado.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $orders->links() }}
                    </div>
                </div>

                @if ($canManageAcl && $aclData)
                    <div class="rounded-4 border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">ACL</h2>
                        <form method="POST" action="{{ route('backend.users.acl.update', $user) }}" class="mt-6 grid gap-6">
                            @csrf
                            @method('PUT')

                            <div class="grid gap-3">
                                <h3 class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Responsabilidades</h3>
                                @foreach ($aclData['responsibilities'] as $responsibility)
                                    <label class="flex items-start gap-3 rounded-3 border border-slate-200 px-4 py-3 text-sm dark:border-zinc-700">
                                        <input type="checkbox" name="responsibility_ids[]" value="{{ $responsibility['id'] }}" {{ $responsibility['selected'] ? 'checked' : '' }}>
                                        <span>
                                            <span class="block font-medium text-slate-900 dark:text-slate-100">{{ $responsibility['name'] }}</span>
                                            <span class="block text-slate-500 dark:text-slate-400">{{ $responsibility['description'] }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>

                            <div class="grid gap-3">
                                <h3 class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Permissões adicionais</h3>
                                @foreach ($aclData['modules'] as $module)
                                    <div class="rounded-3 border border-slate-200 p-4 dark:border-zinc-700">
                                        <div class="font-medium text-slate-900 dark:text-slate-100">{{ $module['label'] }}</div>
                                        <div class="mt-3 grid gap-2">
                                            @foreach ($module['permissions'] as $permission)
                                                <label class="flex items-start gap-3 text-sm">
                                                    <input type="checkbox" name="permission_ids[]" value="{{ $permission['id'] }}" {{ $permission['selected'] ? 'checked' : '' }}>
                                                    <span>
                                                        <span class="block font-medium text-slate-900 dark:text-slate-100">{{ $permission['label'] }}</span>
                                                        <span class="block text-slate-500 dark:text-slate-400">{{ $permission['description'] }}</span>
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="rounded-full bg-sky-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-400">Salvar ACL</button>
                            </div>
                        </form>
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection
