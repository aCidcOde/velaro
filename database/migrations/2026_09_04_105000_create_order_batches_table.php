<?php

/*
[Modulo: database/migrations]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Lote semanal de faturamento ao lojista: unidade de pagamento, nota fiscal e liberacao de remessa.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_batches', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->foreignId('reseller_id')->constrained()->restrictOnDelete();
            $table->date('cut_date');
            $table->date('due_date');
            $table->string('status', 30)->default('aberto');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('retirado_em')->nullable();
            $table->string('retirado_por')->nullable();
            $table->string('retirado_por_documento', 30)->nullable();
            $table->timestamps();

            $table->index(['reseller_id', 'status']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_batches');
    }
};
