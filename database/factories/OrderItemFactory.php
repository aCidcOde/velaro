<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 3);
        $unitPrice = fake()->randomFloat(2, 0, 200);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            // Item do scaffold nao tem aro: a variante e exclusiva do catalogo Velaro.
            'product_variant_id' => null,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => round($quantity * $unitPrice, 2),
            'status' => 'pending',
            'meta' => [
                'source' => fake()->randomElement(['manual', 'catalog', 'mobile']),
            ],
        ];
    }

    /**
     * Item com aro escolhido. A variante e sempre do mesmo produto do item.
     */
    public function comVariante(?ProductVariant $variant = null): static
    {
        if ($variant instanceof ProductVariant) {
            return $this->state(fn (array $attributes): array => [
                'product_id' => $variant->product_id,
                'product_variant_id' => $variant->getKey(),
            ]);
        }

        return $this->state(fn (array $attributes): array => [
            // Closure resolvida depois de `product_id`, para a variante nascer do mesmo produto.
            'product_variant_id' => fn (array $resolved): int => (int) ProductVariant::factory()
                ->createOne(['product_id' => $resolved['product_id']])
                ->getKey(),
        ]);
    }
}
