<?php

/*
[Modulo: database/migrations]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Cada rodada da checagem automatica de CNPJ e CNAE: triagem com score, nunca a decisao final.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reseller_verifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reseller_id')->constrained()->cascadeOnDelete();
            $table->string('status', 30)->default('pendente');
            $table->boolean('cnpj_valido')->nullable();
            $table->boolean('empresa_ativa')->nullable();
            $table->boolean('cnaes_compativeis')->nullable();
            $table->boolean('documentacao_enviada')->nullable();
            $table->unsignedTinyInteger('score')->nullable();
            $table->json('result')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->index(['reseller_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_verifications');
    }
};
