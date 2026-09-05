<?php

/*
[Modulo: database/migrations]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Os tres uploads obrigatorios do cadastro: contrato social, documento do socio e cartao CNPJ.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reseller_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reseller_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('original_name');
            $table->string('disk', 40)->default('local');
            $table->string('path');
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('mime', 120)->nullable();
            $table->timestamps();

            $table->index(['reseller_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_documents');
    }
};
