<?php

/*
[Modulo: database/migrations]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Da ao produto a ficha tecnica de joalheria, a taxonomia e o slug da rota publica, sem tabela 1:1.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'sku']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->change();
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            $table->string('slug')->nullable()->after('name');
            $table->foreignId('collection_id')->nullable()->after('slug')
                ->constrained('collections')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->after('collection_id')
                ->constrained('categories')->nullOnDelete();
            $table->foreignId('material_id')->nullable()->after('category_id')
                ->constrained('materials')->nullOnDelete();
            $table->foreignId('finish_id')->nullable()->after('material_id')
                ->constrained('finishes')->nullOnDelete();

            $table->decimal('largura_mm', 6, 2)->nullable()->after('description');
            $table->string('formato', 40)->nullable()->after('largura_mm');
            $table->boolean('permite_gravacao')->default(false)->after('formato');
            $table->unsignedSmallInteger('gravacao_max_chars')->nullable()->after('permite_gravacao');
            $table->unsignedSmallInteger('prazo_entrega_dias')->nullable()->after('gravacao_max_chars');
            $table->boolean('is_made_to_order')->default(false)->after('prazo_entrega_dias');

            $table->unique('slug');
            $table->unique('sku');
            $table->index(['is_active', 'collection_id']);
            $table->index(['is_active', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropForeign(['finish_id']);
            $table->dropForeign(['material_id']);
            $table->dropForeign(['category_id']);
            $table->dropForeign(['collection_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex(['is_active', 'category_id']);
            $table->dropIndex(['is_active', 'collection_id']);
            $table->dropUnique(['sku']);
            $table->dropUnique(['slug']);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn([
                'slug', 'collection_id', 'category_id', 'material_id', 'finish_id',
                'largura_mm', 'formato', 'permite_gravacao',
                'gravacao_max_chars', 'prazo_entrega_dias', 'is_made_to_order',
            ]);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable(false)->change();
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'sku']);
        });
    }
};
