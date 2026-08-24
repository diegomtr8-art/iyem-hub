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
        Schema::create('personas', function (Blueprint $table) {
            // Identidad básica
            $table->id();
            $table->string('nombre_completo');
            $table->string('email')->nullable()->unique();
            $table->string('telefono')->nullable();
            $table->string('telefono_secundario')->nullable();

            // Identificación oficial
            $table->string('curp')->nullable()->unique();
            $table->string('rfc')->nullable();
            $table->string('ine_clave')->nullable();

            // Domicilio
            $table->string('calle')->nullable();
            $table->string('calle_2')->nullable();
            $table->string('codigo_postal')->nullable();
            $table->string('ciudad')->nullable();
            $table->string('municipio')->nullable();
            $table->string('localidad')->nullable();
            $table->string('estado')->nullable();
            $table->string('pais')->default('México');

            // Datos demográficos
            $table->date('fecha_nacimiento')->nullable();
            $table->smallInteger('edad')->nullable();
            $table->enum('sexo', ['M', 'F', 'Otro'])->nullable();

            // Educación y patrimonio
            $table->string('nivel_educativo')->nullable();
            $table->boolean('habla_maya')->default(false);

            // Redes sociales y web
            $table->string('facebook_negocio')->nullable();
            $table->string('instagram_negocio')->nullable();
            $table->string('tiktok_negocio')->nullable();
            $table->string('sitio_web')->nullable();

            // Preferencias de comunicación
            $table->string('idioma')->default('es');
            $table->string('medio_ingreso')->nullable();

            // Gestión de ciclo de vida
            $table->enum('tipo_persona', ['fisica', 'moral'])->default('fisica');
            $table->enum('estado_persona', ['activa', 'inactiva', 'bloqueada'])->default('activa');

            // Metadata
            $table->string('creado_por_modulo')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Índices
            $table->index('curp');
            $table->index('rfc');
            $table->index('email');
            $table->index('telefono');
            $table->index('municipio');
            $table->index('estado_persona');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personas');
    }
};
