<?php

/*
[Modulo: app/Models]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Chamado entre Velaro e revendedor, com diagnostico de ambiente e os marcos de SLA do atendimento.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasFactory;

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_LOW = 'low';

    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_AWAITING_CUSTOMER = 'awaiting_customer';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_ANSWERED = 'answered';

    public const STATUS_RESOLVED = 'resolved';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'reseller_id',
        'order_id',
        'customer_id',
        'subject',
        'category',
        'priority',
        'status',
        'assignee_id',
        'channel',
        'environment',
        'browser',
        'os',
        'ip_address',
        'first_response_at',
        'resolved_at',
        'closed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'first_response_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Reseller, $this> */
    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /** @return HasMany<SupportMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class, 'ticket_id');
    }

    /** @return HasMany<SupportAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(SupportAttachment::class, 'ticket_id');
    }

    /** @return HasMany<SupportStatusEvent, $this> */
    public function statusEvents(): HasMany
    {
        return $this->hasMany(SupportStatusEvent::class, 'ticket_id');
    }

    /** @return BelongsToMany<SupportTag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(SupportTag::class, 'support_ticket_tag');
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }
}
