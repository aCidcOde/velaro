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

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_number',
        'user_id',
        'customer_id',
        'reference',
        'status',
        'total_amount',
        'currency',
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

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
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
