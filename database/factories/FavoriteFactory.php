<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Cria o coracao da vitrine preso ao token do visitante; states amarram a loja e ao cliente conhecido.
*/

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\ResellerStore;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Favorite>
 */
class FavoriteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'reseller_store_id' => null,
            'customer_id' => null,
            'visitor_token' => Str::random(64),
        ];
    }

    public function inStore(?ResellerStore $store = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'reseller_store_id' => $store?->getKey() ?? ResellerStore::factory(),
        ]);
    }

    public function forCustomer(?Customer $customer = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'customer_id' => $customer?->getKey() ?? Customer::factory(),
        ]);
    }
}
