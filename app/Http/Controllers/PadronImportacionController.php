<?php

namespace App\Http\Controllers;

use App\Exports\PadronExport;
use App\Models\PadronImportacion;
use App\Services\ImportadorPadron;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Importación y exportación del padrón.
 *
 * La importación va en dos pasos —previsualizar y confirmar— porque un lote
 * mal cargado en el padrón central no se deshace: se propaga a los seis
 * módulos que lo consultan.
 */
class PadronImportacionController extends Controller
{
    public function __construct(private readonly ImportadorPadron $importador) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Padron/Importar', [
            'campos' => collect(ImportadorPadron::CAMPOS_IMPORTABLES)
                ->map(fn (array $campo, string $clave) => [
                    'clave' => $clave,
                    'etiqueta' => $campo['etiqueta'],
                    'obligatorio' => $campo['obligatorio'],
                    'alias' => $campo['alias'],
                ])->values()->all(),
            'historial' => $this->historial(),
        ]);
    }

    /**
     * Sube el archivo y devuelve la vista previa. No escribe en el padrón.
     */
    public function previsualizar(Request $request): RedirectResponse
    {
        $request->validate([
            'archivo' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
        ], [
            'archivo.mimes' => 'El archivo debe ser CSV o XLSX.',
            'archivo.max' => 'El archivo no puede pasar de 10 MB.',
        ]);

        $vistaPrevia = $this->importador->previsualizar($request->file('archivo'), $request->user());

        return back()->with('vistaPrevia', $vistaPrevia);
    }

    /**
     * Escribe el lote con el mapeo que confirmó quien captura.
     */
    public function confirmar(Request $request, PadronImportacion $importacion): RedirectResponse
    {
        abort_unless($importacion->estado === 'previsualizada', 409, 'Este lote ya fue procesado.');
        abort_unless($importacion->usuario_id === $request->user()->id, 403);

        $request->validate([
            'mapeo' => ['required', 'array'],
            'mapeo.nombre_completo' => ['required', 'string'],
            'mapeo.*' => ['nullable', 'string', 'max:255'],
        ], [
            'mapeo.nombre_completo.required' => 'Hay que indicar qué columna trae el nombre.',
        ]);

        /*
         * El mapeo se lee de `input()` y no del resultado de `validate()`.
         *
         * `validate()` devuelve únicamente las claves que se declararon como
         * regla: con `mapeo.nombre_completo` en la lista, el arreglo volvía
         * reducido a ese solo campo y la importación entraba sin CURP, sin
         * teléfono y sin municipio, en silencio.
         *
         * Además se acota a los campos importables conocidos, para que una
         * petición armada a mano no pueda mapear columnas arbitrarias.
         */
        $mapeo = collect($request->input('mapeo', []))
            ->only(array_keys(ImportadorPadron::CAMPOS_IMPORTABLES))
            ->filter(fn ($encabezado) => filled($encabezado))
            ->all();

        $procesada = $this->importador->confirmar($importacion, $mapeo, $request->user());

        return redirect()
            ->route('padron.importar.index')
            ->with('flash', ['success' => "Importación terminada: {$procesada->mensaje}"]);
    }

    /**
     * Descarga las filas que no entraron, con el motivo de cada una.
     */
    public function rechazos(Request $request, PadronImportacion $importacion): StreamedResponse
    {
        abort_unless($importacion->tieneRechazos(), 404, 'Este lote no tiene filas rechazadas.');
        abort_unless(Storage::exists($importacion->ruta_rechazos), 404);

        return Storage::download(
            $importacion->ruta_rechazos,
            'rechazos-'.pathinfo($importacion->archivo_original, PATHINFO_FILENAME).'.csv'
        );
    }

    /**
     * Exporta el padrón respetando los filtros que traiga la petición.
     */
    public function exportar(Request $request): StreamedResponse
    {
        $formato = $request->string('formato')->toString() ?: 'xlsx';
        abort_unless(in_array($formato, ['csv', 'xlsx'], true), 422, 'Formato no soportado.');

        $filtros = [
            'busqueda' => $request->string('busqueda')->toString(),
            'estado_persona' => $request->string('estado_persona')->toString(),
            'municipio' => $request->string('municipio')->toString(),
            'etiqueta' => $request->string('etiqueta')->toString(),
        ];

        $nombre = 'padron-'.now()->format('Ymd-Hi');

        if ($formato === 'xlsx') {
            return app(PadronExport::class, ['filtros' => $filtros])->descargar("{$nombre}.xlsx");
        }

        return $this->exportarCsv($filtros, "{$nombre}.csv");
    }

    private function exportarCsv(array $filtros, string $nombre): StreamedResponse
    {
        $columnas = PadronExport::COLUMNAS;

        return response()->streamDownload(function () use ($filtros, $columnas) {
            $salida = fopen('php://output', 'w');

            // BOM de UTF-8: sin él Excel en Windows destroza los acentos.
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, array_values($columnas));

            // `chunk` y no `get`: exportar el padrón completo de golpe
            // cargaría decenas de miles de modelos en memoria.
            PadronExport::consulta($filtros)->chunk(500, function ($personas) use ($salida, $columnas) {
                foreach ($personas as $persona) {
                    fputcsv($salida, array_map(
                        fn (string $campo) => PadronExport::valor($persona, $campo),
                        array_keys($columnas)
                    ));
                }
            });

            fclose($salida);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function historial()
    {
        return PadronImportacion::query()
            ->with('usuario:id,name,apellido')
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn (PadronImportacion $lote) => [
                'id' => $lote->id,
                'archivo' => $lote->archivo_original,
                'estado' => $lote->estado,
                'total_filas' => $lote->total_filas,
                'creadas' => $lote->filas_creadas,
                'actualizadas' => $lote->filas_actualizadas,
                'rechazadas' => $lote->filas_rechazadas,
                'tiene_rechazos' => $lote->tieneRechazos(),
                'mensaje' => $lote->mensaje,
                'usuario' => $lote->usuario
                    ? trim("{$lote->usuario->name} {$lote->usuario->apellido}")
                    : 'Usuario eliminado',
                'fecha' => $lote->created_at?->toIso8601String(),
            ]);
    }
}
