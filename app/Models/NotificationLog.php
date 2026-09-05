<?php

/*
[Modulo: app/Models]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Trilha de envio dos avisos transacionais por e-mail e WhatsApp, sempre disparados por job.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use HasFactory;

    public const TYPE_ORDER_READY = 'order_ready';

    /**
     * Aviso de recebimento do cadastro de lojista, disparado na tela 1.4 e lido
     * pela tela 1.5: o unico aviso que sai antes de existir pedido.
     */
    public const TYPE_REGISTRATION_RECEIVED = 'registration_received';

    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const RECIPIENT_TYPE_RESELLER = 'reseller';

    public const RECIPIENT_TYPE_CUSTOMER = 'customer';

    /**
     * Fila de envio. `pending` e o default da migration: a mensagem entrou na
     * fila e ninguem confirmou a entrega ainda. `sent` e `failed` sao carimbados
     * por quem fala com o provedor.
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'channel',
        'recipient',
        'recipient_type',
        'order_id',
        'reseller_id',
        'customer_id',
        'status',
        'sent_at',
        'provider_message_id',
        'error_message',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Reseller, $this> */
    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
