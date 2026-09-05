<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'collection_id',
        'category_id',
        'material_id',
        'finish_id',
        'sku',
        'description',
        'largura_mm',
        'formato',
        'permite_gravacao',
        'gravacao_max_chars',
        'prazo_entrega_dias',
        'is_made_to_order',
        'price', // custo B2B (custo Velaro) cobrado do lojista — nao e preco de vitrine
        'is_active',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'largura_mm' => 'decimal:2',
            'permite_gravacao' => 'boolean',
            'gravacao_max_chars' => 'integer',
            'prazo_entrega_dias' => 'integer',
            'is_made_to_order' => 'boolean',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<ProductCollection, $this> */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(ProductCollection::class, 'collection_id');
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<Material, $this> */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /** @return BelongsTo<Finish, $this> */
    public function finish(): BelongsTo
    {
        return $this->belongsTo(Finish::class);
    }

    /** @return HasMany<ProductVariant, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /** @return HasMany<ProductImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    /** @return HasMany<ProductRevision, $this> */
    public function revisions(): HasMany
    {
        return $this->hasMany(ProductRevision::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return HasMany<Favorite, $this> */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /** @return HasMany<PromotionProduct, $this> */
    public function promotionProducts(): HasMany
    {
        return $this->hasMany(PromotionProduct::class);
    }

    /** @return HasMany<ResellerPriceRule, $this> */
    public function resellerPriceRules(): HasMany
    {
        return $this->hasMany(ResellerPriceRule::class);
    }

    /** @return HasMany<ResellerStoreProduct, $this> */
    public function resellerStoreProducts(): HasMany
    {
        return $this->hasMany(ResellerStoreProduct::class);
    }

    /** @return BelongsToMany<Promotion, $this> */
    public function promotions(): BelongsToMany
    {
        return $this->belongsToMany(Promotion::class, 'promotion_products')
            ->withTimestamps();
    }

    /** @return BelongsToMany<ResellerStore, $this> */
    public function resellerStores(): BelongsToMany
    {
        return $this->belongsToMany(ResellerStore::class, 'reseller_store_products')
            ->withPivot(['position', 'is_featured'])
            ->withTimestamps();
    }
}
