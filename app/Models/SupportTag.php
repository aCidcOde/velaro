<?php

/*
[Modulo: app/Models]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Vocabulario de etiquetas do suporte, para o filtro por tag da fila valer alguma coisa.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SupportTag extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
    ];

    /** @return BelongsToMany<SupportTicket, $this> */
    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(SupportTicket::class, 'support_ticket_tag');
    }
}
