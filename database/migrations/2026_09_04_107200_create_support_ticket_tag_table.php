<?php

/*
[Modulo: database/migrations]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Pivo entre chamado e etiqueta; sem timestamps, porque ali e vinculo e nao evento.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_ticket_tag', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('support_tag_id')->constrained()->cascadeOnDelete();

            $table->unique(['support_ticket_id', 'support_tag_id'], 'support_ticket_tag_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_tag');
    }
};
