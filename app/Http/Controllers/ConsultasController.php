<?php

namespace App\Http\Controllers;

use App\Exports\ConsultaExport;
use App\Models\Persona;
use App\Services\Consultas\Consulta;
use App\Services\Consultas\FiltrosConsulta;
use App\Services\Consultas\RegistroDeConsultas;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Consultas 360°: donde el instituto le pregunta cosas a su propia
 * información.
 *
 * Todos los filtros viven en la query string, así que cualquier resultado
 * se comparte copiando la URL.
 */
class ConsultasController extends Controller
{
    public function __construct(private readonly RegistroDeConsultas $registro) {}

    public function index(Request $request): Response
    {
        $consulta = $this->registro->encontrar($request->string('consulta')->toString());

        if (! $consulta) {
            return Inertia::render('Consultas/Index', [
                'catalogo' => $this->registro->catalogo(),
                'municipios' => $this->municipios(),
            ]);
        }

        $filtros = FiltrosConsulta::desdePeticion($request);

        return Inertia::render('Consultas/Detalle', [
            'catalogo' => $this->registro->catalogo(),
            'municipios' => $this->municipios(),
            'consulta' => [
                'clave' => $consulta->clave(),
                'titulo' => $consulta->titulo(),
                'descripcion' => $consulta->descripcion(),
                'icono' => $consulta->icono(),
                'controles' => $consulta->controles(),
                'columnas' => $consulta->columnas(),
            ],
            'filtros' => $filtros->aQueryString(),
            'resumen' => $consulta->resumen($filtros),
            'tabla' => $consulta->tabla($filtros),
            'grafica' => $consulta->grafica($filtros),
            'puedeExportar' => $request->user()->can('exportar-padron'),
        ]);
    }

    /**
     * Exporta el resultado completo, no solo la página visible.
     *
     * Se envía como flujo (`StreamedResponse`): una consulta sobre el
     * padrón entero puede pasar de las decenas de miles de filas, y armar
     * el archivo en memoria antes de mandarlo tumbaría al proceso de PHP.
     */
    public function exportar(Request $request, string $clave): StreamedResponse
    {
        abort_unless($request->user()->can('exportar-padron'), 403);

        $consulta = $this->registro->encontrar($clave);
        abort_unless($consulta, 404);

        $filtros = FiltrosConsulta::desdePeticion($request);
        $formato = $request->string('formato')->toString() ?: 'csv';

        abort_unless(in_array($formato, ['csv', 'xlsx'], true), 422, 'Formato no soportado.');

        $nombre = $consulta->clave().'-'.now()->format('Ymd-Hi');

        return $formato === 'xlsx'
            ? $this->exportarXlsx($consulta, $filtros, $nombre)
            : $this->exportarCsv($consulta, $filtros, $nombre);
    }

    private function exportarCsv(Consulta $consulta, FiltrosConsulta $filtros, string $nombre): StreamedResponse
    {
        $columnas = $consulta->columnas();
        $filas = $consulta->filasParaExportar($filtros);

        return response()->streamDownload(function () use ($columnas, $filas, $consulta, $filtros) {
            $salida = fopen('php://output', 'w');

            // BOM de UTF-8: sin él, Excel en Windows abre "Mérida" como
            // "MÃ©rida", que es exactamente donde se usa este archivo.
            fwrite($salida, "\xEF\xBB\xBF");

            fputcsv($salida, [$consulta->titulo()]);
            fputcsv($salida, ['Periodo', $filtros->periodoLegible()]);
            fputcsv($salida, ['Generado', now()->translatedFormat('d \d\e F \d\e Y, H:i')]);
            fputcsv($salida, []);

            fputcsv($salida, array_values($columnas));

            foreach ($filas as $fila) {
                fputcsv($salida, array_map(
                    fn (string $columna) => $fila[$columna] ?? '',
                    array_keys($columnas)
                ));
            }

            fclose($salida);
        }, "{$nombre}.csv", [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function exportarXlsx(Consulta $consulta, FiltrosConsulta $filtros, string $nombre): StreamedResponse
    {
        $exportador = app(ConsultaExport::class, [
            'consulta' => $consulta,
            'filtros' => $filtros,
        ]);

        return $exportador->descargar("{$nombre}.xlsx");
    }

    /**
     * Municipios que realmente aparecen en el padrón, más los del catálogo.
     * Filtrar por un municipio donde no hay nadie no sirve de nada, pero sí
     * sirve verlo en cobertura territorial.
     */
    private function municipios(): array
    {
        return Persona::query()
            ->whereNotNull('municipio')
            ->distinct()
            ->orderBy('municipio')
            ->pluck('municipio')
            ->merge(array_keys(config('municipios_yucatan')))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
