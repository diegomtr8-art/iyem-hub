<?php

namespace App\Services;

use App\Models\Persona;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Encuentra o crea la persona a la que se refiere un sistema satélite.
 *
 * Es la pieza que sostiene todo el padrón central. Sin ella, cada módulo
 * daría de alta su propia copia del mismo emprendedor y el hub acabaría
 * siendo un séptimo lugar donde está mal capturado.
 *
 * Orden de coincidencia, de más a menos confiable:
 *
 *   1. CURP           Identificador único de la persona ante RENAPO.
 *   2. RFC            Único ante el SAT, pero una persona moral y su
 *                     representante pueden compartir contacto.
 *   3. Correo         Único en la tabla, aunque se comparte en familias
 *                     y negocios.
 *   4. Teléfono + nombre  El teléfono solo no basta: en Yucatán es común
 *                     que varios miembros de un negocio den el mismo
 *                     número. Se exige además que el nombre se parezca.
 *
 * Si nada coincide, se crea la persona y se anota qué módulo la trajo.
 */
class ResolvedorPersonas
{
    /**
     * Qué tan parecidos deben ser dos nombres para aceptar la coincidencia
     * por teléfono. 82 % tolera un segundo apellido faltante o un acento
     * distinto, sin llegar a confundir a dos hermanos.
     */
    public const SIMILITUD_MINIMA_DE_NOMBRE = 82.0;

    /**
     * @param  array<string, mixed>  $datos
     * @return array{persona: Persona, creada: bool, coincidio_por: string|null}
     */
    public function resolver(array $datos, ?string $moduloOrigen = null): array
    {
        $normalizados = $this->normalizar($datos);

        // La transacción evita que dos módulos que dan de alta a la misma
        // persona en el mismo instante creen dos registros.
        return DB::transaction(function () use ($normalizados, $moduloOrigen) {
            $coincidencia = $this->buscarCoincidencia($normalizados);

            if ($coincidencia !== null) {
                [$persona, $criterio] = $coincidencia;

                $this->completarHuecos($persona, $normalizados, $moduloOrigen);

                return ['persona' => $persona, 'creada' => false, 'coincidio_por' => $criterio];
            }

            $persona = Persona::create([
                ...array_filter($normalizados, fn ($valor) => $valor !== null),
                'creado_por_modulo' => $moduloOrigen ?? 'api',
            ]);

            return ['persona' => $persona, 'creada' => true, 'coincidio_por' => null];
        });
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array{0: Persona, 1: string}|null
     */
    private function buscarCoincidencia(array $datos): ?array
    {
        if (! empty($datos['curp'])) {
            $persona = Persona::withoutGlobalScope('aislamiento_demo')
                ->where('curp', $datos['curp'])->first();

            if ($persona) {
                return [$persona, 'curp'];
            }
        }

        if (! empty($datos['rfc'])) {
            $persona = Persona::withoutGlobalScope('aislamiento_demo')
                ->where('rfc', $datos['rfc'])->first();

            if ($persona) {
                return [$persona, 'rfc'];
            }
        }

        if (! empty($datos['email'])) {
            $persona = Persona::withoutGlobalScope('aislamiento_demo')
                ->where('email', $datos['email'])->first();

            if ($persona) {
                return [$persona, 'email'];
            }
        }

        if (! empty($datos['telefono']) && ! empty($datos['nombre_completo'])) {
            $candidatas = Persona::withoutGlobalScope('aislamiento_demo')
                ->where(function ($query) use ($datos) {
                    $query->where('telefono', $datos['telefono'])
                        ->orWhere('telefono_secundario', $datos['telefono']);
                })
                ->get();

            foreach ($candidatas as $candidata) {
                if ($this->nombresSeParecen($candidata->nombre_completo, $datos['nombre_completo'])) {
                    return [$candidata, 'telefono_y_nombre'];
                }
            }
        }

        return null;
    }

    /**
     * Rellena los campos que la persona todavía no tenía.
     *
     * Nunca sobrescribe un dato existente: si CREA dice que el teléfono es
     * otro, ese conflicto lo resuelve una persona desde la ficha, no una
     * llamada a la API. Lo que sí hace es completar los huecos, que es como
     * el padrón se va enriqueciendo con lo que cada módulo aporta.
     */
    private function completarHuecos(Persona $persona, array $datos, ?string $moduloOrigen): void
    {
        $cambios = [];

        foreach ($datos as $campo => $valor) {
            if ($valor === null || $valor === '') {
                continue;
            }

            if (blank($persona->getRawOriginal($campo))) {
                $cambios[$campo] = $valor;
            }
        }

        if ($cambios === []) {
            return;
        }

        $persona->fill($cambios)->save();

        foreach ($cambios as $campo => $valor) {
            $persona->marcarAuditoria(
                campo: $campo,
                valor_anterior: null,
                valor_nuevo: (string) $valor,
                usuario_id: null,
                modulo: $moduloOrigen ?? 'api'
            );
        }
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    private function normalizar(array $datos): array
    {
        $limpiar = fn (?string $valor) => filled($valor) ? trim($valor) : null;

        return array_filter([
            'nombre_completo' => $limpiar($datos['nombre'] ?? $datos['nombre_completo'] ?? null),
            'curp' => filled($datos['curp'] ?? null) ? Str::upper(trim($datos['curp'])) : null,
            'rfc' => filled($datos['rfc'] ?? null) ? Str::upper(trim($datos['rfc'])) : null,
            'email' => filled($datos['email'] ?? null) ? Str::lower(trim($datos['email'])) : null,
            // Se conservan solo los dígitos: los módulos mandan el teléfono
            // con guiones, espacios y paréntesis según quién lo capturó.
            'telefono' => filled($datos['telefono'] ?? null)
                ? preg_replace('/\D+/', '', $datos['telefono'])
                : null,
            'municipio' => $limpiar($datos['municipio'] ?? null),
            'estado' => $limpiar($datos['estado'] ?? null),
        ], fn ($valor) => $valor !== null);
    }

    /**
     * Compara dos nombres ignorando acentos, mayúsculas y el orden de las
     * palabras, que en México cambia según si quien capturó puso primero el
     * nombre o los apellidos.
     */
    private function nombresSeParecen(string $uno, string $otro): bool
    {
        $canonizar = function (string $nombre): string {
            $partes = preg_split('/\s+/', Str::lower(Str::ascii(trim($nombre))), -1, PREG_SPLIT_NO_EMPTY);
            sort($partes);

            return implode(' ', $partes);
        };

        $a = $canonizar($uno);
        $b = $canonizar($otro);

        if ($a === $b) {
            return true;
        }

        similar_text($a, $b, $porcentaje);

        return $porcentaje >= self::SIMILITUD_MINIMA_DE_NOMBRE;
    }
}
