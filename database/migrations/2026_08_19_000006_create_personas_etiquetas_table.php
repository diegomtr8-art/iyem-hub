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
        Schema::create('personas_etiquetas', function (Blueprint $table) {
            $table->unsignedBigInteger('persona_id');
            $table->string('etiqueta');
            $table->primary(['persona_id', 'etiqueta']);
            $table->foreign('persona_id')->references('id')->on('personas')->onDelete('cascade');
            $table->index('etiqueta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personas_etiquetas');
    }
};
