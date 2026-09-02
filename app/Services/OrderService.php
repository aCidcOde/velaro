<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function resolveOwnedCustomer(User $user, int $customerId): Customer
    {
        $customer = $user->customers()->find($customerId);

        if (! $customer instanceof Customer) {
            throw ValidationException::withMessages([
                'customer_id' => 'Selecione um cliente valido.',
            ]);
        }

        return $customer;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return Collection<int|string, Product>
     */
    public function resolveOwnedProducts(User $user, array $items): Collection
    {
        $productIds = collect($items)
            ->pluck('product_id')
            ->map(static fn ($productId): int => (int) $productId)
            ->unique()
            ->values();

        $products = $user->products()
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        if ($products->count() !== $productIds->count()) {
            throw ValidationException::withMessages([
                'items' => 'Um ou mais produtos selecionados nao pertencem ao usuario autenticado.',
            ]);
        }

        return $products;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  Collection<int|string, Product>  $products
     */
    public function syncItems(Order $order, array $items, Collection $products): void
    {
        $totalAmount = 0;

        $order->items()->delete();

        foreach ($items as $item) {
            /** @var Product $product */
            $product = $products->get((int) $item['product_id']);
            $quantity = max(1, (int) $item['quantity']);
            $unitPrice = array_key_exists('unit_price', $item) && $item['unit_price'] !== null
                ? (float) $item['unit_price']
                : (float) $product->price;
            $lineTotal = round($quantity * $unitPrice, 2);
            $totalAmount += $lineTotal;

            $order->items()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $lineTotal,
                'status' => $item['status'] ?? 'pending',
                'meta' => $item['meta'] ?? null,
            ]);
        }

        $order->update([
            'total_amount' => round($totalAmount, 2),
        ]);
    }
}
