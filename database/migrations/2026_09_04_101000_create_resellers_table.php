<?php

/*
[Modulo: database/migrations]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Lojista com CNPJ, do pre-cadastro a aprovacao: entidade central e eixo de escopo de todo o B2B.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resellers', function (Blueprint $table): void {
            $table->id();
            $table->string('protocolo', 40)->unique();
            $table->string('code', 40)->nullable()->unique();

            $table->string('razao_social');
            $table->string('nome_fantasia')->nullable();
            $table->string('cnpj', 20)->unique();
            $table->string('inscricao_estadual', 30)->nullable();

            $table->string('responsavel_nome');
            $table->string('responsavel_cpf', 20)->nullable();
            $table->string('email');
            $table->string('telefone', 30)->nullable();
            $table->string('whatsapp', 30)->nullable();

            $table->string('cep', 12)->nullable();
            $table->string('logradouro')->nullable();
            $table->string('numero', 30)->nullable();
            $table->string('complemento')->nullable();
            $table->string('bairro')->nullable();
            $table->string('cidade')->nullable();
            $table->string('uf', 2)->nullable();

            $table->string('origem_contato', 60)->nullable();
            $table->string('registration_type', 20)->default('automatico');
            $table->text('observacoes')->nullable();
            $table->text('observacoes_internas')->nullable();

            $table->string('status', 30)->default('pre_cadastro');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('uf');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resellers');
    }
};
