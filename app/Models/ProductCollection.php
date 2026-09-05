<?php

/*
[Modulo: app/Models]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Colecao comercial do catalogo Velaro, usada como aba e filtro no site, no portal e no master.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCollection extends Model
{
    use HasFactory;

    protected $table = 'collections';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'cover_path',
        'position',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'collection_id');
    }

    /** @return HasMany<PromotionProduct, $this> */
    public function promotionProducts(): HasMany
    {
        return $this->hasMany(PromotionProduct::class, 'collection_id');
    }

    /** @return HasMany<ResellerPriceRule, $this> */
    public function resellerPriceRules(): HasMany
    {
        return $this->hasMany(ResellerPriceRule::class, 'collection_id');
    }

    /** @return BelongsToMany<Promotion, $this> */
    public function promotions(): BelongsToMany
    {
        return $this->belongsToMany(Promotion::class, 'promotion_products', 'collection_id', 'promotion_id')
            ->withTimestamps();
    }
}
