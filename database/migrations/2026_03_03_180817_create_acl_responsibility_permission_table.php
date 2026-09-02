<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('acl_responsibility_permission', function (Blueprint $table) {
            $table->foreignId('responsibility_id')
                ->constrained('acl_responsibilities')
                ->cascadeOnDelete();
            $table->foreignId('permission_id')
                ->constrained('acl_permissions')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['responsibility_id', 'permission_id'], 'acl_responsibility_permission_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acl_responsibility_permission');
    }
};
