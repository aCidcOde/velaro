<?php

/*
[Modulo: app/Models]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Chamado entre Velaro e revendedor, com diagnostico de ambiente e os marcos de SLA do atendimento.
*/

namespace App\Models;

use App\Models\Concerns\BelongsToReseller;
use App\Models\Contracts\OwnedByReseller;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model implements OwnedByReseller
{
    use BelongsToReseller;
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
     * Categorias do chamado. Sao as quatro que a secao 5 da tela 2.8 lista como
     * "categorias reais" e que a coluna `category` ja guarda por extenso — o
     * valor gravado e o proprio rotulo, entao a constante e a unica forma
     * aceita e a view nunca escreve a string na mao.
     */
    public const CATEGORY_ORDERS = 'Pedidos';

    public const CATEGORY_FINANCE = 'Financeiro';

    public const CATEGORY_STOREFRONT = 'Vitrine / Loja';

    public const CATEGORY_STORE_BRANDING = 'Personalização da loja';

    /** @var list<string> */
    public const CATEGORIES = [
        self::CATEGORY_ORDERS,
        self::CATEGORY_FINANCE,
        self::CATEGORY_STOREFRONT,
        self::CATEGORY_STORE_BRANDING,
    ];

    /**
     * Origem da abertura. O chamado nascido na tela 2.8 e sempre do portal; o
     * campo existe porque a Velaro tambem abre chamado por WhatsApp e telefone.
     */
    public const CHANNEL_PORTAL = 'Portal do Lojista';

    /**
     * Prioridades da fila, na ordem em que a tela 2.8 as apresenta.
     *
     * @var list<string>
     */
    public const PRIORITIES = [
        self::PRIORITY_HIGH,
        self::PRIORITY_MEDIUM,
        self::PRIORITY_LOW,
    ];

    /**
     * Status do atendimento. `open` e o unico que o revendedor consegue criar:
     * todos os demais sao a Velaro respondendo.
     *
     * @var list<string>
     */
    public const STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_IN_PROGRESS,
        self::STATUS_AWAITING_CUSTOMER,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_ANSWERED,
        self::STATUS_RESOLVED,
    ];

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
