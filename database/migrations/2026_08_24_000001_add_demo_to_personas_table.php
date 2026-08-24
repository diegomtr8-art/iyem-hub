<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca de dato ficticio.
 *
 * El rol Tester solo puede ver personas con `demo = true`. Separar los datos
 * de demostración con una columna (en vez de con una base aparte) permite
 * que las pruebas de servicio social corran contra el mismo código que
 * atiende al padrón real, sin exponer un solo registro real.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personas', function (Blueprint $table) {
            $table->boolean('demo')->default(false)->after('creado_por_modulo');
            $table->index('demo');
        });
    }

    public function down(): void
    {
        Schema::table('personas', function (Blueprint $table) {
            $table->dropIndex(['demo']);
            $table->dropColumn('demo');
        });
    }
};
