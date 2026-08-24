<?php

namespace App\Models;

use App\Models\Modulos\CitasAgendamiento;
use App\Models\Modulos\CreaSolicitud;
use App\Models\Modulos\HerenciaVivaCliente;
use App\Models\Modulos\ImpulsateInscripcion;
use App\Models\Modulos\JuridicoAsesoria;
use App\Models\Modulos\NodicoMembresia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Persona extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'personas';

    protected $fillable = [
        // Identidad básica
        'nombre_completo',
        'email',
        'telefono',
        'telefono_secundario',

        // Identificación oficial
        'curp',
        'rfc',
        'ine_clave',

        // Domicilio
        'calle',
        'calle_2',
        'codigo_postal',
        'ciudad',
        'municipio',
        'localidad',
        'latitud',
        'longitud',
        'estado',
        'pais',

        // Datos demográficos
        'fecha_nacimiento',
        'edad',
        'sexo',

        // Educación y patrimonio
        'nivel_educativo',
        'habla_maya',

        // Redes sociales y web
        'facebook_negocio',
        'instagram_negocio',
        'tiktok_negocio',
        'sitio_web',

        // Preferencias de comunicación
        'idioma',
        'medio_ingreso',

        // Gestión de ciclo de vida
        'tipo_persona',
        'estado_persona',

        // Metadata
        'creado_por_modulo',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'habla_maya' => 'boolean',
        'latitud' => 'decimal:7',
        'longitud' => 'decimal:7',
    ];

    // Relaciones
    public function creaSolicitudes()
    {
        return $this->hasMany(CreaSolicitud::class);
    }

    public function impulstateInscripciones()
    {
        return $this->hasMany(ImpulsateInscripcion::class);
    }

    public function nodicoMembresias()
    {
        return $this->hasMany(NodicoMembresia::class);
    }

    public function herenciaVivaClientes()
    {
        return $this->hasMany(HerenciaVivaCliente::class);
    }

    public function juridicoAsesorias()
    {
        return $this->hasMany(JuridicoAsesoria::class);
    }

    public function citasAgendamientos()
    {
        return $this->hasMany(CitasAgendamiento::class);
    }

    public function etiquetas()
    {
        return $this->hasMany(PersonaEtiqueta::class, 'persona_id');
    }

    public function auditorias()
    {
        return $this->hasMany(PersonaAuditoria::class);
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('estado_persona', 'activa');
    }

    public function scopeInactivas($query)
    {
        return $query->where('estado_persona', 'inactiva');
    }

    public function scopePorMunicipio($query, $municipio)
    {
        return $query->where('municipio', $municipio);
    }

    public function scopePorModulo($query, $modulo)
    {
        return $query->where('creado_por_modulo', $modulo);
    }

    // Métodos útiles
    public function marcarAuditoria($campo, $valor_anterior, $valor_nuevo, $usuario_id = null, $modulo = null)
    {
        return $this->auditorias()->create([
            'campo_modificado' => $campo,
            'valor_anterior' => $valor_anterior,
            'valor_nuevo' => $valor_nuevo,
            'usuario_id' => $usuario_id,
            'modulo_origen' => $modulo,
        ]);
    }

    public function agregarEtiqueta($etiqueta)
    {
        return $this->etiquetas()->firstOrCreate(['etiqueta' => $etiqueta]);
    }

    public function removerEtiqueta($etiqueta)
    {
        return $this->etiquetas()->where('etiqueta', $etiqueta)->delete();
    }

    public function scopePorEtiqueta($query, $etiqueta)
    {
        return $query->whereHas('etiquetas', fn ($q) => $q->where('etiqueta', $etiqueta));
    }

    public function scopeGeolocalizadas($query)
    {
        return $query->whereNotNull('latitud')->whereNotNull('longitud');
    }

    protected static function booted(): void
    {
        static::saving(function (Persona $persona) {
            if ($persona->fecha_nacimiento) {
                $persona->edad = $persona->fecha_nacimiento->diffInYears(now());
            }

            if ($persona->municipio && is_null($persona->latitud) && is_null($persona->longitud)) {
                $centroide = config("municipios_yucatan.{$persona->municipio}") ?? config('municipios_yucatan.Mérida');
                [$lat, $lng] = $centroide;

                $persona->latitud = $lat + (mt_rand(-300, 300) / 100000);
                $persona->longitud = $lng + (mt_rand(-300, 300) / 100000);
            }
        });
    }
}
