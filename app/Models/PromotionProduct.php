<?php

/*
[Modulo: app/Models]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Pivo que amarra a campanha aos produtos e colecoes que ela alcanca.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionProduct extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'promotion_id',
        'product_id',
        'collection_id',
    ];

    /** @return BelongsTo<Promotion, $this> */
    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<ProductCollection, $this> */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(ProductCollection::class, 'collection_id');
    }
}
