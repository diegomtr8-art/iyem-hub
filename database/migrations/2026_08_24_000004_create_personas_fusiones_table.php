<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bitácora de fusiones de personas duplicadas, con lo necesario para
 * deshacerlas.
 *
 * Fusionar dos expedientes es la operación más destructiva del padrón: se
 * juntan trámites de módulos distintos bajo una sola identidad y, si la
 * decisión estuvo mal, se mezcló el historial de dos personas reales.
 *
 * Por eso se guarda el estado completo de ambas fichas antes de tocarlas y
 * el detalle de qué vínculos se movieron. Durante 30 días la fusión se
 * puede revertir dejando todo como estaba.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personas_fusiones', function (Blueprint $table) {
            $table->id();

            // Quién sobrevive y quién fue absorbida.
            $table->foreignId('principal_id')->constrained('personas')->cascadeOnDelete();
            $table->foreignId('duplicada_id')->constrained('personas')->cascadeOnDelete();

            // Fotografía de ambas fichas justo antes de la fusión.
            $table->json('snapshot_principal');
            $table->json('snapshot_duplicada');

            // Qué se movió y de dónde: {"crea_solicitudes": 2, ...}
            $table->json('vinculos_movidos')->nullable();
            $table->json('etiquetas_movidas')->nullable();
            // Campos que la principal tenía vacíos y se llenaron con los de
            // la duplicada. Al revertir hay que volverlos a vaciar.
            $table->json('campos_completados')->nullable();

            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('criterio')->nullable();
            $table->text('motivo')->nullable();

            $table->timestamp('revertible_hasta');
            $table->timestamp('revertida_at')->nullable();
            $table->foreignId('revertida_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('principal_id');
            $table->index('duplicada_id');
            $table->index('revertida_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personas_fusiones');
    }
};
