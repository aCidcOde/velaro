<?php

/*
[Modulo: database/migrations]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Pivo que limita a campanha a produtos ou colecoes especificos do catalogo.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('collection_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['promotion_id', 'product_id']);
            $table->unique(['promotion_id', 'collection_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_products');
    }
};
