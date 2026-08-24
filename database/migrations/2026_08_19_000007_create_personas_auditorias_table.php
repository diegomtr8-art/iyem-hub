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
        Schema::create('personas_auditorias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('persona_id');
            $table->string('campo_modificado')->nullable();
            $table->longText('valor_anterior')->nullable();
            $table->longText('valor_nuevo')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('modulo_origen')->nullable();
            $table->timestamp('fecha_cambio')->useCurrent();

            $table->foreign('persona_id')->references('id')->on('personas')->onDelete('cascade');
            $table->index('persona_id');
            $table->index('fecha_cambio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personas_auditorias');
    }
};
