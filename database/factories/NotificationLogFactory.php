<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Monta disparo transacional pendente; states escolhem o canal e amarram a pedido, lojista ou consumidor.
*/

namespace Database\Factories;

use App\Models\Customer;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Models\Reseller;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationLog>
 */
class NotificationLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // As tres FKs sao nullable e nascem nulas: o vinculo vem pelos states.
        // `status` fica no default da migration ('pendente') — o model nao declara
        // constante para os demais valores da fila de envio.
        return [
            'type' => NotificationLog::TYPE_PEDIDO_PRONTO,
            'channel' => NotificationLog::CHANNEL_EMAIL,
            'recipient' => fake()->safeEmail(),
            'recipient_type' => NotificationLog::RECIPIENT_TYPE_REVENDEDOR,
            'order_id' => null,
            'reseller_id' => null,
            'customer_id' => null,
            'status' => 'pendente',
        ];
    }

    /**
     * Disparo amarrado ao pedido que chegou para retirada.
     */
    public function paraPedido(?Order $order = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'order_id' => $order instanceof Order ? $order->getKey() : Order::factory(),
        ]);
    }

    /**
     * Aviso enviado ao lojista.
     */
    public function paraRevendedor(?Reseller $reseller = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'reseller_id' => $reseller instanceof Reseller ? $reseller->getKey() : Reseller::factory(),
            'recipient_type' => NotificationLog::RECIPIENT_TYPE_REVENDEDOR,
        ]);
    }

    /**
     * Aviso enviado ao consumidor final, em nome da loja do revendedor.
     */
    public function paraCliente(?Customer $customer = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'customer_id' => $customer instanceof Customer ? $customer->getKey() : Customer::factory(),
            'recipient_type' => NotificationLog::RECIPIENT_TYPE_CLIENTE,
        ]);
    }

    /**
     * Canal WhatsApp — o destinatario passa a ser telefone.
     */
    public function porWhatsapp(): static
    {
        return $this->state(fn (array $attributes): array => [
            'channel' => NotificationLog::CHANNEL_WHATSAPP,
            'recipient' => fake()->numerify('(##) 9####-####'),
        ]);
    }

    /**
     * Canal e-mail — o destinatario passa a ser endereco eletronico.
     */
    public function porEmail(): static
    {
        return $this->state(fn (array $attributes): array => [
            'channel' => NotificationLog::CHANNEL_EMAIL,
            'recipient' => fake()->safeEmail(),
        ]);
    }
}
