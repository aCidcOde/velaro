<?php

/*
[Modulo: database/migrations]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Chamado entre Velaro e revendedor, com o diagnostico do ambiente e os marcos de SLA.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->foreignId('reseller_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();

            $table->string('subject');
            $table->string('category', 60);
            $table->string('priority', 20)->default('media');
            $table->string('status', 30)->default('aberta');
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel', 60)->nullable();

            $table->string('environment', 30)->nullable();
            $table->string('browser', 120)->nullable();
            $table->string('os', 120)->nullable();
            $table->string('ip_address', 45)->nullable();

            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['reseller_id', 'status']);
            $table->index(['status', 'priority']);
            $table->index('assignee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
