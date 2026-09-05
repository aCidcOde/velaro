<?php

/*
[Modulo: database/migrations]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Campanha da Velaro para o lojista, distinta da promocao que o revendedor faz na propria vitrine.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 40);
            $table->string('status', 20)->default('rascunho');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('show_badge')->default(false);
            $table->decimal('budget', 12, 2)->nullable();
            $table->timestamps();

            $table->index(['status', 'starts_at', 'ends_at']);
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
