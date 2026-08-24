<?php

namespace App\Console\Commands;

use App\Services\DetectorDuplicados;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Reporte de personas duplicadas en el padrón.
 *
 * A propósito **no fusiona nada**: juntar dos expedientes es una decisión
 * que toma una persona con contexto, no un comando programado. Este comando
 * levanta la lista y la deja lista en `/padron/duplicados`.
 */
class DetectarDuplicados extends Command
{
    protected $signature = 'padron:duplicados
                            {--sin-similitud : Solo criterios exactos (CURP, RFC, correo, teléfono)}
                            {--csv= : Ruta relativa dentro de storage/app donde guardar el reporte}
                            {--limite=25 : Cuántos grupos mostrar en pantalla}';

    protected $description = 'Detecta personas duplicadas en el padrón y genera un reporte';

    public function handle(DetectorDuplicados $detector): int
    {
        $this->info('Revisando el padrón...');

        $grupos = $detector->detectar(incluirSimilitud: ! $this->option('sin-similitud'));
        $resumen = $detector->resumen($grupos);

        if ($grupos->isEmpty()) {
            $this->newLine();
            $this->info('No se encontraron duplicados. El padrón está limpio.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->warn("Se encontraron {$resumen['grupos']} grupos de posibles duplicados, ".
            "con {$resumen['personas_involucradas']} personas involucradas.");
        $this->newLine();

        $this->table(
            ['Confianza', 'Grupos'],
            collect($resumen['por_confianza'])
                ->map(fn (int $total, string $confianza) => [
                    match ($confianza) {
                        'certeza' => 'Certeza (misma CURP)',
                        'alta' => 'Alta (RFC o correo)',
                        'media' => 'Media (teléfono)',
                        default => 'Sospecha (nombre parecido)',
                    },
                    $total,
                ])
                ->values()
                ->all()
        );

        $limite = (int) $this->option('limite');

        $this->newLine();
        $this->line("Primeros {$limite} grupos:");
        $this->table(
            ['Criterio', 'Valor', 'IDs', 'Nombres'],
            $grupos->take($limite)->map(fn (array $grupo) => [
                $grupo['etiqueta'],
                $grupo['valor'] ?? '—',
                $grupo['personas']->pluck('id')->implode(', '),
                $grupo['personas']->pluck('nombre_completo')->implode(' | '),
            ])->all()
        );

        if ($grupos->count() > $limite) {
            $this->comment('… y '.($grupos->count() - $limite).' grupos más. Usa --csv para el reporte completo.');
        }

        if ($ruta = $this->option('csv')) {
            $this->escribirCsv($grupos, $ruta);
        }

        $this->newLine();
        $this->comment('Este comando no fusiona nada. La fusión se decide en /padron/duplicados,');
        $this->comment('donde queda auditada y se puede revertir durante 30 días.');

        return self::SUCCESS;
    }

    private function escribirCsv($grupos, string $ruta): void
    {
        $lineas = ['criterio,confianza,valor,persona_id,nombre_completo,curp,rfc,email,telefono,alta'];

        foreach ($grupos as $indice => $grupo) {
            foreach ($grupo['personas'] as $persona) {
                $lineas[] = implode(',', array_map(
                    fn ($campo) => '"'.str_replace('"', '""', (string) $campo).'"',
                    [
                        $grupo['criterio'],
                        $grupo['confianza'],
                        $grupo['valor'] ?? '',
                        $persona->id,
                        $persona->nombre_completo,
                        $persona->getRawOriginal('curp') ?? '',
                        $persona->getRawOriginal('rfc') ?? '',
                        $persona->getRawOriginal('email') ?? '',
                        $persona->getRawOriginal('telefono') ?? '',
                        $persona->created_at?->toDateString() ?? '',
                    ]
                ));
            }
        }

        // BOM de UTF-8: este reporte se abre en Excel y sin él los acentos
        // llegan rotos.
        Storage::put($ruta, "\xEF\xBB\xBF".implode("\n", $lineas));

        $this->newLine();
        $this->info('Reporte guardado en storage/app/'.$ruta);
    }
}
