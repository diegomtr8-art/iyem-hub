<?php

namespace App\Services;

use App\Models\PadronImportacion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Importación de personas desde CSV o XLSX.
 *
 * El flujo es en dos pasos a propósito:
 *
 *   1. Se sube el archivo y se **previsualiza**: se detectan las columnas,
 *      se propone un mapeo y se valida fila por fila sin escribir nada.
 *   2. Solo si quien captura confirma, se escribe.
 *
 * Un lote malo en el padrón central no se limpia con un `undo`: se propaga
 * a los seis módulos que lo consultan. Vale la pena el paso extra.
 */
class ImportadorPadron
{
    /** Cuántas filas se muestran en la vista previa. */
    public const FILAS_DE_MUESTRA = 10;

    /**
     * Campos del padrón que se pueden llenar desde un archivo, con los
     * encabezados que se reconocen automáticamente.
     */
    public const CAMPOS_IMPORTABLES = [
        'nombre_completo' => ['etiqueta' => 'Nombre completo', 'obligatorio' => true, 'alias' => ['nombre', 'nombre completo', 'nombre_completo', 'nombres']],
        'curp' => ['etiqueta' => 'CURP', 'obligatorio' => false, 'alias' => ['curp']],
        'rfc' => ['etiqueta' => 'RFC', 'obligatorio' => false, 'alias' => ['rfc']],
        'email' => ['etiqueta' => 'Correo electrónico', 'obligatorio' => false, 'alias' => ['email', 'correo', 'correo electronico', 'e-mail']],
        'telefono' => ['etiqueta' => 'Teléfono', 'obligatorio' => false, 'alias' => ['telefono', 'teléfono', 'celular', 'tel']],
        'telefono_secundario' => ['etiqueta' => 'Teléfono secundario', 'obligatorio' => false, 'alias' => ['telefono2', 'telefono_secundario']],
        'calle' => ['etiqueta' => 'Calle y número', 'obligatorio' => false, 'alias' => ['calle', 'direccion', 'domicilio']],
        'codigo_postal' => ['etiqueta' => 'Código postal', 'obligatorio' => false, 'alias' => ['cp', 'codigo_postal', 'codigo postal']],
        'localidad' => ['etiqueta' => 'Localidad', 'obligatorio' => false, 'alias' => ['localidad', 'comisaria']],
        'municipio' => ['etiqueta' => 'Municipio', 'obligatorio' => false, 'alias' => ['municipio']],
        'estado' => ['etiqueta' => 'Estado', 'obligatorio' => false, 'alias' => ['estado', 'entidad']],
        'fecha_nacimiento' => ['etiqueta' => 'Fecha de nacimiento', 'obligatorio' => false, 'alias' => ['fecha_nacimiento', 'nacimiento', 'fecha de nacimiento']],
        'sexo' => ['etiqueta' => 'Sexo', 'obligatorio' => false, 'alias' => ['sexo', 'genero', 'género']],
        'nivel_educativo' => ['etiqueta' => 'Nivel educativo', 'obligatorio' => false, 'alias' => ['nivel_educativo', 'escolaridad']],
        'medio_ingreso' => ['etiqueta' => 'Cómo llegó al IYEM', 'obligatorio' => false, 'alias' => ['medio_ingreso', 'origen', 'como llego']],
    ];

    public function __construct(private readonly ResolvedorPersonas $resolvedor) {}

    /**
     * Guarda el archivo y devuelve la vista previa, sin tocar el padrón.
     */
    public function previsualizar(UploadedFile $archivo, User $usuario): array
    {
        $ruta = $archivo->store('importaciones');

        $encabezados = $this->leerEncabezados($ruta);
        $filas = $this->leerFilas($ruta);

        $mapeo = $this->proponerMapeo($encabezados);
        $validadas = $filas->map(fn (array $fila, int $i) => $this->validarFila($fila, $mapeo, $i + 2));

        $importacion = PadronImportacion::create([
            'usuario_id' => $usuario->id,
            'archivo_original' => $archivo->getClientOriginalName(),
            'ruta_archivo' => $ruta,
            'total_filas' => $filas->count(),
            'filas_rechazadas' => $validadas->where('valida', false)->count(),
            'mapeo' => $mapeo,
            'estado' => 'previsualizada',
        ]);

        return [
            'importacion_id' => $importacion->id,
            'encabezados' => $encabezados,
            'mapeo' => $mapeo,
            'campos' => collect(self::CAMPOS_IMPORTABLES)
                ->map(fn (array $campo, string $clave) => [
                    'clave' => $clave,
                    'etiqueta' => $campo['etiqueta'],
                    'obligatorio' => $campo['obligatorio'],
                ])->values()->all(),
            'total_filas' => $filas->count(),
            'validas' => $validadas->where('valida', true)->count(),
            'rechazadas' => $validadas->where('valida', false)->count(),
            'muestra' => $validadas->take(self::FILAS_DE_MUESTRA)->values()->all(),
            'errores_frecuentes' => $this->erroresFrecuentes($validadas),
        ];
    }

    /**
     * Escribe el lote. Cada fila pasa por el resolvedor, así que una
     * importación nunca crea un duplicado de alguien que ya está.
     */
    public function confirmar(PadronImportacion $importacion, array $mapeo, User $usuario): PadronImportacion
    {
        $filas = $this->leerFilas($importacion->ruta_archivo);

        $creadas = 0;
        $actualizadas = 0;
        $rechazos = collect();

        foreach ($filas as $indice => $fila) {
            $numeroDeFila = $indice + 2; // +1 por el encabezado, +1 porque Excel empieza en 1
            $resultado = $this->validarFila($fila, $mapeo, $numeroDeFila);

            if (! $resultado['valida']) {
                $rechazos->push([...$fila, '_fila' => $numeroDeFila, '_motivo' => implode(' | ', $resultado['errores'])]);

                continue;
            }

            try {
                DB::transaction(function () use ($resultado, $usuario, &$creadas, &$actualizadas) {
                    $resuelto = $this->resolvedor->resolver($resultado['datos'], 'importacion');

                    if ($resuelto['creada']) {
                        $creadas++;
                    } else {
                        $actualizadas++;
                    }

                    $resuelto['persona']->marcarAuditoria(
                        campo: '__importacion__',
                        valor_anterior: null,
                        valor_nuevo: $resuelto['creada'] ? 'creada por importación' : 'completada por importación',
                        usuario_id: $usuario->id,
                        modulo: 'importacion'
                    );
                });
            } catch (\Throwable $error) {
                // Una fila que revienta no debe tumbar el lote entero: se
                // manda a rechazos con el motivo y la importación sigue.
                $rechazos->push([...$fila, '_fila' => $numeroDeFila, '_motivo' => $error->getMessage()]);
            }
        }

        $rutaRechazos = $rechazos->isNotEmpty()
            ? $this->escribirRechazos($rechazos, $importacion)
            : null;

        $importacion->update([
            'mapeo' => $mapeo,
            'filas_creadas' => $creadas,
            'filas_actualizadas' => $actualizadas,
            'filas_rechazadas' => $rechazos->count(),
            'ruta_rechazos' => $rutaRechazos,
            'estado' => 'confirmada',
            'mensaje' => "{$creadas} creadas, {$actualizadas} completadas, {$rechazos->count()} rechazadas.",
        ]);

        return $importacion->fresh();
    }

    /**
     * Valida una fila sin escribirla.
     *
     * @return array{fila: int, valida: bool, datos: array, errores: array, crudo: array}
     */
    public function validarFila(array $fila, array $mapeo, int $numeroDeFila): array
    {
        $datos = [];

        foreach ($mapeo as $campo => $encabezado) {
            if ($encabezado === null || $encabezado === '') {
                continue;
            }

            $valor = $fila[$encabezado] ?? null;
            $datos[$campo] = is_string($valor) ? trim($valor) : $valor;
        }

        // Normalizaciones que evitan rechazos por formato de captura.
        if (filled($datos['curp'] ?? null)) {
            $datos['curp'] = Str::upper(preg_replace('/\s+/', '', $datos['curp']));
        }
        if (filled($datos['rfc'] ?? null)) {
            $datos['rfc'] = Str::upper(preg_replace('/[\s-]+/', '', $datos['rfc']));
        }
        if (filled($datos['email'] ?? null)) {
            $datos['email'] = Str::lower($datos['email']);
        }
        foreach (['telefono', 'telefono_secundario'] as $campo) {
            if (filled($datos[$campo] ?? null)) {
                $datos[$campo] = preg_replace('/\D+/', '', $datos[$campo]);
            }
        }

        $validador = Validator::make($datos, [
            'nombre_completo' => ['required', 'string', 'max:255'],
            'curp' => ['nullable', 'string', 'size:18', 'regex:'.config('padron.reglas.curp')],
            'rfc' => ['nullable', 'string', 'between:12,13', 'regex:'.config('padron.reglas.rfc')],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'telefono' => ['nullable', 'string', 'regex:'.config('padron.reglas.telefono')],
            'telefono_secundario' => ['nullable', 'string', 'regex:'.config('padron.reglas.telefono')],
            'codigo_postal' => ['nullable', 'string', 'regex:'.config('padron.reglas.codigo_postal')],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
            'sexo' => ['nullable', 'in:M,F,Otro'],
        ], [
            'nombre_completo.required' => 'Falta el nombre.',
            'curp.size' => 'La CURP debe tener 18 caracteres.',
            'curp.regex' => 'La CURP no tiene la estructura oficial.',
            'rfc.regex' => 'El RFC no tiene la estructura oficial.',
            'telefono.regex' => 'El teléfono debe tener 10 dígitos.',
            'telefono_secundario.regex' => 'El teléfono secundario debe tener 10 dígitos.',
            'codigo_postal.regex' => 'El código postal debe tener 5 dígitos.',
            'email.email' => 'El correo no es válido.',
        ]);

        return [
            'fila' => $numeroDeFila,
            'valida' => ! $validador->fails(),
            'datos' => array_filter($datos, fn ($v) => $v !== null && $v !== ''),
            'errores' => $validador->errors()->all(),
            'crudo' => $fila,
        ];
    }

    /**
     * Empareja los encabezados del archivo con los campos del padrón.
     *
     * Es una propuesta, no una decisión: la pantalla la muestra para que
     * quien captura la corrija antes de confirmar.
     *
     * @return array<string, string|null>
     */
    public function proponerMapeo(array $encabezados): array
    {
        $normalizar = fn (string $texto) => Str::lower(Str::ascii(trim($texto)));
        $normalizados = collect($encabezados)->mapWithKeys(
            fn (string $encabezado) => [$normalizar($encabezado) => $encabezado]
        );

        $mapeo = [];

        foreach (self::CAMPOS_IMPORTABLES as $campo => $definicion) {
            $mapeo[$campo] = null;

            foreach ($definicion['alias'] as $alias) {
                if ($normalizados->has($normalizar($alias))) {
                    $mapeo[$campo] = $normalizados->get($normalizar($alias));
                    break;
                }
            }
        }

        return $mapeo;
    }

    /**
     * Encabezados tal como vienen en el archivo.
     *
     * Se leen de la primera fila de la hoja y no con `HeadingRowImport`,
     * que los normaliza a snake_case en minúsculas: "Teléfono" se volvía
     * "telefono" y dejaba de coincidir con la clave que usa `leerFilas`
     * para armar cada renglón, así que el mapeo apuntaba a columnas
     * inexistentes y todas las filas se rechazaban por "falta el nombre".
     *
     * Como efecto secundario, la pantalla muestra los nombres reales de
     * las columnas, que es lo que quien captura reconoce.
     *
     * @return array<int, string>
     */
    private function leerEncabezados(string $ruta): array
    {
        $hojas = Excel::toArray(new class {}, Storage::path($ruta));
        $primeraFila = collect($hojas[0] ?? [])->first() ?? [];

        return collect($primeraFila)
            ->filter(fn ($valor) => filled($valor))
            ->map(fn ($valor) => (string) $valor)
            ->values()
            ->all();
    }

    private function leerFilas(string $ruta): Collection
    {
        $hojas = Excel::toArray(new class {}, Storage::path($ruta));
        $filas = collect($hojas[0] ?? []);

        if ($filas->isEmpty()) {
            return collect();
        }

        $encabezados = collect($filas->first())->map(fn ($v) => (string) $v)->all();

        return $filas->skip(1)
            ->map(function (array $fila) use ($encabezados) {
                $asociativa = [];

                foreach ($encabezados as $i => $encabezado) {
                    if (filled($encabezado)) {
                        $asociativa[$encabezado] = $fila[$i] ?? null;
                    }
                }

                return $asociativa;
            })
            // Las hojas de cálculo suelen traer filas vacías al final.
            ->filter(fn (array $fila) => collect($fila)->filter(fn ($v) => filled($v))->isNotEmpty())
            ->values();
    }

    /**
     * Archivo con las filas que no entraron y el motivo de cada una, para
     * que quien capturó lo corrija y vuelva a intentarlo.
     */
    private function escribirRechazos(Collection $rechazos, PadronImportacion $importacion): string
    {
        $ruta = "importaciones/rechazos-{$importacion->id}.csv";
        $columnas = array_keys($rechazos->first());

        $lineas = [implode(',', array_map(fn ($c) => '"'.$c.'"', $columnas))];

        foreach ($rechazos as $fila) {
            $lineas[] = implode(',', array_map(
                fn ($columna) => '"'.str_replace('"', '""', (string) ($fila[$columna] ?? '')).'"',
                $columnas
            ));
        }

        Storage::put($ruta, "\xEF\xBB\xBF".implode("\n", $lineas));

        return $ruta;
    }

    /**
     * Los errores más repetidos del lote, para que la vista previa diga qué
     * hay que arreglar en lugar de listar mil mensajes iguales.
     */
    private function erroresFrecuentes(Collection $validadas): array
    {
        return $validadas
            ->where('valida', false)
            ->flatMap(fn (array $resultado) => $resultado['errores'])
            ->countBy()
            ->sortDesc()
            ->take(5)
            ->map(fn (int $total, string $mensaje) => ['mensaje' => $mensaje, 'total' => $total])
            ->values()
            ->all();
    }
}
