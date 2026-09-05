<?php

namespace Database\Factories;

use App\Models\Reseller;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'document' => fake()->bothify('DOC-####'),
            'email_verified_at' => now(),
            'password' => static::$password ??= 'password',
            'remember_token' => Str::random(10),
            'two_factor_secret' => Str::random(10),
            'two_factor_recovery_codes' => Str::random(10),
            'two_factor_confirmed_at' => now(),
            'theme_preference' => 'dark',
            'is_admin' => false,
            'is_agent' => false,
            'is_blocked' => false,
            // Usuario do scaffold nao pertence a revendedor nenhum: `Reseller::users()` e hasMany,
            // preencher isso no default criaria revendedor em todo teste do template.
            'reseller_id' => null,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => [
            'email_verified_at' => null,
        ]);
    }

    public function withoutTwoFactor(): static
    {
        return $this->state(fn () => [
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'is_admin' => true,
        ]);
    }

    public function agent(): static
    {
        return $this->state(fn () => [
            'is_agent' => true,
        ]);
    }

    public function blocked(): static
    {
        return $this->state(fn () => [
            'is_blocked' => true,
        ]);
    }

    /**
     * Vincula o usuario a um revendedor (lojista do portal B2B).
     */
    public function paraRevendedor(?Reseller $reseller = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'reseller_id' => $reseller?->getKey() ?? Reseller::factory(),
        ]);
    }
}
