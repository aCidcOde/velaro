<?php

/*
[Modulo: app/Models]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Timeline do pedido com ator e nota, separando as transicoes operacionais das financeiras.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusEvent extends Model
{
    use HasFactory;

    // Os dois escopos da timeline do pedido: `operational_status` e `payment_status` sao
    // independentes entre si e cada transicao e registrada sob o escopo a que pertence.
    public const SCOPE_OPERATIONAL = 'operational';

    public const SCOPE_PAYMENT = 'payment';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'scope',
        'from_status',
        'to_status',
        'actor_id',
        'note',
    ];

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
