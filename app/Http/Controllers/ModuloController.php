<?php

namespace App\Http\Controllers;

use App\Models\Acceso;
use App\Services\CatalogoModulos;
use App\Services\Modulos\AdaptadorDeModulo;
use App\Services\Modulos\Falla;
use App\Services\Modulos\Periodo;
use App\Services\Modulos\RegistroDeAdaptadores;
use App\Services\Modulos\Seccion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tableros de módulos externos dentro del ERP. Solo lectura.
 *
 * La página se pinta sin esperar a la API del módulo: `tablero` manda el
 * marco y los datos se piden después a `datos`, que responde JSON. Es la
 * misma lección de `/dashboard/salud`: consultar otro dominio durante el
 * render deja la página en blanco cada vez que ese dominio no contesta.
 *
 * Toda consulta queda en `accesos`. El módulo registra las suyas a nombre de
 * la cuenta de servicio del ERP; quién preguntó de verdad solo queda aquí.
 */
class ModuloController extends Controller
{
    public function __construct(
        private readonly CatalogoModulos $catalogo,
        private readonly RegistroDeAdaptadores $registro,
    ) {}

    public function tablero(Request $request, string $slug): Response
    {
        [$modulo, $adaptador] = $this->resolver($request, $slug);
        $periodo = Periodo::desdeConsulta($request->query());

        // Cambiar de periodo recarga solo `periodo` (visita parcial): eso no
        // es entrar otra vez al tablero.
        if (! $request->header('X-Inertia-Partial-Data')) {
            $this->registrarAcceso($request, $slug, 'tablero');
        }

        $puedeVerPersonas = $request->user()->can('ver-modulo-datos-personales');

        return Inertia::render('Modulos/Tablero', [
            'modulo' => $this->marco($modulo),
            'periodo' => $periodo->aArreglo(),
            'periodos' => Periodo::opciones(),
            'informes' => $this->informes($adaptador, $puedeVerPersonas),
            'puedeVerDatosPersonales' => $puedeVerPersonas && $adaptador->ofreceDatosPersonales(),
        ]);
    }

    public function datos(Request $request, string $slug): JsonResponse
    {
        [, $adaptador] = $this->resolver($request, $slug);
        $periodo = Periodo::desdeConsulta($request->query());

        return response()->json([
            'periodo' => $periodo->aArreglo(),
            'resumen' => $adaptador->resumen($periodo)->aArreglo(),
            'secciones' => array_map(fn (Seccion $s) => $s->aArreglo(), $adaptador->secciones($periodo)),
        ]);
    }

    public function personas(Request $request, string $slug): Response
    {
        [$modulo, $adaptador] = $this->resolver($request, $slug);
        abort_unless($adaptador->ofreceDatosPersonales(), 404);
        $periodo = Periodo::desdeConsulta($request->query());

        return Inertia::render('Modulos/DatosPersonales', [
            'modulo' => $this->marco($modulo),
            'periodo' => $periodo->aArreglo(),
            'periodos' => Periodo::opciones(),
        ]);
    }

    /**
     * Las listas con nombres y contacto. Se registra aquí y no en `personas`
     * porque es aquí donde los datos salen hacia el navegador.
     */
    public function personasDatos(Request $request, string $slug): JsonResponse
    {
        [, $adaptador] = $this->resolver($request, $slug);
        abort_unless($adaptador->ofreceDatosPersonales(), 404);
        $periodo = Periodo::desdeConsulta($request->query());

        $secciones = $adaptador->datosPersonales($periodo);

        $this->registrarAcceso(
            $request,
            $slug,
            'datos-personales: '.implode(', ', array_map(fn (Seccion $s) => $s->clave, $secciones)),
        );

        return response()->json([
            'periodo' => $periodo->aArreglo(),
            'secciones' => array_map(fn (Seccion $s) => $s->aArreglo(), $secciones),
        ]);
    }

    public function informe(Request $request, string $slug, string $informe): StreamedResponse|JsonResponse
    {
        [, $adaptador] = $this->resolver($request, $slug);
        $disponible = $adaptador->informesExportables()[$informe] ?? null;

        abort_unless($disponible, 404);
        abort_if($disponible['personal'] && ! $request->user()->can('ver-modulo-datos-personales'), 403);

        $periodo = Periodo::desdeConsulta($request->query());
        $this->registrarAcceso($request, $slug, "informe: {$informe}.csv");

        $resultado = $adaptador->exportar($informe, $periodo);

        // Una descarga fallida no puede bajar un CSV vacío: se vería como un
        // periodo sin movimientos.
        if ($resultado instanceof Falla) {
            return response()->json(['falla' => $resultado->aArreglo()], 502);
        }

        return $resultado;
    }

    /**
     * @return array{0: array, 1: AdaptadorDeModulo}
     */
    private function resolver(Request $request, string $slug): array
    {
        $modulo = $this->catalogo->encontrar($slug);
        $adaptador = $modulo ? $this->registro->paraModulo($slug) : null;

        abort_unless($modulo && $adaptador, 404);
        // Los dos permisos: el del tablero lo pone la ruta; el del módulo, aquí.
        abort_unless($request->user()->can("ver-{$slug}"), 403);

        return [$modulo, $adaptador];
    }

    /** Lo que la página necesita del módulo. Nada de `api_base` ni tokens. */
    private function marco(array $modulo): array
    {
        return [
            'slug' => $modulo['slug'],
            'nombre' => $modulo['nombre'],
            'descripcion' => $modulo['descripcion'],
            'icono' => $modulo['icono'],
            'estado' => $modulo['estado'],
            // Pasa por `dashboard.acceder` para que la salida también quede
            // en la bitácora. null si el sitio todavía no se puede visitar.
            'url_sitio' => $modulo['navegable'] && $modulo['externo'] ? $modulo['url'] : null,
            'entorno_de_prueba' => $modulo['entorno_de_prueba'],
        ];
    }

    private function informes(AdaptadorDeModulo $adaptador, bool $puedeVerPersonas): array
    {
        return collect($adaptador->informesExportables())
            ->filter(fn (array $informe) => $puedeVerPersonas || ! $informe['personal'])
            ->map(fn (array $informe, string $clave) => ['clave' => $clave, 'etiqueta' => $informe['etiqueta'], 'personal' => $informe['personal']])
            ->values()
            ->all();
    }

    private function registrarAcceso(Request $request, string $slug, string $detalle): void
    {
        Acceso::create([
            'user_id' => $request->user()->id,
            'modulo' => $slug,
            'detalle' => mb_substr($detalle, 0, 120),
            'ip_address' => $request->ip(),
            'accedido_at' => now(),
        ]);
    }
}
