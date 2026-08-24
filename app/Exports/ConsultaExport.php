<?php

namespace App\Exports;

use App\Services\Consultas\Consulta;
use App\Services\Consultas\FiltrosConsulta;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exportación a XLSX del resultado de una consulta.
 *
 * Sale con el encabezado guinda institucional y en Arial, la misma
 * identidad que el resto de la plataforma: estos archivos terminan en
 * juntas de dirección, no solo en el escritorio de quien los descarga.
 */
class ConsultaExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;

    public function __construct(
        private readonly Consulta $consulta,
        private readonly FiltrosConsulta $filtros,
    ) {}

    public function descargar(string $nombre): StreamedResponse
    {
        return $this->download($nombre);
    }

    public function collection()
    {
        return $this->consulta->filasParaExportar($this->filtros);
    }

    public function headings(): array
    {
        return [
            [$this->consulta->titulo()],
            ['Periodo: '.$this->filtros->periodoLegible()],
            ['Generado: '.now()->translatedFormat('d \d\e F \d\e Y, H:i').' — IYEM Yucatán'],
            [],
            array_values($this->consulta->columnas()),
        ];
    }

    public function map($fila): array
    {
        return array_map(
            fn (string $columna) => $fila[$columna] ?? '',
            array_keys($this->consulta->columnas())
        );
    }

    public function title(): string
    {
        // Excel no acepta más de 31 caracteres ni ciertos símbolos en el
        // nombre de la hoja, y falla en silencio si se pasa.
        return mb_substr(preg_replace('/[\\\/\*\?\:\[\]]/', '', $this->consulta->titulo()), 0, 31);
    }

    public function columnFormats(): array
    {
        return [];
    }

    public function styles(Worksheet $hoja): array
    {
        $ultimaColumna = $hoja->getHighestColumn();

        // Título del reporte.
        $hoja->mergeCells("A1:{$ultimaColumna}1");
        $hoja->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'name' => 'Arial', 'color' => ['rgb' => '691C32']],
        ]);

        $hoja->getStyle('A2:A3')->applyFromArray([
            'font' => ['size' => 9, 'name' => 'Arial', 'color' => ['rgb' => '6B7280']],
        ]);

        // Fila 5: encabezados de la tabla, en guinda con texto blanco.
        $hoja->getStyle("A5:{$ultimaColumna}5")->applyFromArray([
            'font' => ['bold' => true, 'name' => 'Arial', 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '691C32'],
            ],
        ]);

        $hoja->getStyle('A6:'.$ultimaColumna.$hoja->getHighestRow())
            ->applyFromArray(['font' => ['name' => 'Arial', 'size' => 10]]);

        // Los encabezados quedan fijos al desplazarse: un reporte de miles
        // de filas es ilegible sin eso.
        $hoja->freezePane('A6');

        return [];
    }
}
