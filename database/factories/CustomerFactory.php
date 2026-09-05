<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Reseller;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            // FK nullable: cliente do scaffold nao nasce preso a um revendedor.
            'reseller_id' => null,
            'name' => fake()->name(),
            'person_type' => Customer::PERSON_TYPE_INDIVIDUAL,
            'company_name' => fake()->boolean(30) ? fake()->company() : null,
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'document' => fake()->bothify('DOC-####'),
            'notes' => fake()->boolean(50) ? fake()->sentence() : null,
            'meta' => [
                'segment' => fake()->randomElement(['standard', 'priority', 'partner']),
            ],
        ];
    }

    /**
     * Cliente da carteira de um revendedor.
     */
    public function forReseller(?Reseller $reseller = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'reseller_id' => $reseller?->getKey() ?? Reseller::factory(),
        ]);
    }

    /**
     * Cliente pessoa juridica: exige razao social.
     */
    public function company(): static
    {
        return $this->state(fn (array $attributes): array => [
            'person_type' => Customer::PERSON_TYPE_COMPANY,
            'company_name' => fake()->company(),
        ]);
    }
}
