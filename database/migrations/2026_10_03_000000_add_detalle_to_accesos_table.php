<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Qué se consultó dentro de un módulo.
 *
 * Hasta ahora un acceso era "salió hacia tal módulo". Con los tableros dentro
 * del ERP también es "miró el tablero", "descargó tal informe" o "vio la lista
 * de miembros en riesgo". El módulo de origen registra esas consultas a nombre
 * de la cuenta de servicio del ERP, así que la persona que preguntó solo queda
 * aquí.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accesos', function (Blueprint $table) {
            $table->string('detalle', 120)->nullable()->after('modulo');
        });
    }

    public function down(): void
    {
        Schema::table('accesos', function (Blueprint $table) {
            $table->dropColumn('detalle');
        });
    }
};
