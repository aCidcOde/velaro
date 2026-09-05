<?php

/*
[Modulo: app/Models]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Agendamento recorrente de relatorio, com destinatarios, filtros e formato de saida.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportSchedule extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'type',
        'cron',
        'recipients',
        'filters',
        'format',
        'is_active',
        'last_run_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recipients' => 'array',
            'filters' => 'array',
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
        ];
    }

    /** @return HasMany<ReportExport, $this> */
    public function exports(): HasMany
    {
        return $this->hasMany(ReportExport::class);
    }
}
