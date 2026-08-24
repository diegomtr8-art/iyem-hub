<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sistemas satélite autorizados a consumir la API del padrón.
 *
 * Cada sistema —CREA, Impúlsate, Jurídico…— es un cliente con su propio
 * token de Sanctum y sus propias habilidades. No se usan tokens de usuario:
 * el que consulta el padrón es el sistema, no la persona que lo opera, y
 * atarlo a una cuenta haría que revocar a un empleado tumbara la
 * integración entera.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sistemas_integrados', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->string('url_base')->nullable();
            $table->string('contacto')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamp('ultimo_ping')->nullable();
            $table->timestamps();

            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sistemas_integrados');
    }
};
