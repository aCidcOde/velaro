<?php

/*
[Modulo: database/migrations]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Torna o pedido B2B: dono, lote, os dois status independentes, as linhas de valor e a retirada.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->change();
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            $table->foreignId('reseller_id')->nullable()->after('user_id')
                ->constrained('resellers')->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->after('customer_id')
                ->constrained('order_batches')->nullOnDelete();

            $table->string('operational_status', 30)->default('registrado')->after('status');
            $table->string('payment_status', 30)->default('pendente')->after('operational_status');

            $table->decimal('subtotal_amount', 12, 2)->default(0)->after('total_amount');
            $table->decimal('engraving_amount', 12, 2)->default(0)->after('subtotal_amount');
            $table->decimal('shipping_amount', 12, 2)->default(0)->after('engraving_amount');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('shipping_amount');

            $table->date('previsao')->nullable()->after('currency');
            $table->timestamp('arrived_at')->nullable()->after('previsao');
            $table->timestamp('retirado_em')->nullable()->after('arrived_at');
            $table->string('retirado_por')->nullable()->after('retirado_em');
            $table->string('retirado_por_documento', 30)->nullable()->after('retirado_por');
            $table->foreignId('retirado_por_customer_id')->nullable()->after('retirado_por_documento')
                ->constrained('customers')->nullOnDelete();

            $table->index(['reseller_id', 'operational_status']);
            $table->index(['reseller_id', 'payment_status']);
        });
    }

    public function down(): void
    {
        // O up() tornou `user_id` nulavel; restaurar NOT NULL quebraria na metade se algum
        // registro ficou orfao. Falha antes de qualquer DDL, com o schema intacto.
        $orfaos = DB::table('orders')->whereNull('user_id')->count();

        if ($orfaos > 0) {
            throw new RuntimeException(
                'Rollback bloqueado: '.$orfaos.' registro(s) em orders sem dono. '
                .'Reatribua o `user_id` antes de reverter esta migracao.'
            );
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['retirado_por_customer_id']);
            $table->dropForeign(['batch_id']);
            $table->dropForeign(['reseller_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex(['reseller_id', 'payment_status']);
            $table->dropIndex(['reseller_id', 'operational_status']);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'reseller_id', 'batch_id', 'retirado_por_customer_id',
                'operational_status', 'payment_status', 'subtotal_amount', 'engraving_amount',
                'shipping_amount', 'discount_amount', 'previsao', 'arrived_at',
                'retirado_em', 'retirado_por', 'retirado_por_documento',
            ]);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable(false)->change();
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
