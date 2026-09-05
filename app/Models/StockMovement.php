<?php

/*
[Modulo: app/Models]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Razao do estoque com before/after: entrada, saida, ajuste, reserva e producao, sempre com ator.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    public const TYPE_ENTRADA = 'entrada';

    public const TYPE_SAIDA = 'saida';

    public const TYPE_AJUSTE = 'ajuste';

    public const TYPE_RESERVA = 'reserva';

    public const TYPE_PRODUCAO = 'producao';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'stock_item_id',
        'type',
        'qty',
        'before',
        'after',
        'reason',
        'actor_id',
        'order_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'before' => 'integer',
            'after' => 'integer',
        ];
    }

    /** @return BelongsTo<StockItem, $this> */
    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
