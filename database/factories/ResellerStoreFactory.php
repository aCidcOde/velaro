<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Monta vitrine white-label despublicada com cores e slug; states dao publicacao, marca propria e dominio.
*/

namespace Database\Factories;

use App\Models\Reseller;
use App\Models\ResellerStore;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ResellerStore>
 */
class ResellerStoreFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = (string) fake()->randomElement([
            'Joalheria Aurora',
            'Casa das Alianças',
            'Ateliê Ouro Fino',
            'Ourivesaria Bela Vista',
            'Prata & Ouro Joias',
        ]);

        $slug = Str::slug($name).'-'.fake()->unique()->numerify('####');

        return [
            'reseller_id' => Reseller::factory(),
            'name' => $name,
            'slogan' => fake()->randomElement([
                'Alianças que contam histórias',
                'Joias para momentos únicos',
                'Tradição em ouro 18K',
                'O par certo para o seu sim',
            ]),
            'slug' => $slug,
            // `domain` é o domínio próprio, opcional: a rota padrão da vitrine é /loja/{slug}
            // (tela 2.9). Nulo por padrão para não fingir que toda loja contratou domínio —
            // e é UNIQUE, então ocupá-lo à toa só encurta o espaço do índice.
            'domain' => null,
            'phone' => fake()->numerify('(##) 3###-####'),
            'whatsapp' => fake()->numerify('(##) 9####-####'),
            'email' => 'contato@'.$slug.'.com.br',
            'address' => 'Rua '.fake()->lastName().', '.fake()->numberBetween(10, 1999).' - '.fake()->randomElement(['Centro', 'Jardim América', 'Vila Nova', 'Boa Vista']),
            'color_primary' => '#800020',
            'color_secondary' => '#B8860B',
            'color_background' => '#FFFFFF',
            'color_text' => '#1A1A1A',
            'own_brand_only' => false,
            'hide_supplier_brand' => false,
            'show_prices' => true,
            'pickup_only' => true,
            'payment_in_store' => true,
            'is_active' => false,
        ];
    }

    /**
     * Vitrine no ar. `is_active` nasce `false` (default da migration) e `published_at` só existe
     * a partir da publicação.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => true,
            'published_at' => now(),
        ]);
    }

    public function ownBrand(): static
    {
        return $this->state(fn (array $attributes): array => [
            'own_brand_only' => true,
            'hide_supplier_brand' => true,
        ]);
    }

    /**
     * Loja servida em domínio próprio, e não pela rota /loja/{slug}.
     */
    public function withCustomDomain(?string $domain = null): static
    {
        return $this->state(function (array $attributes) use ($domain): array {
            $slug = $attributes['slug'] ?? null;

            return [
                'domain' => $domain ?? (is_string($slug) ? $slug : 'loja-'.fake()->unique()->numerify('####')).'.com.br',
            ];
        });
    }
}
