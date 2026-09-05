<?php

/*
[Modulo: app/Models]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
NF-e da venda B2B da Velaro ao lojista, emitida por lote; a nota do consumidor sai pela loja.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    /**
     * Nota transmitida e autorizada pela SEFAZ — o "Autorizada" da tela 2.4. A
     * migration só declara o default `pending`; este é o outro degrau que o
     * faturamento já usa, e existe aqui para nenhum ponto do código precisar
     * escrever o slug à mão.
     */
    public const STATUS_AUTHORIZED = 'authorized';

    /**
     * Nota cancelada junto a SEFAZ — a linha vermelha da tela de notas do Portal,
     * onde a acao deixa de ser "baixar" e passa a ser "ver motivo". Entra aqui
     * pelo mesmo motivo de {@see STATUS_AUTHORIZED}: e um degrau que a tela le, e
     * nenhum ponto do codigo deve escrever o slug a mao.
     */
    public const STATUS_CANCELED = 'canceled';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'batch_id',
        'number',
        'series',
        'amount',
        'status',
        'issued_at',
        'pdf_path',
        'xml_path',
        'provider',
        'issued_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'issued_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<OrderBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(OrderBatch::class, 'batch_id');
    }

    /** @return BelongsTo<User, $this> */
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /** @return HasMany<InvoiceItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
