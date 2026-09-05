<?php

/*
[Modulo: app/Models]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Rodada da verificacao automatica de CNPJ e CNAE: triagem com score, nunca decisao final.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerVerification extends Model
{
    use HasFactory;

    public const STATUS_PENDENTE = 'pendente';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'reseller_id',
        'status',
        'cnpj_valido',
        'empresa_ativa',
        'cnaes_compativeis',
        'documentacao_enviada',
        'score',
        'result',
        'raw_payload',
        'checked_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cnpj_valido' => 'boolean',
            'empresa_ativa' => 'boolean',
            'cnaes_compativeis' => 'boolean',
            'documentacao_enviada' => 'boolean',
            'score' => 'integer',
            'result' => 'array',
            'raw_payload' => 'array',
            'checked_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Reseller, $this> */
    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }
}
