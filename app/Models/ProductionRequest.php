<?php

/*
[Modulo: app/Models]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Pedido de producao ou reposicao em aberto, com quantidade pedida contra entregue, prazo e dono.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDENTE = 'pendente';

    public const PRIORITY_NORMAL = 'normal';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_variant_id',
        'stock_location_id',
        'qty_requested',
        'qty_delivered',
        'status',
        'priority',
        'due_date',
        'note',
        'requested_by',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty_requested' => 'integer',
            'qty_delivered' => 'integer',
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ProductVariant, $this> */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /** @return BelongsTo<StockLocation, $this> */
    public function stockLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class);
    }

    /** @return BelongsTo<User, $this> */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
