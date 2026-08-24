<?php

namespace App\Services;

use App\Models\Persona;
use Illuminate\Support\Collection;

/**
 * Arma la ficha 360° de una persona.
 *
 * Reúne en una sola pantalla lo que hoy está repartido entre seis sistemas:
 * los datos del padrón, la línea de tiempo de todo lo que esa persona ha
 * hecho con el instituto, y el conteo de sus registros por módulo.
 */
class FichaPersona
{
    public function __construct(private readonly CatalogoModulos $catalogo) {}

    /**
     * Los campos del padrón, agrupados y etiquetados según `config/padron.php`.
     *
     * Devuelve el valor tal como lo entrega el modelo, así que los campos
     * sensibles ya vienen enmascarados si la sesión es de Tester.
     */
    public function datosGenerales(Persona $persona): array
    {
        return collect(config('padron.secciones'))
            ->map(fn (array $seccion, string $clave) => [
                'clave' => $clave,
                'titulo' => $seccion['titulo'],
                'icono' => $seccion['icono'],
                'abierta' => $seccion['abierta'],
                'campos' => collect($seccion['campos'])
                    ->map(fn (array $campo, string $nombre) => [
                        'nombre' => $nombre,
                        'etiqueta' => $campo['etiqueta'],
                        'tipo' => $campo['tipo'],
                        'valor' => $this->valorLegible($persona, $nombre, $campo),
                        'vacio' => blank($persona->{$nombre}),
                    ])
                    ->values(),
            ])
            ->values()
            ->all();
    }

    /**
     * Todo lo que le ha pasado a esta persona, de lo más reciente a lo más
     * antiguo, sin importar de qué módulo venga.
     */
    public function lineaDeTiempo(Persona $persona): Collection
    {
        $eventos = collect();

        $eventos->push([
            'modulo' => 'padron',
            'titulo' => 'Alta en el padrón central',
            'detalle' => $persona->creado_por_modulo
                ? "Capturada desde el módulo {$persona->creado_por_modulo}"
                : 'Origen no registrado',
            'fecha' => $persona->created_at,
            'estado' => null,
        ]);

        foreach ($persona->impulstateInscripciones as $inscripcion) {
            $eventos->push([
                'modulo' => 'impulsate',
                'titulo' => $inscripcion->programa_nombre ?? 'Inscripción a Impúlsate',
                'detalle' => 'Inscripción a programa de capacitación',
                'fecha' => $inscripcion->fecha_inscripcion ?? $inscripcion->created_at,
                'estado' => $inscripcion->estado,
            ]);
        }

        foreach ($persona->creaSolicitudes as $solicitud) {
            $eventos->push([
                'modulo' => 'crea',
                'titulo' => 'Solicitud de crédito'.($solicitud->tipo_credito ? " · {$solicitud->tipo_credito}" : ''),
                'detalle' => $solicitud->monto_solicitado
                    ? 'Por $'.number_format((float) $solicitud->monto_solicitado, 2)
                    : 'Monto no capturado',
                'fecha' => $solicitud->fecha_solicitud ?? $solicitud->created_at,
                'estado' => $solicitud->estado_solicitud,
            ]);
        }

        foreach ($persona->nodicoMembresias as $membresia) {
            $eventos->push([
                'modulo' => 'nodico',
                'titulo' => 'Membresía de coworking'.($membresia->tipo_membresia ? " · {$membresia->tipo_membresia}" : ''),
                'detalle' => $membresia->fecha_fin
                    ? 'Vigente hasta '.$membresia->fecha_fin->translatedFormat('d \d\e F \d\e Y')
                    : 'Sin fecha de término',
                'fecha' => $membresia->fecha_inicio ?? $membresia->created_at,
                'estado' => $membresia->estado_membresia,
            ]);
        }

        foreach ($persona->herenciaVivaClientes as $cliente) {
            $eventos->push([
                'modulo' => 'herenciaviva',
                'titulo' => 'Alta como cliente de Herencia Viva',
                'detalle' => $cliente->numero_compras.' compra'.($cliente->numero_compras === 1 ? '' : 's')
                    .' por $'.number_format((float) $cliente->total_gastado, 2),
                'fecha' => $cliente->fecha_primer_compra ?? $cliente->created_at,
                'estado' => $cliente->es_mayorista ? 'mayorista' : null,
            ]);
        }

        foreach ($persona->juridicoAsesorias as $asesoria) {
            $eventos->push([
                'modulo' => 'juridico',
                'titulo' => 'Asesoría jurídica'.($asesoria->tipo_asesoria ? " · {$asesoria->tipo_asesoria}" : ''),
                'detalle' => 'Atención del área jurídica',
                'fecha' => $asesoria->fecha_asesoria ?? $asesoria->created_at,
                'estado' => $asesoria->estado,
            ]);
        }

        foreach ($persona->citasAgendamientos as $cita) {
            $eventos->push([
                'modulo' => $cita->modulo_destino ?: 'impulsate',
                'titulo' => 'Cita'.($cita->tipo_cita ? " · {$cita->tipo_cita}" : ''),
                'detalle' => $cita->modulo_destino
                    ? "Agendada con el módulo {$cita->modulo_destino}"
                    : 'Cita de asesoría',
                'fecha' => $cita->fecha_cita ?? $cita->created_at,
                'estado' => $cita->estado,
            ]);
        }

        return $eventos
            ->filter(fn (array $evento) => $evento['fecha'] !== null)
            ->map(fn (array $evento) => [
                ...$evento,
                'fecha' => $evento['fecha']->toIso8601String(),
                'modulo_nombre' => $this->catalogo->encontrar($evento['modulo'])['nombre'] ?? $evento['modulo'],
                'modulo_icono' => $this->catalogo->encontrar($evento['modulo'])['icono'] ?? 'squares-2x2',
                'modulo_color' => $this->catalogo->encontrar($evento['modulo'])['color'] ?? 'iyem-primario',
            ])
            ->sortByDesc('fecha')
            ->values();
    }

    /**
     * Una tarjeta por módulo donde la persona tiene registros.
     *
     * Los módulos sin registros no aparecen: una ficha llena de ceros
     * esconde lo que sí importa.
     */
    public function vinculos(Persona $persona): Collection
    {
        $conteos = [
            'crea' => $persona->creaSolicitudes->count(),
            'impulsate' => $persona->impulstateInscripciones->count(),
            'nodico' => $persona->nodicoMembresias->count(),
            'herenciaviva' => $persona->herenciaVivaClientes->count(),
            'juridico' => $persona->juridicoAsesorias->count(),
        ];

        $descripciones = [
            'crea' => fn (int $n) => $n.' solicitud'.($n === 1 ? '' : 'es').' de crédito',
            'impulsate' => fn (int $n) => $n.' inscripción'.($n === 1 ? '' : 'es').' a programas',
            'nodico' => fn (int $n) => $n.' membresía'.($n === 1 ? '' : 's').' de coworking',
            'herenciaviva' => fn (int $n) => $n.' registro'.($n === 1 ? '' : 's').' de cliente',
            'juridico' => fn (int $n) => $n.' asesoría'.($n === 1 ? '' : 's').' jurídica'.($n === 1 ? '' : 's'),
        ];

        return collect($conteos)
            ->filter(fn (int $total) => $total > 0)
            ->map(function (int $total, string $slug) use ($descripciones, $persona) {
                $modulo = $this->catalogo->encontrar($slug);

                return [
                    'slug' => $slug,
                    'nombre' => $modulo['nombre'] ?? $slug,
                    'icono' => $modulo['icono'] ?? 'squares-2x2',
                    'color' => $modulo['color'] ?? 'iyem-primario',
                    'total' => $total,
                    'descripcion' => $descripciones[$slug]($total),
                    /*
                     * Enlace profundo al sistema satélite, construido con el
                     * `persona_id` del hub. Los módulos que ya guardan esa
                     * columna lo resuelven directo; los que todavía no la
                     * tienen mostrarán un "no encontrado" hasta que la
                     * agreguen, que es justamente lo que documenta
                     * `docs/API_PADRON.md`.
                     */
                    'url' => ($modulo['navegable'] ?? false) && ($modulo['externo'] ?? false)
                        ? rtrim($modulo['url_destino'], '/')."/personas/{$persona->id}"
                        : null,
                    'navegable' => (bool) ($modulo['navegable'] ?? false),
                ];
            })
            ->values();
    }

    private function valorLegible(Persona $persona, string $campo, array $definicion): mixed
    {
        $valor = $persona->{$campo};

        if (blank($valor)) {
            return null;
        }

        return match ($definicion['tipo']) {
            'booleano' => $valor ? 'Sí' : 'No',
            'fecha' => $valor->toIso8601String(),
            'opcion' => $definicion['opciones'][$valor] ?? $valor,
            default => (string) $valor,
        };
    }
}
