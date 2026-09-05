<?php

/*
[Modulo: app/Models]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Lote semanal de faturamento Velaro para o lojista: unidade de pagamento, nota fiscal e remessa.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderBatch extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'reseller_id',
        'cut_date',
        'due_date',
        'status',
        'total_amount',
        'paid_at',
        'shipped_at',
        'arrived_at',
        'picked_up_at',
        'picked_up_by_name',
        'picked_up_by_document',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cut_date' => 'date',
            'due_date' => 'date',
            'total_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'shipped_at' => 'datetime',
            'arrived_at' => 'datetime',
            'picked_up_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Reseller, $this> */
    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'batch_id');
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'batch_id');
    }

    /** @return HasMany<Invoice, $this> */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'batch_id');
    }

    /** @return HasMany<Shipment, $this> */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'order_batch_id');
    }
}
