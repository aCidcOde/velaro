<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Gera mensagem do lojista na thread; states dao a resposta da Velaro e a nota interna que nunca vaza.
*/

namespace Database\Factories;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportMessage>
 */
class SupportMessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_id' => SupportTicket::factory(),
            'author_id' => User::factory(),
            'author_role' => SupportMessage::AUTHOR_ROLE_REVENDEDOR,
            'body' => fake()->randomElement([
                'Boa tarde! A cliente pediu a troca do aro 16 para o aro 18. Como devo proceder?',
                'O pedido chegou sem a gravação interna que foi combinada na compra.',
                'Preciso de ajuda para publicar a vitrine: o botão fica carregando e nada acontece.',
                'O boleto foi pago ontem e o pedido continua aguardando compensação.',
            ]),
            'is_internal_note' => false,
        ];
    }

    public function daVelaro(): static
    {
        return $this->state(fn (array $attributes): array => [
            'author_role' => SupportMessage::AUTHOR_ROLE_VELARO,
            'body' => fake()->randomElement([
                'Recebemos o chamado e já encaminhamos a troca para a produção.',
                'O pagamento foi identificado e o pedido seguiu para produção.',
                'Pode publicar a vitrine novamente: o ajuste foi aplicado no painel.',
            ]),
        ]);
    }

    public function notaInterna(): static
    {
        return $this->state(fn (array $attributes): array => [
            'author_role' => SupportMessage::AUTHOR_ROLE_VELARO,
            'is_internal_note' => true,
            'body' => fake()->randomElement([
                'Conferir com a produção se o aro 18 está disponível antes de responder.',
                'Revendedor já abriu chamado parecido no mês passado; verificar histórico.',
            ]),
        ]);
    }
}
