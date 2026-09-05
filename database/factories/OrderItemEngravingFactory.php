<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Gera gravacao 1:1 do item com iniciais, data e contador de caracteres coerente; state zera a cobranca.
*/

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\OrderItemEngraving;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItemEngraving>
 */
class OrderItemEngravingFactory extends Factory
{
    /**
     * Preco da gravacao interna no prototipo Velaro: R$ 35,00 por alianca.
     */
    private const ENGRAVING_PRICE = 35.00;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // A data gravada e a mesma que aparece no texto — sao o mesmo fato.
        $engravedOn = fake()->dateTimeBetween('-2 years', '+1 year');

        // Iniciais do casal, sem repetir a mesma letra nos dois lados.
        $initials = fake()->randomElements(range('A', 'Z'), 2);

        $text = sprintf('%s & %s %s', $initials[0], $initials[1], $engravedOn->format('d.m.Y'));

        return [
            // order_item_id e UNIQUE: a gravacao e 1:1 com o item do pedido.
            'order_item_id' => OrderItem::factory(),
            'enabled' => true,
            'text' => $text,
            'date' => $engravedOn,
            // chars e o contador cobrado — precisa bater com o texto, nunca ser sorteado.
            'chars' => mb_strlen($text),
            'price' => self::ENGRAVING_PRICE,
        ];
    }

    /**
     * Item sem gravacao: o registro existe, mas nao conta caracteres nem cobra nada
     * (exatamente os defaults da migration).
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'enabled' => false,
            'text' => null,
            'date' => null,
            'chars' => 0,
            'price' => 0,
        ]);
    }
}
