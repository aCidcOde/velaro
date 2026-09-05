<?php

/*
[Modulo: database/migrations]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Vocabulario de etiquetas do atendimento, para o filtro por tag do chamado fazer sentido.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 60);
            $table->string('slug', 60)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tags');
    }
};
