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
        Schema::create('crea_solicitudes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('persona_id');
            $table->decimal('monto_solicitado', 12, 2)->nullable();
            $table->string('tipo_credito')->nullable();
            $table->enum('estado_solicitud', ['borrador', 'enviada', 'aprobada', 'rechazada', 'desembolsada'])->default('borrador');
            $table->timestamp('fecha_solicitud')->nullable();
            $table->timestamps();

            $table->foreign('persona_id')->references('id')->on('personas')->onDelete('cascade');
            $table->index('persona_id');
            $table->index('estado_solicitud');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crea_solicitudes');
    }
};
