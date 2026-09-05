<?php

/*
[Modulo: app/Models]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Excecao de preco ao consumidor sobre o custo Velaro, resolvida por escopo e prioridade.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerPriceRule extends Model
{
    use HasFactory;

    public const SCOPE_GLOBAL = 'global';

    public const SCOPE_COLLECTION = 'collection';

    public const SCOPE_PRODUCT = 'product';

    public const MODE_MULTIPLIER = 'multiplier';

    public const MODE_PERCENT = 'percent';

    public const MODE_MANUAL = 'manual';

    public const MODE_PROMO = 'promo';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'reseller_id',
        'scope',
        'collection_id',
        'product_id',
        'mode',
        'value',
        'rounding',
        'priority',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'decimal:4',
            'priority' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Reseller, $this> */
    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    /** @return BelongsTo<ProductCollection, $this> */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(ProductCollection::class, 'collection_id');
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
