<?php

namespace App\Exports;

use App\Models\Persona;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exportación del padrón a XLSX, respetando los filtros aplicados.
 *
 * Implementa `FromQuery` y no `FromCollection`: así el paquete recorre el
 * resultado por lotes en vez de traer el padrón entero a memoria. Con
 * decenas de miles de personas, la diferencia es entre un archivo y un
 * proceso de PHP caído.
 *
 * Los valores salen del modelo, así que el enmascarado del rol Tester sigue
 * aplicando. Aun así, el rol Tester no tiene `exportar-padron`: es una
 * segunda barrera, no la principal.
 */
class PadronExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;

    public const COLUMNAS = [
        'id' => 'ID',
        'nombre_completo' => 'Nombre completo',
        'curp' => 'CURP',
        'rfc' => 'RFC',
        'email' => 'Correo',
        'telefono' => 'Teléfono',
        'calle' => 'Calle y número',
        'codigo_postal' => 'Código postal',
        'localidad' => 'Localidad',
        'municipio' => 'Municipio',
        'estado' => 'Estado',
        'fecha_nacimiento' => 'Fecha de nacimiento',
        'edad' => 'Edad',
        'sexo' => 'Sexo',
        'nivel_educativo' => 'Nivel educativo',
        'habla_maya' => 'Habla maya',
        'tipo_persona' => 'Tipo de persona',
        'estado_persona' => 'Estado de la persona',
        'medio_ingreso' => 'Cómo llegó al IYEM',
        'creado_por_modulo' => 'Módulo de origen',
        'etiquetas' => 'Etiquetas',
        'created_at' => 'Alta en el padrón',
    ];

    public function __construct(private readonly array $filtros = []) {}

    public function descargar(string $nombre): StreamedResponse
    {
        return $this->download($nombre);
    }

    public function query(): Builder
    {
        return self::consulta($this->filtros);
    }

    /**
     * La misma consulta que usa la exportación CSV, para que ambos formatos
     * no puedan divergir en lo que devuelven.
     */
    public static function consulta(array $filtros): Builder
    {
        return Persona::query()
            ->when($filtros['busqueda'] ?? null, fn ($q, $v) => $q->where(function ($q) use ($v) {
                $q->where('nombre_completo', 'like', "%{$v}%")
                    ->orWhere('email', 'like', "%{$v}%")
                    ->orWhere('municipio', 'like', "%{$v}%");
            }))
            ->when($filtros['estado_persona'] ?? null, fn ($q, $v) => $q->where('estado_persona', $v))
            ->when($filtros['municipio'] ?? null, fn ($q, $v) => $q->porMunicipio($v))
            ->when($filtros['etiqueta'] ?? null, fn ($q, $v) => $q->porEtiqueta($v))
            ->with('etiquetas')
            ->orderBy('id');
    }

    public static function valor(Persona $persona, string $campo): string
    {
        return match ($campo) {
            'etiquetas' => $persona->etiquetas->pluck('etiqueta')->implode(', '),
            'habla_maya' => $persona->habla_maya ? 'Sí' : 'No',
            'fecha_nacimiento' => $persona->fecha_nacimiento?->toDateString() ?? '',
            'created_at' => $persona->created_at?->toDateTimeString() ?? '',
            default => (string) ($persona->{$campo} ?? ''),
        };
    }

    public function headings(): array
    {
        return [
            ['Padrón Central — IYEM Yucatán'],
            ['Generado: '.now()->translatedFormat('d \d\e F \d\e Y, H:i')],
            [$this->descripcionDeFiltros()],
            [],
            array_values(self::COLUMNAS),
        ];
    }

    public function map($persona): array
    {
        return array_map(
            fn (string $campo) => self::valor($persona, $campo),
            array_keys(self::COLUMNAS)
        );
    }

    public function title(): string
    {
        return 'Padrón';
    }

    public function styles(Worksheet $hoja): array
    {
        $ultima = $hoja->getHighestColumn();

        $hoja->mergeCells("A1:{$ultima}1");
        $hoja->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'name' => 'Arial', 'color' => ['rgb' => '691C32']],
        ]);

        $hoja->getStyle('A2:A3')->applyFromArray([
            'font' => ['size' => 9, 'name' => 'Arial', 'color' => ['rgb' => '6B7280']],
        ]);

        $hoja->getStyle("A5:{$ultima}5")->applyFromArray([
            'font' => ['bold' => true, 'name' => 'Arial', 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '691C32']],
        ]);

        $hoja->freezePane('A6');

        return [];
    }

    private function descripcionDeFiltros(): string
    {
        $activos = collect($this->filtros)->filter(fn ($valor) => filled($valor));

        if ($activos->isEmpty()) {
            return 'Filtros: ninguno (padrón completo)';
        }

        return 'Filtros: '.$activos
            ->map(fn ($valor, $clave) => str_replace('_', ' ', $clave).' = '.$valor)
            ->implode(' · ');
    }
}
