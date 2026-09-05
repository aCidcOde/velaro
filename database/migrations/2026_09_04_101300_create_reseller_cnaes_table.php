<?php

/*
[Modulo: database/migrations]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
CNAEs declarados pelo lojista com a marca de compatibilidade com o segmento vinda da triagem.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reseller_cnaes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reseller_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('description')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('compatible')->nullable();
            $table->timestamps();

            $table->unique(['reseller_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_cnaes');
    }
};
