<?php

/*
[Modulo: app/Models]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Os tres uploads obrigatorios do cadastro: contrato social, documento do socio e cartao CNPJ.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerDocument extends Model
{
    use HasFactory;

    public const TYPE_CONTRATO_SOCIAL = 'contrato_social';

    public const TYPE_DOCUMENTO_SOCIO = 'documento_socio';

    public const TYPE_CARTAO_CNPJ = 'cartao_cnpj';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'reseller_id',
        'type',
        'original_name',
        'disk',
        'path',
        'size_bytes',
        'mime',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    /** @return BelongsTo<Reseller, $this> */
    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }
}
