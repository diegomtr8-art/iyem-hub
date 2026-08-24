<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lo que los módulos le cuentan al hub sobre una persona.
 *
 * Un evento es un hecho ya ocurrido en un sistema satélite ("se aprobó la
 * solicitud 44 de esta persona"). El hub no lo interpreta ni lo valida
 * contra la lógica del módulo: lo guarda para la línea de tiempo y para las
 * consultas cruzadas.
 *
 * `carga` guarda el cuerpo original en JSON. Así, cuando un módulo empiece
 * a mandar un dato que hoy no se modela, no se pierde mientras el hub
 * aprende a leerlo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventos_modulo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->constrained('personas')->cascadeOnDelete();
            $table->string('modulo');
            $table->string('tipo');
            $table->string('titulo');
            $table->text('detalle')->nullable();
            $table->string('estado')->nullable();
            $table->string('referencia_externa')->nullable();
            $table->json('carga')->nullable();
            $table->timestamp('ocurrio_at');
            $table->foreignId('sistema_id')->nullable()->constrained('sistemas_integrados')->nullOnDelete();
            $table->timestamps();

            $table->index(['persona_id', 'ocurrio_at']);
            $table->index(['modulo', 'tipo']);
            // Un módulo no puede reportar dos veces el mismo hecho: si
            // reintenta el envío, la segunda vez actualiza en lugar de
            // duplicar la línea de tiempo.
            $table->unique(['modulo', 'referencia_externa'], 'eventos_modulo_referencia_unica');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos_modulo');
    }
};
