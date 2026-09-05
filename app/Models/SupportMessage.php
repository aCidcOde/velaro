<?php

/*
[Modulo: app/Models]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Mensagem da thread do chamado, com a marca de nota interna que nunca chega ao revendedor.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportMessage extends Model
{
    use HasFactory;

    public const AUTHOR_ROLE_REVENDEDOR = 'revendedor';

    public const AUTHOR_ROLE_VELARO = 'velaro';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ticket_id',
        'author_id',
        'author_role',
        'body',
        'is_internal_note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_internal_note' => 'boolean',
        ];
    }

    /** @return BelongsTo<SupportTicket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return HasMany<SupportAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(SupportAttachment::class, 'message_id');
    }

    /**
     * Observacao interna nunca vaza para o revendedor.
     *
     * @param  Builder<static>  $query
     */
    public function scopeVisibleToReseller(Builder $query): void
    {
        $query->where('is_internal_note', false);
    }
}
