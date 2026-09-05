<?php

/*
[Modulo: database/migrations]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Poe o consumidor final na carteira de um lojista, com endereco e datas que alimentam campanha.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->change();
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            $table->foreignId('reseller_id')->nullable()->after('user_id')
                ->constrained('resellers')->cascadeOnDelete();
            $table->string('person_type', 2)->default('pf')->after('name');
            $table->string('cep', 12)->nullable()->after('document');
            $table->string('endereco')->nullable()->after('cep');
            $table->string('cidade')->nullable()->after('endereco');
            $table->string('uf', 2)->nullable()->after('cidade');
            $table->date('data_nascimento')->nullable()->after('uf');
            $table->date('data_casamento')->nullable()->after('data_nascimento');
            $table->date('data_namoro')->nullable()->after('data_casamento');
            $table->string('origem_contato', 60)->nullable()->after('data_namoro');

            $table->index(['reseller_id', 'name']);
            $table->index(['reseller_id', 'document']);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropForeign(['reseller_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropIndex(['reseller_id', 'document']);
            $table->dropIndex(['reseller_id', 'name']);
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn([
                'reseller_id', 'person_type', 'cep', 'endereco', 'cidade', 'uf',
                'data_nascimento', 'data_casamento', 'data_namoro', 'origem_contato',
            ]);
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable(false)->change();
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
