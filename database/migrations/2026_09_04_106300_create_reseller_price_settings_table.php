<?php

/*
[Modulo: database/migrations]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Padrao de precificacao do lojista: modelo, margens, arredondamento e permissoes de preco.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reseller_price_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reseller_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('pricing_model', 20)->default('multiplier');
            $table->decimal('multiplier', 6, 2)->default(3.60);
            $table->decimal('margin_global', 5, 2)->default(50.00);
            $table->decimal('margin_min', 5, 2)->default(40.00);
            $table->decimal('margin_ideal', 5, 2)->default(50.00);
            $table->decimal('margin_max', 5, 2)->default(60.00);
            $table->string('rounding', 30)->default('up_099');
            $table->string('rule_scope', 20)->default('global');
            $table->boolean('apply_to_all')->default(true);
            $table->boolean('allow_manual_override')->default(true);
            $table->boolean('allow_promotional_prices')->default(true);
            $table->timestamp('recalculated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_price_settings');
    }
};
