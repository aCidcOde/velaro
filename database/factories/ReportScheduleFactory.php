<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Agenda relatorio recorrente por cron com destinatarios; states dao estoque, financeiro e agenda desligada.
*/

namespace Database\Factories;

use App\Models\ReportSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportSchedule>
 */
class ReportScheduleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Agendamento padrao = "Relatorio semanal de vendas · Toda segunda-feira as 08:00"
        // da tela 3.9. `type` nao tem constante no model: usamos o rotulo literal do
        // prototipo, que a coluna varchar(60) sustenta. As chaves de `filters` seguem em
        // pt-BR: nao estao no mapa de anglicizacao e sao lidas pela tela 3.9 como estao.
        return [
            'name' => 'Relatório semanal de vendas',
            'type' => 'Vendas por período',
            'cron' => '0 8 * * 1',
            'recipients' => [fake()->safeEmail()],
            'filters' => [
                'comparar_com' => 'Período anterior',
                'revendedor' => 'Todos',
                'categoria' => 'Todas',
            ],
            'format' => 'pdf',
            'is_active' => true,
        ];
    }

    /**
     * "Relatorio de estoque · Todo dia 1o as 09:00".
     */
    public function monthlyStock(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Relatório de estoque',
            'type' => 'Estoque atual',
            'cron' => '0 9 1 * *',
        ]);
    }

    /**
     * "Relatorio financeiro mensal · Todo dia 5 as 10:00".
     */
    public function monthlyFinancial(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Relatório financeiro mensal',
            'type' => 'Financeiro',
            'cron' => '0 10 5 * *',
        ]);
    }

    /**
     * Agendamento desligado — nao entra na varredura do scheduler.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Agendamento que ja rodou pelo menos uma vez.
     */
    public function alreadyRun(): static
    {
        return $this->state(fn (array $attributes): array => [
            'last_run_at' => now()->subWeek(),
        ]);
    }
}
