<?php

/*
[Modulo: database/migrations]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Excecoes de formacao do preco ao consumidor sobre o custo Velaro, por prioridade e escopo.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reseller_price_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reseller_id')->constrained()->cascadeOnDelete();
            $table->string('scope', 20)->default('global');
            $table->foreignId('collection_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('mode', 20)->default('multiplier');
            $table->decimal('value', 12, 4)->default(0);
            $table->string('rounding', 30)->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['reseller_id', 'scope', 'priority'], 'reseller_price_rules_scope_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_price_rules');
    }
};
