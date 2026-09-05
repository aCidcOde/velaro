<?php

/*
[Modulo: database/migrations]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
As categorias que o lojista escolhe exibir na vitrine, na ordem dele, sem herdar tudo.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reseller_store_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reseller_store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['reseller_store_id', 'category_id'], 'reseller_store_category_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_store_categories');
    }
};
