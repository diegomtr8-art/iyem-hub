<?php

namespace App\Services;

use App\Models\Persona;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Encuentra personas que probablemente son la misma.
 *
 * Los cuatro primeros criterios son exactos y no admiten discusión: dos
 * fichas con la misma CURP son la misma persona. El quinto, por similitud
 * de nombre, es una sospecha y se marca como tal: nunca fusiona solo.
 *
 * NOTA SOBRE EL ESQUEMA ACTUAL
 *
 * `personas.curp` y `personas.email` tienen índice UNIQUE, así que en esta
 * base **esos dos duplicados no pueden existir**: la restricción los frena
 * al insertar. Los criterios se conservan igual por dos razones:
 *
 *   1. Una carga histórica que entre con `insertOrIgnore`, o una migración
 *      futura que relaje la restricción, los volvería posibles de inmediato.
 *   2. El mismo detector se pensó para poder correrse contra las bases de
 *      los módulos satélite, que no tienen esa garantía.
 *
 * En la práctica, hoy los duplicados reales entran por RFC, por teléfono y
 * por nombre mal capturado. La pantalla lo dice para que nadie interprete
 * "cero duplicados por CURP" como un indicador de calidad.
 */
class DetectorDuplicados
{
    /**
     * Umbral de similitud de nombre, en porcentaje.
     *
     * Calibrado contra casos reales del padrón yucateco:
     *
     *   Adriana Peniche Gómez  vs  Adriana Peniche Gomez    100.0 %  (acento)
     *   Adriana Peniche Gómez  vs  Adriana Penicheh Gómez    97.7 %  (errata)
     *   Ana Canul Poot         vs  Ana Canul Pool            92.9 %  (letra)
     *   Adriana Peniche Gómez  vs  Adriana Peniche           83.3 %  (falta apellido)
     *   Ana Canul Poot         vs  Beto Canul Poot           75.9 %  (hermanos)
     *
     * A 85 % entran los errores de captura y quedan fuera los hermanos, que
     * es lo que importa: fusionar es destructivo, así que ante la duda es
     * mejor no proponer el par. El caso del apellido faltante (83.3 %) queda
     * justo abajo y se pierde; bajar a 82 % lo atraparía, a costa de más
     * falsos positivos que un humano tendría que descartar uno por uno.
     */
    public const SIMILITUD_MINIMA = 85.0;

    /**
     * Criterios exactos: columna del padrón y qué tan fuerte es la señal.
     */
    private const CRITERIOS_EXACTOS = [
        'curp' => ['etiqueta' => 'Misma CURP', 'confianza' => 'certeza'],
        'rfc' => ['etiqueta' => 'Mismo RFC', 'confianza' => 'alta'],
        'email' => ['etiqueta' => 'Mismo correo', 'confianza' => 'alta'],
        'telefono' => ['etiqueta' => 'Mismo teléfono', 'confianza' => 'media'],
    ];

    /**
     * Todos los grupos de posibles duplicados.
     *
     * @return Collection<int, array{criterio: string, etiqueta: string, confianza: string, valor: string|null, personas: Collection}>
     */
    public function detectar(bool $incluirSimilitud = true): Collection
    {
        $grupos = collect();

        foreach (self::CRITERIOS_EXACTOS as $columna => $definicion) {
            $grupos = $grupos->merge($this->porColumna($columna, $definicion));
        }

        if ($incluirSimilitud) {
            $grupos = $grupos->merge($this->porSimilitudDeNombre());
        }

        // Un mismo par puede caer por CURP y por correo a la vez. Se conserva
        // el criterio más fuerte para no pedirle al Super Admin que revise
        // dos veces el mismo caso.
        return $this->deduplicarGrupos($grupos);
    }

    /**
     * @return Collection<int, array>
     */
    private function porColumna(string $columna, array $definicion): Collection
    {
        $valoresRepetidos = Persona::query()
            ->sinAislamientoDemo()
            ->whereNotNull($columna)
            ->where($columna, '!=', '')
            ->groupBy($columna)
            ->havingRaw('COUNT(*) > 1')
            ->pluck($columna);

        return $valoresRepetidos->map(function ($valor) use ($columna, $definicion) {
            $personas = Persona::query()
                ->sinAislamientoDemo()
                ->where($columna, $valor)
                ->orderBy('created_at')
                ->get();

            return [
                'criterio' => $columna,
                'etiqueta' => $definicion['etiqueta'],
                'confianza' => $definicion['confianza'],
                'valor' => $valor,
                'personas' => $personas,
            ];
        })->filter(fn (array $grupo) => $grupo['personas']->count() > 1);
    }

    /**
     * Sospechas por parecido de nombre.
     *
     * Comparar cada ficha contra todas sería cuadrático y con un padrón
     * grande no terminaría nunca. Se acota a personas del mismo municipio
     * con la misma inicial de apellido: dos capturas de la misma persona
     * casi siempre comparten ambas cosas, y eso reduce el universo a grupos
     * pequeños antes de calcular la similitud.
     *
     * @return Collection<int, array>
     */
    private function porSimilitudDeNombre(): Collection
    {
        $grupos = collect();
        $yaEmparejadas = [];

        $porBloque = Persona::query()
            ->sinAislamientoDemo()
            ->select(['id', 'nombre_completo', 'municipio', 'curp', 'rfc', 'email', 'telefono', 'created_at'])
            ->orderBy('id')
            ->get()
            ->groupBy(fn (Persona $persona) => Str::lower(
                ($persona->municipio ?? 'sin-municipio').'|'.
                Str::substr(Str::ascii($persona->nombre_completo), 0, 1)
            ));

        foreach ($porBloque as $bloque) {
            if ($bloque->count() < 2) {
                continue;
            }

            foreach ($bloque as $i => $persona) {
                foreach ($bloque->slice($i + 1) as $otra) {
                    $par = "{$persona->id}-{$otra->id}";

                    if (isset($yaEmparejadas[$par])) {
                        continue;
                    }

                    $similitud = $this->similitud($persona->nombre_completo, $otra->nombre_completo);

                    if ($similitud < self::SIMILITUD_MINIMA) {
                        continue;
                    }

                    $yaEmparejadas[$par] = true;

                    $grupos->push([
                        'criterio' => 'similitud_nombre',
                        'etiqueta' => 'Nombre parecido ('.round($similitud).' %)',
                        'confianza' => 'sospecha',
                        'valor' => null,
                        'personas' => collect([$persona, $otra]),
                    ]);
                }
            }
        }

        return $grupos;
    }

    /**
     * Similitud entre dos nombres, ignorando acentos, mayúsculas y el orden
     * de las palabras.
     *
     * Se usa `similar_text` y no `levenshtein` a secas porque este último
     * está limitado a 255 bytes y penaliza igual una letra cambiada que un
     * apellido entero de más, que es el caso frecuente aquí.
     */
    public function similitud(string $uno, string $otro): float
    {
        $canonizar = function (string $nombre): string {
            $partes = preg_split('/\s+/', Str::lower(Str::ascii(trim($nombre))), -1, PREG_SPLIT_NO_EMPTY);
            sort($partes);

            return implode(' ', $partes);
        };

        $a = $canonizar($uno);
        $b = $canonizar($otro);

        if ($a === '' || $b === '') {
            return 0.0;
        }

        if ($a === $b) {
            return 100.0;
        }

        similar_text($a, $b, $porcentaje);

        return round($porcentaje, 1);
    }

    /**
     * Cuando el mismo par aparece por varios criterios, gana el más fuerte.
     */
    private function deduplicarGrupos(Collection $grupos): Collection
    {
        $orden = ['certeza' => 0, 'alta' => 1, 'media' => 2, 'sospecha' => 3];

        return $grupos
            ->sortBy(fn (array $grupo) => $orden[$grupo['confianza']] ?? 9)
            ->unique(fn (array $grupo) => $grupo['personas']->pluck('id')->sort()->implode('-'))
            ->values();
    }

    /**
     * Resumen para el comando de consola y el encabezado de la pantalla.
     */
    public function resumen(Collection $grupos): array
    {
        return [
            'grupos' => $grupos->count(),
            'personas_involucradas' => $grupos->flatMap(fn (array $g) => $g['personas']->pluck('id'))->unique()->count(),
            'por_criterio' => $grupos->countBy('criterio')->all(),
            'por_confianza' => $grupos->countBy('confianza')->all(),
            'criterios_bloqueados_por_esquema' => $this->criteriosImposibles(),
        ];
    }

    /**
     * Criterios que nunca darán resultado porque la base los impide.
     *
     * Se reporta explícitamente: sin esta advertencia, un "0 duplicados por
     * CURP" se leería como un logro de calidad del padrón cuando en realidad
     * es la restricción UNIQUE haciendo su trabajo.
     *
     * @return array<int, string>
     */
    public function criteriosImposibles(): array
    {
        $imposibles = [];

        foreach (['curp', 'email'] as $columna) {
            if ($this->columnaEsUnica($columna)) {
                $imposibles[] = $columna;
            }
        }

        return $imposibles;
    }

    private function columnaEsUnica(string $columna): bool
    {
        try {
            return collect(Schema::getIndexes('personas'))
                ->contains(fn (array $indice) => ($indice['unique'] ?? false)
                    && $indice['columns'] === [$columna]);
        } catch (\Throwable) {
            // Motores que no exponen los índices: se asume que sí puede haber
            // duplicados, que es el supuesto conservador.
            return false;
        }
    }
}
