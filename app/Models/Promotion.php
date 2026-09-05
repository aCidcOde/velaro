<?php

/*
[Modulo: app/Models]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Campanha promocional B2B da Velaro para o lojista, com vigencia, prioridade e orcamento.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    use HasFactory;

    public const TYPE_DESCONTO_PROGRESSIVO = 'desconto_progressivo';

    public const TYPE_PRECO_ESPECIAL = 'preco_especial';

    public const TYPE_FRETE_GRATIS = 'frete_gratis';

    public const TYPE_DESCONTO_FIXO = 'desconto_fixo';

    public const TYPE_LANCAMENTO = 'lancamento';

    public const STATUS_RASCUNHO = 'rascunho';

    public const STATUS_AGENDADA = 'agendada';

    public const STATUS_ATIVA = 'ativa';

    public const STATUS_PAUSADA = 'pausada';

    public const STATUS_ENCERRADA = 'encerrada';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'status',
        'starts_at',
        'ends_at',
        'priority',
        'show_badge',
        'budget',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'priority' => 'integer',
            'show_badge' => 'boolean',
            'budget' => 'decimal:2',
        ];
    }

    /** @return HasMany<PromotionRule, $this> */
    public function rules(): HasMany
    {
        return $this->hasMany(PromotionRule::class);
    }

    /** @return HasMany<PromotionProduct, $this> */
    public function promotionProducts(): HasMany
    {
        return $this->hasMany(PromotionProduct::class);
    }

    /** @return HasMany<PromotionAudience, $this> */
    public function audiences(): HasMany
    {
        return $this->hasMany(PromotionAudience::class);
    }

    /** @return HasMany<OrderPromotion, $this> */
    public function orderPromotions(): HasMany
    {
        return $this->hasMany(OrderPromotion::class);
    }

    /** @return BelongsToMany<Product, $this> */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'promotion_products')
            ->withTimestamps();
    }

    /** @return BelongsToMany<ProductCollection, $this> */
    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(ProductCollection::class, 'promotion_products', 'promotion_id', 'collection_id')
            ->withTimestamps();
    }
}
