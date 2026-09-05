<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Reseller;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $totalAmount = fake()->randomFloat(2, 0, 500);

        // As parcelas saem do total para a identidade valer sempre:
        // total = subtotal + gravacao + frete - desconto.
        $engravingAmount = round($totalAmount * fake()->randomElement([0.0, 0.05, 0.1]), 2);
        $shippingAmount = round($totalAmount * fake()->randomElement([0.0, 0.04, 0.08]), 2);
        $discountAmount = round($totalAmount * fake()->randomElement([0.0, 0.05]), 2);
        $subtotalAmount = round($totalAmount - $engravingAmount - $shippingAmount + $discountAmount, 2);

        return [
            'user_id' => User::factory(),
            // FKs Velaro nulas no default: pedido do scaffold nao tem revendedor,
            // nao entra em lote de faturamento e nao viaja em remessa.
            'reseller_id' => null,
            'customer_id' => Customer::factory(),
            'batch_id' => null,
            'shipment_id' => null,
            'public_number' => 'ORD'.fake()->unique()->numerify('######'),
            'reference' => strtoupper(fake()->bothify('REF-####')),
            'status' => fake()->randomElement(['draft', 'awaiting_payment', 'paid', 'in_progress', 'completed', 'canceled', 'error']),
            // Status canonicos do modulo Velaro — independentes entre si e do espelho `status`.
            'operational_status' => Order::OPERATIONAL_STATUS_REGISTRADO,
            'payment_status' => Order::PAYMENT_STATUS_PENDENTE,
            'total_amount' => $totalAmount,
            'subtotal_amount' => $subtotalAmount,
            'engraving_amount' => $engravingAmount,
            'shipping_amount' => $shippingAmount,
            'discount_amount' => $discountAmount,
            'currency' => 'BRL',
            'notes' => fake()->sentence(),
            'meta' => [
                'channel' => fake()->randomElement(['web', 'mobile', 'admin']),
            ],
        ];
    }

    /**
     * Pedido feito por um revendedor no portal B2B.
     */
    public function paraRevendedor(?Reseller $reseller = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'reseller_id' => $reseller?->getKey() ?? Reseller::factory(),
        ]);
    }

    /**
     * Pedido que chegou na loja e aguarda o cliente final.
     */
    public function prontoParaRetirada(): static
    {
        return $this->state(fn (array $attributes): array => [
            'operational_status' => Order::OPERATIONAL_STATUS_PRONTO_RETIRADA,
            'arrived_at' => now()->subDay(),
        ]);
    }

    /**
     * Pedido ja entregue ao cliente final no balcao da loja.
     */
    public function retirado(): static
    {
        return $this->state(fn (array $attributes): array => [
            'operational_status' => Order::OPERATIONAL_STATUS_RETIRADO,
            'arrived_at' => now()->subDays(3),
            'retirado_em' => now(),
            'retirado_por' => fake()->name(),
            'retirado_por_documento' => fake()->numerify('###.###.###-##'),
        ]);
    }
}
