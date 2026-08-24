<?php

namespace App\Models;

use App\Models\Modulos\CitasAgendamiento;
use App\Models\Modulos\CreaSolicitud;
use App\Models\Modulos\HerenciaVivaCliente;
use App\Models\Modulos\ImpulsateInscripcion;
use App\Models\Modulos\JuridicoAsesoria;
use App\Models\Modulos\NodicoMembresia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Persona extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Campos que el rol Tester no puede ver en claro.
     *
     * Se enmascaran en el modelo y no en la vista a propósito: así quedan
     * protegidos también en la API, en las exportaciones y en cualquier
     * pantalla que se escriba después sin acordarse de esta regla.
     */
    public const CAMPOS_SENSIBLES = [
        'curp',
        'rfc',
        'ine_clave',
        'telefono',
        'telefono_secundario',
        'calle',
        'calle_2',
        'codigo_postal',
    ];

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
        'demo',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'habla_maya' => 'boolean',
        'demo' => 'boolean',
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

    public function scopePorEtiqueta($query, $etiqueta)
    {
        return $query->whereHas('etiquetas', fn ($q) => $q->where('etiqueta', $etiqueta));
    }

    public function scopeGeolocalizadas($query)
    {
        return $query->whereNotNull('latitud')->whereNotNull('longitud');
    }

    public function scopeDemo($query)
    {
        return $query->where('demo', true);
    }

    public function scopeReales($query)
    {
        return $query->where('demo', false);
    }

    /**
     * Ignora el aislamiento de datos de demostración.
     *
     * Solo para comandos de consola y mantenimiento que necesitan recorrer
     * el padrón completo aunque haya una sesión de Tester activa.
     */
    public function scopeSinAislamientoDemo(Builder $query): Builder
    {
        return $query->withoutGlobalScope('aislamiento_demo');
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

    /**
     * Valor real de un campo, sin pasar por el enmascarado.
     *
     * Lo usan la detección de duplicados, la fusión y la API entre sistemas,
     * que necesitan comparar CURP y RFC de verdad.
     */
    public function valorSinEnmascarar(string $campo): mixed
    {
        return $this->getRawOriginal($campo);
    }

    /**
     * ¿La sesión actual debe ver los campos sensibles enmascarados?
     */
    public function debeEnmascarar(): bool
    {
        $usuario = Auth::user();

        return $usuario !== null
            && method_exists($usuario, 'hasRole')
            && $usuario->hasRole('Tester');
    }

    /**
     * Deja visibles los dos últimos caracteres: suficiente para cotejar un
     * dato contra un documento, insuficiente para llevárselo.
     */
    public static function enmascarar(?string $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return $valor;
        }

        $largo = mb_strlen($valor);

        if ($largo <= 2) {
            return str_repeat('*', $largo);
        }

        return str_repeat('*', min($largo - 2, 8)).mb_substr($valor, -2);
    }

    /**
     * Aplica el enmascarado solo si la sesión actual lo amerita.
     *
     * Los accessors de abajo son explícitos —y repetitivos— a propósito:
     * Eloquent solo consulta los mutadores declarados al armar `toArray()`,
     * así que sobrescribir `getAttribute()` protegería `$persona->curp` pero
     * dejaría el valor en claro al serializar la respuesta de Inertia o de
     * la API. Un accessor por campo cubre ambos caminos.
     */
    private function quizasEnmascarar(mixed $valor): mixed
    {
        if (! $this->debeEnmascarar()) {
            return $valor;
        }

        return self::enmascarar($valor === null ? null : (string) $valor);
    }

    public function getCurpAttribute(mixed $valor): mixed
    {
        return $this->quizasEnmascarar($valor);
    }

    public function getRfcAttribute(mixed $valor): mixed
    {
        return $this->quizasEnmascarar($valor);
    }

    public function getIneClaveAttribute(mixed $valor): mixed
    {
        return $this->quizasEnmascarar($valor);
    }

    public function getTelefonoAttribute(mixed $valor): mixed
    {
        return $this->quizasEnmascarar($valor);
    }

    public function getTelefonoSecundarioAttribute(mixed $valor): mixed
    {
        return $this->quizasEnmascarar($valor);
    }

    public function getCalleAttribute(mixed $valor): mixed
    {
        return $this->quizasEnmascarar($valor);
    }

    public function getCalle2Attribute(mixed $valor): mixed
    {
        return $this->quizasEnmascarar($valor);
    }

    public function getCodigoPostalAttribute(mixed $valor): mixed
    {
        return $this->quizasEnmascarar($valor);
    }

    protected static function booted(): void
    {
        /*
         * Aislamiento de datos de demostración.
         *
         * Mientras haya una sesión de Tester, el padrón entero se reduce a las
         * personas ficticias. Va como scope global para que aplique también en
         * la API, en las consultas cruzadas y en el mapa, sin depender de que
         * cada consulta se acuerde de filtrar.
         */
        static::addGlobalScope('aislamiento_demo', function (Builder $query) {
            $usuario = Auth::user();

            if ($usuario && method_exists($usuario, 'hasRole') && $usuario->hasRole('Tester')) {
                $query->where($query->getModel()->getTable().'.demo', true);
            }
        });

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
