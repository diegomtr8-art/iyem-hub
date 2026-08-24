<?php

namespace App\Services\Consultas;

use App\Models\Persona;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginador;
use Illuminate\Support\Collection;

/**
 * Qué tan completo está el padrón, campo por campo.
 *
 * Es la consulta menos vistosa y la más útil: sin CURP no hay forma de
 * cruzar a una persona entre módulos, y sin teléfono no hay forma de
 * contactarla. Este tablero dice exactamente cuánto falta y dónde.
 *
 * Los campos se miden con `getRawOriginal` a nivel SQL, no leyendo el
 * modelo: el enmascarado del rol Tester devuelve un valor no vacío incluso
 * para un dato ausente, y eso inflaría los porcentajes.
 */
class CalidadDeDatos implements Consulta
{
    /**
     * Campos que se miden, con su peso en la calidad general.
     *
     * CURP pesa más porque es lo único que permite cruzar a una persona
     * entre sistemas con certeza.
     */
    private const CAMPOS = [
        'curp' => ['etiqueta' => 'CURP', 'peso' => 3, 'critico' => true],
        'telefono' => ['etiqueta' => 'Teléfono', 'peso' => 3, 'critico' => true],
        'email' => ['etiqueta' => 'Correo electrónico', 'peso' => 2, 'critico' => false],
        'rfc' => ['etiqueta' => 'RFC', 'peso' => 1, 'critico' => false],
        'municipio' => ['etiqueta' => 'Municipio', 'peso' => 3, 'critico' => true],
        'calle' => ['etiqueta' => 'Calle y número', 'peso' => 2, 'critico' => false],
        'codigo_postal' => ['etiqueta' => 'Código postal', 'peso' => 1, 'critico' => false],
        'fecha_nacimiento' => ['etiqueta' => 'Fecha de nacimiento', 'peso' => 2, 'critico' => false],
        'sexo' => ['etiqueta' => 'Sexo', 'peso' => 1, 'critico' => false],
        'nivel_educativo' => ['etiqueta' => 'Nivel educativo', 'peso' => 1, 'critico' => false],
        'medio_ingreso' => ['etiqueta' => 'Cómo llegó al IYEM', 'peso' => 1, 'critico' => false],
    ];

    public function clave(): string
    {
        return 'calidad-de-datos';
    }

    public function titulo(): string
    {
        return 'Calidad de datos';
    }

    public function descripcion(): string
    {
        return 'Qué porcentaje de las fichas tiene cada campo capturado, y cuáles son los huecos más grandes.';
    }

    public function icono(): string
    {
        return 'exito';
    }

    public function controles(): array
    {
        return [];
    }

    public function columnas(): array
    {
        return [
            'campo' => 'Campo',
            'capturados' => 'Fichas con el dato',
            'vacios' => 'Fichas sin el dato',
            'porcentaje' => 'Cobertura',
            'importancia' => 'Importancia',
        ];
    }

    public function resumen(FiltrosConsulta $filtros): array
    {
        $total = $this->total($filtros);
        $filas = $this->calcular($filtros);

        if ($total === 0) {
            return [['etiqueta' => 'Fichas evaluadas', 'valor' => 0]];
        }

        // Promedio ponderado: un padrón sin CURP no está "80 % completo"
        // solo porque todos tengan nivel educativo.
        $pesoTotal = collect(self::CAMPOS)->sum('peso');
        $puntaje = $filas->sum(
            fn (array $fila) => ($fila['capturados'] / $total) * self::CAMPOS[$fila['clave']]['peso']
        );

        $peorCritico = $filas
            ->filter(fn (array $f) => self::CAMPOS[$f['clave']]['critico'])
            ->sortBy('capturados')
            ->first();

        return [
            ['etiqueta' => 'Fichas evaluadas', 'valor' => $total],
            [
                'etiqueta' => 'Calidad general',
                'valor' => round($puntaje / $pesoTotal * 100, 1).' %',
                'detalle' => 'Promedio ponderado por importancia',
            ],
            [
                'etiqueta' => 'Fichas con CURP',
                'valor' => $filas->firstWhere('clave', 'curp')['porcentaje'],
                'detalle' => 'Sin CURP no se puede cruzar entre módulos',
            ],
            [
                'etiqueta' => 'Mayor hueco crítico',
                'valor' => $peorCritico['campo'] ?? '—',
                'detalle' => $peorCritico ? $peorCritico['vacios'].' fichas sin el dato' : null,
            ],
        ];
    }

    public function tabla(FiltrosConsulta $filtros): LengthAwarePaginator
    {
        $filas = $this->calcular($filtros);

        return new Paginador(
            items: $filas->all(),
            total: $filas->count(),
            perPage: 50,
            currentPage: 1,
            options: ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    public function grafica(FiltrosConsulta $filtros): ?array
    {
        $filas = $this->calcular($filtros);
        $total = $this->total($filtros);

        if ($total === 0) {
            return null;
        }

        return [
            'tipo' => 'bar',
            'etiquetas' => $filas->pluck('campo')->all(),
            'series' => [[
                'etiqueta' => '% de fichas con el dato',
                'datos' => $filas->map(fn (array $f) => round($f['capturados'] / $total * 100, 1))->all(),
            ]],
        ];
    }

    public function filasParaExportar(FiltrosConsulta $filtros): Collection
    {
        return $this->calcular($filtros);
    }

    private function calcular(FiltrosConsulta $filtros): Collection
    {
        $total = $this->total($filtros);

        return collect(self::CAMPOS)
            ->map(function (array $definicion, string $campo) use ($filtros, $total) {
                $capturados = $filtros->aplicarAPersonas(Persona::query())
                    ->whereNotNull($campo)
                    ->where($campo, '!=', '')
                    ->count();

                return [
                    'clave' => $campo,
                    'campo' => $definicion['etiqueta'],
                    'capturados' => $capturados,
                    'vacios' => $total - $capturados,
                    'porcentaje' => $total > 0 ? round($capturados / $total * 100, 1).' %' : '—',
                    'importancia' => $definicion['critico'] ? 'Crítico' : 'Deseable',
                ];
            })
            // Los huecos más grandes arriba: es la lista de pendientes.
            ->sortBy('capturados')
            ->values();
    }

    private function total(FiltrosConsulta $filtros): int
    {
        return $filtros->aplicarAPersonas(Persona::query())->count();
    }
}
