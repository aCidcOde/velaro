<?php

namespace App\Models;

use App\Models\Concerns\BelongsToReseller;
use App\Models\Contracts\OwnedByReseller;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model implements OwnedByReseller
{
    use BelongsToReseller;

    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    public const PERSON_TYPE_INDIVIDUAL = 'individual';

    public const PERSON_TYPE_COMPANY = 'company';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'reseller_id',
        'name',
        'person_type',
        'company_name',
        'email',
        'phone',
        'document',
        'postal_code',
        'address',
        'city',
        'state',
        'birth_date',
        'wedding_date',
        'relationship_date',
        'contact_source',
        'notes',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'wedding_date' => 'date',
            'relationship_date' => 'date',
            'meta' => 'array',
        ];
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

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** @return HasMany<Order, $this> */
    public function pickedUpOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'picked_up_by_customer_id');
    }

    /** @return HasMany<CustomerConsent, $this> */
    public function consents(): HasMany
    {
        return $this->hasMany(CustomerConsent::class);
    }

    /** @return HasMany<Favorite, $this> */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
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
}
