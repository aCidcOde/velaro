<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    use SoftDeletes;

    // Status canonicos do modulo Velaro (decisao 1.2 de docs/banco-de-dados.md):
    // `operational_status` e `payment_status` sao a verdade e sao independentes entre si.
    // `status` (campo do scaffold) permanece apenas como espelho derivado, por compatibilidade
    // com OrderWorkflowStatusService — nada no modulo Velaro deve le-lo como autoridade.

    public const OPERATIONAL_STATUS_REGISTRADO = 'registrado';

    public const OPERATIONAL_STATUS_PAGAMENTO_CONFIRMADO = 'pagamento_confirmado';

    public const OPERATIONAL_STATUS_PRODUCAO_ANDAMENTO = 'producao_andamento';

    public const OPERATIONAL_STATUS_PRODUCAO_FINALIZADA = 'producao_finalizada';

    public const OPERATIONAL_STATUS_EM_TRANSPORTE = 'em_transporte';

    public const OPERATIONAL_STATUS_PRONTO_RETIRADA = 'pronto_retirada';

    public const OPERATIONAL_STATUS_RETIRADO = 'retirado';

    public const PAYMENT_STATUS_PENDENTE = 'pendente';

    public const PAYMENT_STATUS_AGUARDANDO_COMPENSACAO = 'aguardando_compensacao';

    public const PAYMENT_STATUS_PAGO = 'pago';

    public const PAYMENT_STATUS_VENCIDO = 'vencido';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_number',
        'user_id',
        'reseller_id',
        'customer_id',
        'batch_id',
        'shipment_id',
        'reference',
        'status', // espelho derivado — a autoridade e operational_status/payment_status
        'operational_status',
        'payment_status',
        'total_amount',
        'subtotal_amount',
        'engraving_amount',
        'shipping_amount',
        'discount_amount',
        'currency',
        'previsao',
        'arrived_at',
        'retirado_em',
        'retirado_por',
        'retirado_por_documento',
        'retirado_por_customer_id',
        'notes',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'subtotal_amount' => 'decimal:2',
            'engraving_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'previsao' => 'date',
            'arrived_at' => 'datetime',
            'retirado_em' => 'datetime',
            'meta' => 'array',
        ];
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Reseller, $this> */
    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    /** @return BelongsTo<OrderBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(OrderBatch::class, 'batch_id');
    }

    /** @return BelongsTo<Shipment, $this> */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function retiradoPorCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'retirado_por_customer_id');
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return HasMany<OrderStatusEvent, $this> */
    public function statusEvents(): HasMany
    {
        return $this->hasMany(OrderStatusEvent::class);
    }

    /** @return HasMany<OrderPromotion, $this> */
    public function promotions(): HasMany
    {
        return $this->hasMany(OrderPromotion::class);
    }

    /** @return HasMany<InvoiceItem, $this> */
    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /** @return HasMany<StockMovement, $this> */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /** @return HasMany<SupportTicket, $this> */
    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    /** @return HasMany<NotificationLog, $this> */
    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            if (filled($order->public_number)) {
                return;
            }

            $order->public_number = static::generateUniquePublicNumber();
        });
    }

    public static function generatePublicNumberCandidate(): string
    {
        return strtoupper('ORD'.str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT));
    }

    public static function generateUniquePublicNumber(): string
    {
        do {
            $candidate = static::generatePublicNumberCandidate();
        } while (static::query()->where('public_number', $candidate)->exists());

        return $candidate;
    }

    public function getRouteKeyName(): string
    {
        return 'public_number';
    }

    public function getRouteKey(): mixed
    {
        return $this->public_number ?: (string) $this->getKey();
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if ($field !== null) {
            return parent::resolveRouteBinding($value, $field);
        }

        $normalized = trim((string) $value);

        if ($normalized === '') {
            return null;
        }

        $byPublicNumber = static::query()
            ->where('public_number', $normalized)
            ->first();

        if ($byPublicNumber) {
            return $byPublicNumber;
        }

        if (! ctype_digit($normalized)) {
            return null;
        }

        return static::query()
            ->whereKey((int) $normalized)
            ->first();
    }
}
