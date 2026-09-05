<?php

/*
[Modulo: app/Models]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Padrao de precificacao do lojista: modelo, multiplicador, faixas de margem e arredondamento.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerPriceSetting extends Model
{
    use HasFactory;

    public const PRICING_MODEL_MULTIPLIER = 'multiplier';

    public const PRICING_MODEL_PERCENT = 'percent';

    public const RULE_SCOPE_GLOBAL = 'global';

    public const RULE_SCOPE_COLLECTION = 'collection';

    public const RULE_SCOPE_PRODUCT = 'product';

    public const ROUNDING_NONE = 'none';

    public const ROUNDING_UP_099 = 'up_099';

    public const ROUNDING_UP_090 = 'up_090';

    public const ROUNDING_UP_INTEGER = 'up_integer';

    public const ROUNDING_NEAREST_10 = 'nearest_10';

    /**
     * Os dois modelos do bloco "Modelo de precificacao" da tela 2.6.
     *
     * @var list<string>
     */
    public const PRICING_MODELS = [
        self::PRICING_MODEL_MULTIPLIER,
        self::PRICING_MODEL_PERCENT,
    ];

    /**
     * Alcance da regra que a tela 2.7 oferece no seletor "Regra de preco".
     *
     * @var list<string>
     */
    public const RULE_SCOPES = [
        self::RULE_SCOPE_GLOBAL,
        self::RULE_SCOPE_COLLECTION,
        self::RULE_SCOPE_PRODUCT,
    ];

    /**
     * Politicas de arredondamento do preco exibido na vitrine. O prototipo
     * mostra so "Para cima (0,99)" selecionado, mas o campo e um `select` e
     * precisa de lista — aqui, e nao como string solta na view.
     *
     * @var list<string>
     */
    public const ROUNDINGS = [
        self::ROUNDING_NONE,
        self::ROUNDING_UP_099,
        self::ROUNDING_UP_090,
        self::ROUNDING_UP_INTEGER,
        self::ROUNDING_NEAREST_10,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'reseller_id',
        'pricing_model',
        'multiplier',
        'margin_global',
        'margin_min',
        'margin_ideal',
        'margin_max',
        'rounding',
        'rule_scope',
        'apply_to_all',
        'allow_manual_override',
        'allow_promotional_prices',
        'recalculated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'multiplier' => 'decimal:2',
            'margin_global' => 'decimal:2',
            'margin_min' => 'decimal:2',
            'margin_ideal' => 'decimal:2',
            'margin_max' => 'decimal:2',
            'apply_to_all' => 'boolean',
            'allow_manual_override' => 'boolean',
            'allow_promotional_prices' => 'boolean',
            'recalculated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Reseller, $this> */
    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }
}
