<?php

/*
[Modulo: database/migrations]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
NF-e da venda ao lojista, emitida por lote; a nota do consumidor final e do proprio lojista.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('batch_id')->constrained('order_batches')->restrictOnDelete();
            $table->string('number', 40);
            $table->string('series', 10)->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status', 30)->default('pendente');
            $table->timestamp('issued_at')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('xml_path')->nullable();
            $table->string('provider', 60)->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['series', 'number']);
            $table->index(['batch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
