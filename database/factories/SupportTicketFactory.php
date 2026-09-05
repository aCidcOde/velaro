<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Abre chamado do lojista com codigo, assunto e categoria coerentes; states cobrem status, prioridade e vinculo.
*/

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Reseller;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTicket>
 */
class SupportTicketFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $category = (string) fake()->randomElement([
            'Pedidos',
            'Financeiro',
            'Vitrine / Loja',
            'Personalização da loja',
        ]);

        $subject = match ($category) {
            'Financeiro' => fake()->randomElement([
                'Boleto não compensado após o pagamento',
                'Divergência no valor da nota fiscal',
            ]),
            'Vitrine / Loja' => fake()->randomElement([
                'Produto não aparece na vitrine',
                'Vitrine fora do ar após a publicação',
            ]),
            'Personalização da loja' => fake()->randomElement([
                'Logo cortada no cabeçalho da loja',
                'Cores da loja não salvam',
            ]),
            default => fake()->randomElement([
                'Troca de aliança - aro incorreto',
                'Dúvida sobre o prazo de produção',
                'Pedido retirado sem a gravação combinada',
            ]),
        };

        return [
            'code' => sprintf('SUP-%d-%04d', now()->year, fake()->unique()->numberBetween(1, 9999)),
            'reseller_id' => Reseller::factory(),
            'order_id' => null,
            'customer_id' => null,
            'subject' => $subject,
            'category' => $category,
            'priority' => SupportTicket::PRIORITY_MEDIA,
            'status' => SupportTicket::STATUS_ABERTA,
            'assignee_id' => null,
        ];
    }

    public function aberta(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SupportTicket::STATUS_ABERTA,
            'first_response_at' => null,
            'resolved_at' => null,
            'closed_at' => null,
        ]);
    }

    public function emAtendimento(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SupportTicket::STATUS_EM_ATENDIMENTO,
            'assignee_id' => $attributes['assignee_id'] ?? User::factory(),
            'first_response_at' => now()->subHours(3),
            'resolved_at' => null,
            'closed_at' => null,
        ]);
    }

    public function resolvida(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SupportTicket::STATUS_RESOLVIDO,
            'assignee_id' => $attributes['assignee_id'] ?? User::factory(),
            'first_response_at' => now()->subDay(),
            'resolved_at' => now(),
        ]);
    }

    public function prioridadeAlta(): static
    {
        return $this->state(fn (array $attributes): array => [
            'priority' => SupportTicket::PRIORITY_ALTA,
        ]);
    }

    public function atribuida(?User $assignee = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'assignee_id' => $assignee?->getKey() ?? User::factory(),
        ]);
    }

    public function doPedido(?Order $order = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'order_id' => $order?->getKey() ?? Order::factory(),
        ]);
    }

    public function doCliente(?Customer $customer = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'customer_id' => $customer?->getKey() ?? Customer::factory(),
        ]);
    }
}
