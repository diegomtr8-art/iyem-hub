<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La migración original de `personas` (2026_08_17_172734) ya se había ejecutado
 * en producción con el esquema viejo (nombre/apellido/empresa/estado) antes de
 * reescribirse hoy con el esquema nuevo de 33 columnas. Laravel no vuelve a
 * correr una migración ya registrada, así que en cualquier entorno donde ya
 * corrió la versión vieja, esta migración tira la tabla y la reconstruye con
 * la estructura actual. Es idempotente: en un entorno nuevo donde la migración
 * original ya crea el esquema correcto, esto simplemente la recrea igual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('personas');

        Schema::create('personas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_completo');
            $table->string('email')->nullable()->unique();
            $table->string('telefono')->nullable();
            $table->string('telefono_secundario')->nullable();

            $table->string('curp')->nullable()->unique();
            $table->string('rfc')->nullable();
            $table->string('ine_clave')->nullable();

            $table->string('calle')->nullable();
            $table->string('calle_2')->nullable();
            $table->string('codigo_postal')->nullable();
            $table->string('ciudad')->nullable();
            $table->string('municipio')->nullable();
            $table->string('localidad')->nullable();
            $table->string('estado')->nullable();
            $table->string('pais')->default('México');

            $table->date('fecha_nacimiento')->nullable();
            $table->smallInteger('edad')->nullable();
            $table->enum('sexo', ['M', 'F', 'Otro'])->nullable();

            $table->string('nivel_educativo')->nullable();
            $table->boolean('habla_maya')->default(false);

            $table->string('facebook_negocio')->nullable();
            $table->string('instagram_negocio')->nullable();
            $table->string('tiktok_negocio')->nullable();
            $table->string('sitio_web')->nullable();

            $table->string('idioma')->default('es');
            $table->string('medio_ingreso')->nullable();

            $table->enum('tipo_persona', ['fisica', 'moral'])->default('fisica');
            $table->enum('estado_persona', ['activa', 'inactiva', 'bloqueada'])->default('activa');

            $table->string('creado_por_modulo')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('curp');
            $table->index('rfc');
            $table->index('email');
            $table->index('telefono');
            $table->index('municipio');
            $table->index('estado_persona');
        });
    }

    public function down(): void
    {
        // Irreversible a propósito: no hay forma segura de recrear el esquema viejo.
    }
};
