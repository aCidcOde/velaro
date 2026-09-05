<?php

/*
[Modulo: app/Models]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Lojista com CNPJ habilitado a revender: eixo de escopo de todo o resto da plataforma B2B.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reseller extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_INACTIVE = 'inactive';

    public const REGISTRATION_TYPE_AUTOMATIC = 'automatic';

    public const REGISTRATION_TYPE_MANUAL = 'manual';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'protocol',
        'code',
        'legal_name',
        'trade_name',
        'cnpj',
        'state_registration',
        'contact_name',
        'contact_cpf',
        'email',
        'phone',
        'whatsapp',
        'postal_code',
        'street',
        'street_number',
        'address_complement',
        'district',
        'city',
        'state',
        'contact_source',
        'registration_type',
        'notes',
        'internal_notes',
        'status',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejection_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @return HasMany<ResellerDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(ResellerDocument::class);
    }

    /** @return HasMany<ResellerCnae, $this> */
    public function cnaes(): HasMany
    {
        return $this->hasMany(ResellerCnae::class);
    }

    /** @return HasMany<ResellerVerification, $this> */
    public function verifications(): HasMany
    {
        return $this->hasMany(ResellerVerification::class);
    }

    /** @return HasMany<ResellerStatusEvent, $this> */
    public function statusEvents(): HasMany
    {
        return $this->hasMany(ResellerStatusEvent::class);
    }

    /** @return HasMany<ResellerConsent, $this> */
    public function consents(): HasMany
    {
        return $this->hasMany(ResellerConsent::class);
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<Customer, $this> */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** @return HasMany<OrderBatch, $this> */
    public function batches(): HasMany
    {
        return $this->hasMany(OrderBatch::class);
    }

    /** @return HasMany<Shipment, $this> */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    /** @return HasMany<SupportTicket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    /** @return HasMany<ResellerPriceRule, $this> */
    public function priceRules(): HasMany
    {
        return $this->hasMany(ResellerPriceRule::class);
    }

    /** @return HasMany<NotificationLog, $this> */
    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    /** @return HasOne<ResellerStore, $this> */
    public function store(): HasOne
    {
        return $this->hasOne(ResellerStore::class);
    }

    /** @return HasOne<ResellerPriceSetting, $this> */
    public function priceSetting(): HasOne
    {
        return $this->hasOne(ResellerPriceSetting::class);
    }
}
