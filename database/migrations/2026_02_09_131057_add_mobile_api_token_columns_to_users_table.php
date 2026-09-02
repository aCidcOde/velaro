<?php

/*
[Database/Migrations]
@Author: André Gomes ( @acidcode )
@since 2026-02-09
Adiciona campos de token bearer da API mobile na tabela de usuarios.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('api_token_hash', 64)->nullable()->index();
            $table->timestamp('api_token_expires_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['api_token_hash', 'api_token_expires_at']);
        });
    }
};
