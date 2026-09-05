<?php

/*
[Modulo: database/migrations]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Saldo fisico por aro em cada local: o portal do lojista le o disponivel e nunca escreve.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_location_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('atual')->default(0);
            $table->integer('reservado')->default(0);
            $table->integer('disponivel')->default(0);
            $table->integer('minimo')->default(0);
            $table->integer('reposicao')->default(0);
            $table->timestamps();

            $table->unique(['product_variant_id', 'stock_location_id'], 'stock_items_variant_location_unique');
            $table->index('disponivel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_items');
    }
};
