@csrf
@isset($method)
    @method($method)
@endisset

<div class="grid gap-5 md:grid-cols-2">
    <label class="grid gap-2 text-sm">
        <span class="font-medium text-slate-700 dark:text-slate-200">Nome</span>
        <input type="text" name="name" value="{{ old('name', $product->name) }}" class="rounded-3 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900" required>
        @error('name') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
    </label>
    <label class="grid gap-2 text-sm">
        <span class="font-medium text-slate-700 dark:text-slate-200">SKU</span>
        <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" class="rounded-3 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
        @error('sku') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
    </label>
    <label class="grid gap-2 text-sm">
        <span class="font-medium text-slate-700 dark:text-slate-200">Preço</span>
        <input type="number" name="price" step="0.01" min="0" value="{{ old('price', $product->price) }}" class="rounded-3 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900" required>
        @error('price') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
    </label>
    <label class="flex items-center gap-3 rounded-3 border border-slate-200 px-4 py-3 text-sm dark:border-zinc-700">
        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->exists ? $product->is_active : true) ? 'checked' : '' }}>
        <span class="font-medium text-slate-700 dark:text-slate-200">Produto ativo</span>
    </label>
    <label class="grid gap-2 text-sm md:col-span-2">
        <span class="font-medium text-slate-700 dark:text-slate-200">Descrição</span>
        <textarea name="description" rows="5" class="rounded-4 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">{{ old('description', $product->description) }}</textarea>
        @error('description') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
    </label>
</div>
