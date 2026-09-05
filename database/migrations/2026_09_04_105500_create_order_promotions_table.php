<?php

/*
[Modulo: database/migrations]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Campanha aplicada a um pedido com o desconto congelado em reais, para auditar depois.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_promotions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('promotion_id')->constrained()->restrictOnDelete();
            $table->string('type', 40);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'promotion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_promotions');
    }
};
