<?php

/*
[Modulo: database/migrations]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Identidade white-label da vitrine do lojista: pintura, roteamento proprio e toggles de exibicao.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reseller_stores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reseller_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('slogan')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('banner_path')->nullable();
            $table->string('slug')->unique();
            $table->string('domain')->nullable()->unique();

            $table->string('phone', 30)->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('endereco')->nullable();

            $table->string('color_primary', 9)->default('#800020');
            $table->string('color_secondary', 9)->default('#B8860B');
            $table->string('color_background', 9)->default('#FFFFFF');
            $table->string('color_text', 9)->default('#1A1A1A');

            $table->boolean('own_brand_only')->default(false);
            $table->boolean('hide_supplier_brand')->default(false);
            $table->boolean('show_prices')->default(true);
            $table->boolean('pickup_only')->default(true);
            $table->boolean('payment_in_store')->default(true);

            $table->boolean('is_active')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_stores');
    }
};
