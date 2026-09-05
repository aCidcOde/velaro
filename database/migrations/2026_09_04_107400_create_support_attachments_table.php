<?php

/*
[Modulo: database/migrations]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Anexos do atendimento presos ao chamado e, quando vieram numa mensagem, tambem a ela.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('message_id')->nullable()->constrained('support_messages')->cascadeOnDelete();
            $table->string('original_name');
            $table->string('disk', 40)->default('local');
            $table->string('path');
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('mime', 120)->nullable();
            $table->timestamps();

            $table->index(['ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_attachments');
    }
};
