<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Enfileira exportacao avulsa com filtros e formato; states ligam a um agendamento e a quem pediu.
*/

namespace Database\Factories;

use App\Models\ReportExport;
use App\Models\ReportSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportExport>
 */
class ReportExportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Exportacao avulsa: nasce sem agendamento e sem autor, na fila.
        // `type` e `status` nao tem constante no model — `type` usa o rotulo literal
        // do prototipo (tela 3.9) e `status` fica no default da migration.
        return [
            'report_schedule_id' => null,
            'type' => 'Vendas por período',
            'filters' => [
                'periodo' => '01/05/2026 até 31/05/2026',
                'revendedor' => 'Todos',
                'categoria' => 'Todas',
            ],
            'format' => 'pdf',
            'status' => 'pendente',
            'generated_by' => null,
        ];
    }

    /**
     * Exportacao disparada por um agendamento, e nao pelo botao "Exportar".
     */
    public function paraAgendamento(?ReportSchedule $schedule = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'report_schedule_id' => $schedule instanceof ReportSchedule
                ? $schedule->getKey()
                : ReportSchedule::factory(),
        ]);
    }

    /**
     * Exportacao pedida por um usuario do painel.
     */
    public function geradoPor(?User $user = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'generated_by' => $user instanceof User ? $user->getKey() : User::factory(),
        ]);
    }
}
