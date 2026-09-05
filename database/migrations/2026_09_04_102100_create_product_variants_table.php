<?php

/*
[Modulo: database/migrations]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
SKU por aro: a unidade real de estoque, disponibilidade e producao a que o item do pedido aponta.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 100)->unique();
            $table->string('aro', 20);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['product_id', 'aro']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
