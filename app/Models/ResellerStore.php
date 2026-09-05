<?php

/*
[Modulo: app/Models]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Vitrine white-label do lojista: unica fonte da pintura, do dominio proprio e dos toggles de venda.
*/

namespace App\Models;

use App\Models\Concerns\BelongsToReseller;
use App\Models\Contracts\OwnedByReseller;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * `endereco` nasceu em portugues e virou `address` na migration de traducao do
 * schema; o leitor de migrations do Larastan nao acompanha o rename, entao a
 * coluna precisa ser declarada aqui para a analise estatica enxerga-la. E a
 * mesma razao do bloco equivalente em {@see Reseller}.
 *
 * @property string|null $address
 */
class ResellerStore extends Model implements OwnedByReseller
{
    use BelongsToReseller;
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'reseller_id',
        'name',
        'slogan',
        'logo_path',
        'banner_path',
        'slug',
        'domain',
        'phone',
        'whatsapp',
        'email',
        'address',
        'color_primary',
        'color_secondary',
        'color_background',
        'color_text',
        'own_brand_only',
        'hide_supplier_brand',
        'show_prices',
        'pickup_only',
        'payment_in_store',
        'is_active',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'own_brand_only' => 'boolean',
            'hide_supplier_brand' => 'boolean',
            'show_prices' => 'boolean',
            'pickup_only' => 'boolean',
            'payment_in_store' => 'boolean',
            'is_active' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Reseller, $this> */
    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    /** @return HasMany<ResellerStoreCategory, $this> */
    public function storeCategories(): HasMany
    {
        return $this->hasMany(ResellerStoreCategory::class);
    }

    /** @return HasMany<ResellerStoreProduct, $this> */
    public function storeProducts(): HasMany
    {
        return $this->hasMany(ResellerStoreProduct::class);
    }

    /** @return BelongsToMany<Category, $this> */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'reseller_store_categories')
            ->withPivot('position')
            ->withTimestamps();
    }

    /** @return BelongsToMany<Product, $this> */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'reseller_store_products')
            ->withPivot(['position', 'is_featured'])
            ->withTimestamps();
    }

    /** @return HasMany<Favorite, $this> */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
