<?php

/*
[Modulo: database/migrations]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Remessa fisica do lote ate a loja: transportadora, rastreio, liberacao e datas de entrega.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->foreignId('order_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reseller_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('aguardando_liberacao');
            $table->string('carrier')->nullable();
            $table->string('tracking_code', 80)->nullable();
            $table->string('tracking_url')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->date('estimated_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['reseller_id', 'status']);
            $table->index('tracking_code');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('shipment_id')->nullable()->after('batch_id')
                ->constrained('shipments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('shipment_id');
        });

        Schema::dropIfExists('shipments');
    }
};
