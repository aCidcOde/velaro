<?php

/*
[Modulo: app/Models]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Remessa fisica do lote ate a loja: transportadora, rastreio, liberacao logistica e datas.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    use HasFactory;

    public const STATUS_AWAITING_RELEASE = 'awaiting_release';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'order_batch_id',
        'reseller_id',
        'status',
        'carrier',
        'tracking_code',
        'tracking_url',
        'released_by',
        'released_at',
        'shipped_at',
        'estimated_at',
        'delivered_at',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'released_at' => 'datetime',
            'shipped_at' => 'datetime',
            'estimated_at' => 'date',
            'delivered_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<OrderBatch, $this> */
    public function orderBatch(): BelongsTo
    {
        return $this->belongsTo(OrderBatch::class);
    }

    /** @return BelongsTo<Reseller, $this> */
    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    /** @return BelongsTo<User, $this> */
    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
