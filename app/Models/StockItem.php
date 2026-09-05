<?php

/*
[Modulo: app/Models]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Saldo de um SKU em um cofre da Velaro; o portal do lojista le o disponivel e nunca escreve.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockItem extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_variant_id',
        'stock_location_id',
        'atual',
        'reservado',
        'disponivel',
        'minimo',
        'reposicao',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'atual' => 'integer',
            'reservado' => 'integer',
            'disponivel' => 'integer',
            'minimo' => 'integer',
            'reposicao' => 'integer',
        ];
    }

    /** @return BelongsTo<ProductVariant, $this> */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /** @return BelongsTo<StockLocation, $this> */
    public function stockLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class);
    }

    /** @return HasMany<StockMovement, $this> */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
