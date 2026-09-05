<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Cria cofre da matriz com codigo unico; states marcam o local padrao de entrada e o cofre desativado.
*/

namespace Database\Factories;

use App\Models\StockLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockLocation>
 */
class StockLocationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Setor do cofre: letra + dois dígitos dá 2.600 códigos únicos, folga suficiente
        // para o UNIQUE(code) em qualquer seed sem esgotar o pool do fake()->unique().
        $setor = strtoupper((string) fake()->unique()->bothify('?##'));

        return [
            'code' => 'MTZ-COFRE-'.$setor,
            'name' => 'Matriz - Cofre '.$setor,
            'description' => 'Cofre '.$setor.' da matriz — peças acabadas prontas para expedição.',
            'is_default' => false,
            'is_active' => true,
        ];
    }

    /**
     * Cofre padrão da matriz — destino de entrada quando o local não é informado.
     */
    public function padrao(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => true,
        ]);
    }

    public function inativo(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
