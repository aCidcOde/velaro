<?php

/*
[Modulo: app/Models]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Trilha de consentimento LGPD do consumidor final, revogavel e com evidencia de canal e data.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerConsent extends Model
{
    use HasFactory;

    public const TYPE_MARKETING = 'marketing';

    public const TYPE_TRANSACTIONAL = 'transactional';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'type',
        'granted',
        'granted_at',
        'revoked_at',
        'channel',
        'evidence',
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

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
