<?php

/*
[Modulo: database/migrations]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Trilha dos avisos por e-mail e WhatsApp, ligada ao pedido, ao lojista e ao cliente final.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 60);
            $table->string('channel', 20);
            $table->string('recipient');
            $table->string('recipient_type', 20);

            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reseller_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();

            $table->string('status', 30)->default('pendente');
            $table->timestamp('sent_at')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index(['order_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
