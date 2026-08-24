<?php

namespace App\Services\Consultas;

use App\Models\Persona;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Personas que llevan mucho tiempo sin moverse en ningún módulo.
 *
 * Sirve para dos cosas opuestas y ambas legítimas: reactivar a quien se
 * quedó a medio camino, y depurar el padrón de quien ya no volverá.
 *
 * "Sin actividad" significa que la persona no tiene **ningún** movimiento
 * dentro de la ventana, en ninguno de los cinco módulos integrados. Que su
 * ficha no se haya editado no cuenta como actividad: corregirle el teléfono
 * no es que la persona haya vuelto.
 *
 * La fecha que cuenta es la del hecho —cuándo se inscribió, cuándo solicitó
 * el crédito—, no el `created_at` de la fila. Medir por `created_at` haría
 * que una carga histórica volviera "activa" de golpe a gente que no pisa el
 * instituto desde hace años.
 */
class PersonasSinActividad implements Consulta
{
    /**
     * Relación de Eloquent y columna con la fecha real del hecho por módulo.
     * Si esa columna viene vacía se cae al `created_at` de la fila, que es
     * lo mejor que se sabe de ese registro.
     */
    private const MOVIMIENTOS = [
        'creaSolicitudes' => 'fecha_solicitud',
        'impulstateInscripciones' => 'fecha_inscripcion',
        'nodicoMembresias' => 'fecha_inicio',
        'herenciaVivaClientes' => 'fecha_primer_compra',
        'juridicoAsesorias' => 'fecha_asesoria',
    ];

    public function clave(): string
    {
        return 'personas-sin-actividad';
    }

    public function titulo(): string
    {
        return 'Personas sin actividad';
    }

    public function descripcion(): string
    {
        return 'Quiénes no tienen ningún movimiento en los módulos dentro de la ventana elegida.';
    }

    public function icono(): string
    {
        return 'reloj';
    }

    public function controles(): array
    {
        return [
            [
                'nombre' => 'meses',
                'etiqueta' => 'Sin actividad durante',
                'tipo' => 'select',
                'opciones' => [
                    '6' => '6 meses',
                    '12' => '12 meses',
                    '24' => '24 meses',
                    '36' => '36 meses',
                ],
            ],
            [
                'nombre' => 'estado_persona',
                'etiqueta' => 'Estado de la persona',
                'tipo' => 'select',
                'opciones' => [
                    '' => 'Todos',
                    'activa' => 'Solo activas',
                    'inactiva' => 'Solo inactivas',
                ],
            ],
        ];
    }

    public function columnas(): array
    {
        return [
            'nombre_completo' => 'Nombre',
            'municipio' => 'Municipio',
            'telefono' => 'Teléfono',
            'ultimo_movimiento' => 'Último movimiento',
            'alta' => 'Alta en el padrón',
            'estado_persona' => 'Estado',
        ];
    }

    public function resumen(FiltrosConsulta $filtros): array
    {
        $meses = $this->meses($filtros);
        $inactivas = $this->consulta($filtros)->count();
        $universo = $filtros->aplicarAPersonas(Persona::query())->count();

        return [
            ['etiqueta' => "Sin actividad en {$meses} meses", 'valor' => $inactivas],
            [
                'etiqueta' => 'Del padrón filtrado',
                'valor' => $universo > 0 ? round($inactivas / $universo * 100, 1).' %' : '—',
                'detalle' => number_format($universo).' personas en el universo',
            ],
            [
                'etiqueta' => 'Con teléfono para reactivar',
                'valor' => (clone $this->consulta($filtros))->whereNotNull('telefono')->count(),
            ],
        ];
    }

    public function tabla(FiltrosConsulta $filtros): LengthAwarePaginator
    {
        return $this->consulta($filtros)
            ->orderBy('created_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Persona $persona) => $this->formatear($persona));
    }

    public function grafica(FiltrosConsulta $filtros): ?array
    {
        // Cómo se reparte la inactividad entre los municipios: dice dónde
        // conviene hacer la campaña de reactivación.
        $filas = $this->consulta($filtros)
            ->whereNotNull('municipio')
            ->selectRaw('municipio, COUNT(*) as total')
            ->groupBy('municipio')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        if ($filas->isEmpty()) {
            return null;
        }

        return [
            'tipo' => 'bar',
            'etiquetas' => $filas->pluck('municipio')->all(),
            'series' => [[
                'etiqueta' => 'Personas sin actividad',
                'datos' => $filas->pluck('total')->map(fn ($v) => (int) $v)->all(),
            ]],
        ];
    }

    public function filasParaExportar(FiltrosConsulta $filtros): Collection
    {
        return $this->consulta($filtros)
            ->orderBy('created_at')
            ->get()
            ->map(fn (Persona $persona) => $this->formatear($persona));
    }

    private function consulta(FiltrosConsulta $filtros)
    {
        $corte = Carbon::now()->subMonths($this->meses($filtros));

        $consulta = $filtros->aplicarAPersonas(Persona::query())
            ->when(
                $filtros->extra['estado_persona'] ?? null,
                fn ($q, $v) => $q->where('estado_persona', $v)
            );

        foreach (self::MOVIMIENTOS as $relacion => $columnaFecha) {
            $consulta->whereDoesntHave(
                $relacion,
                fn ($q) => $q->whereRaw("COALESCE({$columnaFecha}, created_at) >= ?", [$corte])
            );
        }

        return $consulta;
    }

    private function meses(FiltrosConsulta $filtros): int
    {
        $meses = (int) ($filtros->extra['meses'] ?? 12);

        return in_array($meses, [6, 12, 24, 36], true) ? $meses : 12;
    }

    private function formatear(Persona $persona): array
    {
        $fechas = collect(self::MOVIMIENTOS)
            ->map(fn (string $columnaFecha, string $relacion) => $persona->{$relacion}()
                ->selectRaw("MAX(COALESCE({$columnaFecha}, created_at)) as ultima")
                ->value('ultima'))
            ->filter();

        return [
            'id' => $persona->id,
            'nombre_completo' => $persona->nombre_completo,
            'municipio' => $persona->municipio ?? '—',
            'telefono' => $persona->telefono ?? '—',
            'ultimo_movimiento' => $fechas->isEmpty()
                ? 'Nunca'
                : Carbon::parse($fechas->max())->translatedFormat('d M Y'),
            'alta' => $persona->created_at?->translatedFormat('d M Y') ?? '—',
            'estado_persona' => $persona->estado_persona,
        ];
    }
}
