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

    // Unico valor de `scope` sustentado pelo schema: o default da migration de
    // order_status_events. O escopo financeiro ainda nao tem vocabulario canonico — o
    // diagrama ER declara `scope (operacional|financeiro)`, em pt-BR, e ja diverge do
    // default gravado no banco. Declarar o segundo valor aqui seria escolher a string no
    // escuro: qualquer consulta pelo termo errado volta vazia em silencio.
    public const SCOPE_OPERATIONAL = 'operational';

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
