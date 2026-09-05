<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Registra um degrau da analise do cadastro; states dao abertura, aprovacao, reprovacao e inativacao.
*/

namespace Database\Factories;

use App\Models\Reseller;
use App\Models\ResellerStatusEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Trilha de mudança de status do revendedor. `from_status`/`to_status` são o vocabulário de
 * `Reseller::STATUS_*` — a tabela não tem vocabulário próprio.
 *
 * @extends Factory<ResellerStatusEvent>
 */
class ResellerStatusEventFactory extends Factory
{
    /**
     * O padrão é o primeiro evento da trilha, coerente com o revendedor que a factory cria
     * junto (que nasce em `pre_cadastro`). Para os demais eventos use os states.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reseller_id' => Reseller::factory(),
            'from_status' => null,
            'to_status' => Reseller::STATUS_PRE_CADASTRO,
            'actor_id' => null,
            'note' => 'Pré-cadastro recebido pelo formulário público.',
        ];
    }

    /**
     * Primeiro evento da trilha: o cadastro entrando na fila de análise.
     */
    public function abertura(): static
    {
        return $this->state(fn (array $attributes): array => [
            'from_status' => null,
            'to_status' => Reseller::STATUS_PRE_CADASTRO,
            'note' => 'Pré-cadastro recebido pelo formulário público.',
        ]);
    }

    public function aprovacao(): static
    {
        return $this->state(fn (array $attributes): array => [
            'from_status' => Reseller::STATUS_PRE_CADASTRO,
            'to_status' => Reseller::STATUS_APROVADO,
            'note' => 'Cadastro aprovado após conferência dos documentos.',
        ]);
    }

    public function reprovacao(): static
    {
        return $this->state(fn (array $attributes): array => [
            'from_status' => Reseller::STATUS_PRE_CADASTRO,
            'to_status' => Reseller::STATUS_REPROVADO,
            'note' => 'Cadastro reprovado: CNAE incompatível com o comércio de joias.',
        ]);
    }

    public function inativacao(): static
    {
        return $this->state(fn (array $attributes): array => [
            'from_status' => Reseller::STATUS_APROVADO,
            'to_status' => Reseller::STATUS_INATIVO,
            'note' => 'Revendedor inativado por ausência de pedidos no período.',
        ]);
    }

    /**
     * Evento com autor identificado — o padrão é a transição automática, sem ator.
     */
    public function porAdmin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'actor_id' => User::factory()->admin(),
        ]);
    }
}
