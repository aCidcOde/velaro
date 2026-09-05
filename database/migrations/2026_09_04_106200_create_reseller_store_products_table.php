<?php

/*
[Modulo: database/migrations]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Curadoria e destaque de produtos na vitrine: selecao do lojista sobre o catalogo da Velaro.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reseller_store_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reseller_store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_featured')->default(true);
            $table->timestamps();

            $table->unique(['reseller_store_id', 'product_id'], 'reseller_store_product_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_store_products');
    }
};
