<?php

/*
[Modulo: app/Models]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Aceites do lojista no cadastro, gravados com versao do texto, IP e agente para prova de LGPD.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerConsent extends Model
{
    use HasFactory;

    public const TYPE_TERMS = 'terms';

    public const TYPE_PRIVACY_POLICY = 'privacy_policy';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'reseller_id',
        'type',
        'granted',
        'document_version',
        'granted_at',
        'revoked_at',
        'ip_address',
        'user_agent',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'granted' => 'boolean',
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Reseller, $this> */
    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }
}
