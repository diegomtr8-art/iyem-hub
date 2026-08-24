<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vigencia de la cuenta. Nace para las cuentas de prueba y de servicio
 * social, que deben caducar solas, pero sirve para cualquier usuario
 * temporal. `null` significa "sin fecha de caducidad".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('expira_at')->nullable()->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('expira_at');
        });
    }
};
