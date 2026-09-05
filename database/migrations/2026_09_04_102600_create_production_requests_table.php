<?php

/*
[Modulo: database/migrations]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Pedido de producao ou reposicao em aberto, com prazo e prioridade, por tras do KPI de pendencias.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_location_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('qty_requested');
            $table->integer('qty_delivered')->default(0);
            $table->string('status', 30)->default('pendente');
            $table->string('priority', 20)->default('normal');
            $table->date('due_date')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_requests');
    }
};
