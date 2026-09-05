<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Registra transicao de status do chamado; states dao abertura, resolucao e mudanca feita por operador.
*/

namespace Database\Factories;

use App\Models\SupportStatusEvent;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Trilha de mudança de status do chamado. `from_status`/`to_status` são o vocabulário de
 * `SupportTicket::STATUS_*` — a tabela não tem vocabulário próprio. `channel` fica fora: é
 * nullable, sem default, e o model não declara os canais.
 *
 * @extends Factory<SupportStatusEvent>
 */
class SupportStatusEventFactory extends Factory
{
    /**
     * O padrão é o primeiro evento da trilha, coerente com o chamado que a factory cria junto
     * (que nasce em `open`). Para os demais degraus use os states.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_id' => SupportTicket::factory(),
            'from_status' => null,
            'to_status' => SupportTicket::STATUS_OPEN,
            'actor_id' => null,
            'note' => 'Chamado aberto pelo revendedor.',
        ];
    }

    /**
     * Primeiro evento da trilha: o chamado entrando na fila de atendimento.
     */
    public function opening(): static
    {
        return $this->state(fn (array $attributes): array => [
            'from_status' => null,
            'to_status' => SupportTicket::STATUS_OPEN,
            'note' => 'Chamado aberto pelo revendedor.',
        ]);
    }

    /**
     * Último degrau do vocabulário atual: o model não declara status de chamado fechado.
     */
    public function resolution(): static
    {
        return $this->state(fn (array $attributes): array => [
            'from_status' => SupportTicket::STATUS_IN_PROGRESS,
            'to_status' => SupportTicket::STATUS_RESOLVED,
            'note' => 'Chamado resolvido e encerrado com o revendedor.',
        ]);
    }

    /**
     * Evento com autor identificado — o padrão é a transição automática, sem ator.
     */
    public function byActor(?User $actor = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'actor_id' => $actor?->getKey() ?? User::factory(),
        ]);
    }
}
