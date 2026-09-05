<?php

/*
[Modulo: database/migrations]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Aceite LGPD do lead do site: reseller_consents exige revendedor e o lead ainda nao e um.
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_leads', function (Blueprint $table): void {
            // A tela 1.8 exige o aceite para enviar e a prova fica com o lead:
            // `reseller_consents` nao serve porque `reseller_id` e NOT NULL.
            $table->timestamp('consent_granted_at')->nullable()->after('origin');
            $table->string('consent_document_version', 40)->nullable()->after('consent_granted_at');
            $table->string('consent_ip_address', 45)->nullable()->after('consent_document_version');
            $table->text('consent_user_agent')->nullable()->after('consent_ip_address');
        });
    }

    public function down(): void
    {
        Schema::table('contact_leads', function (Blueprint $table): void {
            $table->dropColumn([
                'consent_granted_at',
                'consent_document_version',
                'consent_ip_address',
                'consent_user_agent',
            ]);
        });
    }
};
