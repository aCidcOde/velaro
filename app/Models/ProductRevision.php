<?php

/*
[Modulo: app/Models]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Historico de edicao do produto com before/after, exigido porque o preco ali e o custo B2B.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRevision extends Model
{
    use HasFactory;

    public const ACTION_UPDATED = 'updated';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'actor_id',
        'action',
        'before',
        'after',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
