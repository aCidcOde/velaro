<?php

/*
[Modulo: app/Models]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Local fisico de guarda do estoque Velaro, alvo do filtro de local e do saldo por cofre.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockLocation extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'is_default',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<StockItem, $this> */
    public function stockItems(): HasMany
    {
        return $this->hasMany(StockItem::class);
    }

    /** @return HasMany<ProductionRequest, $this> */
    public function productionRequests(): HasMany
    {
        return $this->hasMany(ProductionRequest::class);
    }
}
