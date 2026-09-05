<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Cria etiqueta do vocabulario do suporte (Troca, Tamanho, Alianca) com slug unico por linha.
*/

namespace Database\Factories;

use App\Models\SupportTag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SupportTag>
 */
class SupportTagFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = (string) fake()->randomElement([
            'Troca',
            'Tamanho',
            'Aliança',
            'Ouro 18K',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('####'),
        ];
    }
}
