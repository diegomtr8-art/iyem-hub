<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bitácora de importaciones al padrón.
 *
 * Cada lote deja constancia de quién lo subió, con qué archivo, cuántas
 * filas entraron y cuántas se rechazaron. El archivo de rechazos se
 * conserva para que quien capturó pueda corregirlo y volver a intentarlo,
 * en lugar de adivinar qué salió mal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('padron_importaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('archivo_original');
            $table->string('ruta_archivo')->nullable();
            $table->string('ruta_rechazos')->nullable();

            $table->unsignedInteger('total_filas')->default(0);
            $table->unsignedInteger('filas_creadas')->default(0);
            $table->unsignedInteger('filas_actualizadas')->default(0);
            $table->unsignedInteger('filas_rechazadas')->default(0);

            // Qué columna del archivo alimentó qué campo del padrón.
            $table->json('mapeo')->nullable();
            $table->enum('estado', ['previsualizada', 'confirmada', 'fallida'])->default('previsualizada');
            $table->text('mensaje')->nullable();

            $table->timestamps();

            $table->index('usuario_id');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('padron_importaciones');
    }
};
