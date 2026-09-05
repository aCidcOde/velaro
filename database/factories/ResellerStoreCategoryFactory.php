<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Coloca uma categoria na vitrine do lojista, na posicao em que ele decidiu exibi-la.
*/

namespace Database\Factories;

use App\Models\Category;
use App\Models\ResellerStore;
use App\Models\ResellerStoreCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResellerStoreCategory>
 */
class ResellerStoreCategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reseller_store_id' => ResellerStore::factory(),
            'category_id' => Category::factory(),
            'position' => fake()->numberBetween(0, 20),
        ];
    }
}
