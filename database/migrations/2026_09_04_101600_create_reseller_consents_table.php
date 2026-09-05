<?php

/*
[Modulo: database/migrations]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Aceites de LGPD do lojista no cadastro, com versao do texto, IP e agente para prova posterior.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reseller_consents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reseller_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->boolean('granted')->default(true);
            $table->string('document_version', 40)->nullable();
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['reseller_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_consents');
    }
};
