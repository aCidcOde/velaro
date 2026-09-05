<?php

/*
[Modulo: app/Models]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Curadoria do lojista sobre o catalogo Velaro: quais pecas entram na vitrine e quais sao destaque.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerStoreProduct extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'reseller_store_id',
        'product_id',
        'position',
        'is_featured',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_featured' => 'boolean',
        ];
    }

    /** @return BelongsTo<ResellerStore, $this> */
    public function resellerStore(): BelongsTo
    {
        return $this->belongsTo(ResellerStore::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
