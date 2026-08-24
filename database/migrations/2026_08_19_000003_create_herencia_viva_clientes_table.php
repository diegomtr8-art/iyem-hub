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
        Schema::create('herencia_viva_clientes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('persona_id');
            $table->string('numero_cliente')->nullable()->unique();
            $table->timestamp('fecha_primer_compra')->nullable();
            $table->decimal('total_gastado', 12, 2)->default(0);
            $table->integer('numero_compras')->default(0);
            $table->boolean('es_mayorista')->default(false);
            $table->timestamps();

            $table->foreign('persona_id')->references('id')->on('personas')->onDelete('cascade');
            $table->index('persona_id');
            $table->index('numero_cliente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('herencia_viva_clientes');
    }
};
