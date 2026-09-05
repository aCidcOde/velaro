<?php

/*
[Modulo: app/Models]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Campanha efetivamente aplicada ao pedido, com o desconto congelado em reais para auditoria.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPromotion extends Model
{
    use HasFactory;

    public const TYPE_DESCONTO_PROGRESSIVO = 'desconto_progressivo';

    public const TYPE_PRECO_ESPECIAL = 'preco_especial';

    public const TYPE_FRETE_GRATIS = 'frete_gratis';

    public const TYPE_DESCONTO_FIXO = 'desconto_fixo';

    public const TYPE_LANCAMENTO = 'lancamento';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'promotion_id',
        'type',
        'discount_amount',
        'applied_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'discount_amount' => 'decimal:2',
            'applied_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Promotion, $this> */
    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }
}
