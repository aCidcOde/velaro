@php
    $itemRows = old('items', isset($order) && $order->exists
        ? $order->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
            'status' => $item->status,
        ])->all()
        : [['product_id' => '', 'quantity' => 1, 'unit_price' => '', 'status' => 'pending']]);
@endphp

@csrf
@isset($method)
    @method($method)
@endisset

<div class="grid gap-5 md:grid-cols-2">
    <label class="grid gap-2 text-sm">
        <span class="font-medium text-slate-700 dark:text-slate-200">Cliente</span>
        <select name="customer_id" class="rounded-3 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900" required>
            <option value="">Selecione</option>
            @foreach ($customers as $customerOption)
                <option value="{{ $customerOption->id }}" @selected((string) old('customer_id', $order->customer_id) === (string) $customerOption->id)>
                    {{ $customerOption->name }}
                </option>
            @endforeach
        </select>
        @error('customer_id') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
    </label>

    <label class="grid gap-2 text-sm">
        <span class="font-medium text-slate-700 dark:text-slate-200">Status</span>
        <select name="status" class="rounded-3 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
            @foreach (['draft' => 'Rascunho', 'submitted' => 'Enviado', 'processing' => 'Processando', 'completed' => 'Concluído', 'canceled' => 'Cancelado'] as $value => $label)
                <option value="{{ $value }}" @selected((string) old('status', $order->status ?: 'draft') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
    </label>

    <label class="grid gap-2 text-sm md:col-span-2">
        <span class="font-medium text-slate-700 dark:text-slate-200">Referência interna</span>
        <input type="text" name="reference" value="{{ old('reference', $order->reference) }}" class="rounded-3 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
        @error('reference') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
    </label>

    <label class="grid gap-2 text-sm md:col-span-2">
        <span class="font-medium text-slate-700 dark:text-slate-200">Observações</span>
        <textarea name="notes" rows="4" class="rounded-4 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">{{ old('notes', $order->notes) }}</textarea>
        @error('notes') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
    </label>
    @error('items') <span class="text-xs text-rose-500 md:col-span-2">{{ $message }}</span> @enderror
</div>

<div class="grid gap-4">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Itens</h2>
        <button type="button" id="add-order-item" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold dark:border-zinc-700">Adicionar item</button>
    </div>

    <div id="order-items" class="grid gap-4">
        @foreach ($itemRows as $index => $row)
            <div class="order-item-row grid gap-4 rounded-4 border border-slate-200 p-4 dark:border-zinc-700 md:grid-cols-4">
                <label class="grid gap-2 text-sm">
                    <span class="font-medium text-slate-700 dark:text-slate-200">Produto</span>
                    <select name="items[{{ $index }}][product_id]" class="rounded-3 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900" required>
                        <option value="">Selecione</option>
                        @foreach ($products as $productOption)
                            <option value="{{ $productOption->id }}" @selected((string) ($row['product_id'] ?? '') === (string) $productOption->id)>
                                {{ $productOption->name }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <label class="grid gap-2 text-sm">
                    <span class="font-medium text-slate-700 dark:text-slate-200">Quantidade</span>
                    <input type="number" min="1" name="items[{{ $index }}][quantity]" value="{{ $row['quantity'] ?? 1 }}" class="rounded-3 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900" required>
                </label>
                <label class="grid gap-2 text-sm">
                    <span class="font-medium text-slate-700 dark:text-slate-200">Preço unitário</span>
                    <input type="number" min="0" step="0.01" name="items[{{ $index }}][unit_price]" value="{{ $row['unit_price'] ?? '' }}" class="rounded-3 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
                </label>
                <div class="grid gap-2 text-sm">
                    <label class="grid gap-2">
                        <span class="font-medium text-slate-700 dark:text-slate-200">Status do item</span>
                        <select name="items[{{ $index }}][status]" class="rounded-3 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
                            @foreach (['pending' => 'Pendente', 'processing' => 'Processando', 'completed' => 'Concluído'] as $value => $label)
                                <option value="{{ $value }}" @selected((string) ($row['status'] ?? 'pending') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="button" class="remove-order-item text-left text-sm font-medium text-rose-600">Remover item</button>
                </div>
            </div>
        @endforeach
    </div>
</div>

<template id="order-item-template">
    <div class="order-item-row grid gap-4 rounded-4 border border-slate-200 p-4 dark:border-zinc-700 md:grid-cols-4">
        <label class="grid gap-2 text-sm">
            <span class="font-medium text-slate-700 dark:text-slate-200">Produto</span>
            <select data-name="product_id" class="rounded-3 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900" required>
                <option value="">Selecione</option>
                @foreach ($products as $productOption)
                    <option value="{{ $productOption->id }}">{{ $productOption->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="grid gap-2 text-sm">
            <span class="font-medium text-slate-700 dark:text-slate-200">Quantidade</span>
            <input type="number" min="1" value="1" data-name="quantity" class="rounded-3 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900" required>
        </label>
        <label class="grid gap-2 text-sm">
            <span class="font-medium text-slate-700 dark:text-slate-200">Preço unitário</span>
            <input type="number" min="0" step="0.01" data-name="unit_price" class="rounded-3 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
        </label>
        <div class="grid gap-2 text-sm">
            <label class="grid gap-2">
                <span class="font-medium text-slate-700 dark:text-slate-200">Status do item</span>
                <select data-name="status" class="rounded-3 border border-slate-200 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
                    <option value="pending">Pendente</option>
                    <option value="processing">Processando</option>
                    <option value="completed">Concluído</option>
                </select>
            </label>
            <button type="button" class="remove-order-item text-left text-sm font-medium text-rose-600">Remover item</button>
        </div>
    </div>
</template>

@push('scripts')
    <script>
        (() => {
            const container = document.getElementById('order-items');
            const addButton = document.getElementById('add-order-item');
            const template = document.getElementById('order-item-template');

            if (!container || !addButton || !template) {
                return;
            }

            const applyNames = () => {
                Array.from(container.querySelectorAll('.order-item-row')).forEach((row, index) => {
                    row.querySelectorAll('[data-name]').forEach((field) => {
                        field.name = `items[${index}][${field.dataset.name}]`;
                    });
                });
            };

            const attachRemoval = (root) => {
                root.querySelectorAll('.remove-order-item').forEach((button) => {
                    button.addEventListener('click', () => {
                        if (container.children.length === 1) {
                            return;
                        }

                        button.closest('.order-item-row')?.remove();
                        applyNames();
                    });
                });
            };

            addButton.addEventListener('click', () => {
                const fragment = template.content.cloneNode(true);
                container.appendChild(fragment);
                applyNames();
                attachRemoval(container);
            });

            applyNames();
            attachRemoval(container);
        })();
    </script>
@endpush
