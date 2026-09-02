@csrf
@isset($method)
    @method($method)
@endisset

<div class="grid gap-5 md:grid-cols-2">
    <label class="grid gap-2 text-sm">
        <span class="font-medium text-slate-700 dark:text-slate-200">Nome</span>
        <input type="text" name="name" value="{{ old('name', $customer->name) }}" class="rounded-3 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900" required>
        @error('name') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
    </label>
    <label class="grid gap-2 text-sm">
        <span class="font-medium text-slate-700 dark:text-slate-200">Empresa</span>
        <input type="text" name="company_name" value="{{ old('company_name', $customer->company_name) }}" class="rounded-3 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
        @error('company_name') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
    </label>
    <label class="grid gap-2 text-sm">
        <span class="font-medium text-slate-700 dark:text-slate-200">E-mail</span>
        <input type="email" name="email" value="{{ old('email', $customer->email) }}" class="rounded-3 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
        @error('email') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
    </label>
    <label class="grid gap-2 text-sm">
        <span class="font-medium text-slate-700 dark:text-slate-200">Telefone</span>
        <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}" class="rounded-3 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
        @error('phone') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
    </label>
    <label class="grid gap-2 text-sm md:col-span-2">
        <span class="font-medium text-slate-700 dark:text-slate-200">Documento</span>
        <input type="text" name="document" value="{{ old('document', $customer->document) }}" class="rounded-3 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
        @error('document') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
    </label>
    <label class="grid gap-2 text-sm md:col-span-2">
        <span class="font-medium text-slate-700 dark:text-slate-200">Observações</span>
        <textarea name="notes" rows="5" class="rounded-4 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">{{ old('notes', $customer->notes) }}</textarea>
        @error('notes') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
    </label>
</div>
