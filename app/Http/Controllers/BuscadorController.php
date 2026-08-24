<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Services\CatalogoModulos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Buscador global del hub (⌘K).
 *
 * Responde JSON a la paleta de comandos. Devuelve personas del padrón con
 * un resumen de sus vínculos, para que quien atiende en ventanilla vea de
 * un golpe si esa persona ya pasó por otros módulos.
 */
class BuscadorController extends Controller
{
    /** Debajo de tres caracteres la búsqueda devolvería medio padrón. */
    private const MINIMO_DE_CARACTERES = 3;

    private const MAXIMO_DE_RESULTADOS = 20;

    public function __construct(private readonly CatalogoModulos $catalogo) {}

    public function __invoke(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $termino = trim($datos['q'] ?? '');

        if (Str::length($termino) < self::MINIMO_DE_CARACTERES) {
            return response()->json([
                'resultados' => [],
                'total' => 0,
                'minimo' => self::MINIMO_DE_CARACTERES,
            ]);
        }

        $soloDigitos = preg_replace('/\D+/', '', $termino);

        $consulta = Persona::query()
            ->where(function ($query) use ($termino, $soloDigitos) {
                $query->where('nombre_completo', 'like', "%{$termino}%")
                    ->orWhere('email', 'like', "%{$termino}%")
                    ->orWhere('curp', 'like', Str::upper($termino).'%')
                    ->orWhere('rfc', 'like', Str::upper($termino).'%');

                // Solo se busca por teléfono si el término trae dígitos, y con
                // al menos cuatro: con dos, cualquier número de Mérida encaja.
                if (Str::length($soloDigitos) >= 4) {
                    $query->orWhere('telefono', 'like', "%{$soloDigitos}%")
                        ->orWhere('telefono_secundario', 'like', "%{$soloDigitos}%");
                }
            })
            ->withCount([
                'creaSolicitudes',
                'impulstateInscripciones',
                'nodicoMembresias',
                'herenciaVivaClientes',
                'juridicoAsesorias',
            ]);

        $total = (clone $consulta)->count();

        $personas = $consulta
            ->orderBy('nombre_completo')
            ->limit(self::MAXIMO_DE_RESULTADOS)
            ->get();

        return response()->json([
            'resultados' => $personas->map(fn (Persona $persona) => [
                'id' => $persona->id,
                'nombre_completo' => $persona->nombre_completo,
                'email' => $persona->email,
                'telefono' => $persona->telefono,
                'curp' => $persona->curp,
                'municipio' => $persona->municipio,
                'estado_persona' => $persona->estado_persona,
                'demo' => $persona->demo,
                'url' => route('padron.show', $persona->id),
                'modulos' => $this->modulosDe($persona),
            ]),
            'total' => $total,
            'truncado' => $total > self::MAXIMO_DE_RESULTADOS,
            'maximo' => self::MAXIMO_DE_RESULTADOS,
        ]);
    }

    /**
     * Insignias de los módulos donde la persona tiene registros.
     */
    private function modulosDe(Persona $persona): array
    {
        $conteos = [
            'crea' => $persona->crea_solicitudes_count,
            'impulsate' => $persona->impulstate_inscripciones_count,
            'nodico' => $persona->nodico_membresias_count,
            'herenciaviva' => $persona->herencia_viva_clientes_count,
            'juridico' => $persona->juridico_asesorias_count,
        ];

        return collect($conteos)
            ->filter(fn (int $total) => $total > 0)
            ->map(fn (int $total, string $slug) => [
                'slug' => $slug,
                'nombre' => $this->catalogo->encontrar($slug)['nombre'] ?? $slug,
                'icono' => $this->catalogo->encontrar($slug)['icono'] ?? 'squares-2x2',
                'total' => $total,
            ])
            ->values()
            ->all();
    }
}
