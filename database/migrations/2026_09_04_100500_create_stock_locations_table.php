<?php

/*
[Modulo: database/migrations]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Locais fisicos de guarda do cofre: sem eles o saldo seria uma linha unica por variante de produto.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_locations', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_locations');
    }
};
