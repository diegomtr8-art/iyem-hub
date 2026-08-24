<?php

namespace App\Services;

use Illuminate\Http\Client\Response as Respuesta;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Semáforo de disponibilidad de los módulos del ecosistema.
 *
 * Se consulta desde el navegador después de pintar el dashboard, nunca
 * durante el render: sondear diez subdominios en serie dejaría la página en
 * blanco varios segundos cada vez que uno de ellos estuviera caído.
 */
class SaludModulos
{
    public const MINUTOS_DE_CACHE = 5;

    /**
     * Tiempo máximo de espera por módulo. Corto a propósito: aquí interesa
     * saber si el módulo responde, no esperar a que termine de pensarlo.
     */
    private const SEGUNDOS_DE_ESPERA = 3;

    public const EN_LINEA = 'en_linea';

    public const CAIDO = 'caido';

    public const SIN_MONITOREO = 'sin_monitoreo';

    public function __construct(private readonly CatalogoModulos $catalogo) {}

    /**
     * Estado de cada módulo, indexado por slug.
     *
     * @return array<string, array{estado: string, ms: int|null, revisado_at: string}>
     */
    public function estado(bool $forzar = false): array
    {
        if ($forzar) {
            Cache::forget($this->claveDeCache());
        }

        return Cache::remember(
            $this->claveDeCache(),
            now()->addMinutes(self::MINUTOS_DE_CACHE),
            fn () => $this->sondear()
        );
    }

    private function claveDeCache(): string
    {
        return 'modulos:salud';
    }

    /**
     * @return array<string, array{estado: string, ms: int|null, revisado_at: string}>
     */
    private function sondear(): array
    {
        $modulos = $this->catalogo->todos();

        $conEndpoint = $modulos->filter(
            fn (array $modulo) => $modulo['api_salud'] !== null && $modulo['navegable']
        )->values();

        $resultado = [];

        // Los módulos sin endpoint de salud no se inventan: se marcan como
        // no monitoreados, que es distinto de "caído".
        foreach ($modulos as $modulo) {
            if (! $conEndpoint->contains('slug', $modulo['slug'])) {
                $resultado[$modulo['slug']] = [
                    'estado' => self::SIN_MONITOREO,
                    'ms' => null,
                    'revisado_at' => now()->toIso8601String(),
                ];
            }
        }

        if ($conEndpoint->isEmpty()) {
            return $resultado;
        }

        // Un solo pool: los diez sondeos salen en paralelo y el peor caso es
        // el timeout de uno, no la suma de todos.
        $respuestas = Http::pool(fn ($pool) => $conEndpoint
            ->map(fn (array $modulo) => $pool
                ->as($modulo['slug'])
                ->timeout(self::SEGUNDOS_DE_ESPERA)
                ->connectTimeout(self::SEGUNDOS_DE_ESPERA)
                ->withoutVerifying()
                ->get($modulo['api_salud'])
            )
            ->all()
        );

        foreach ($conEndpoint as $modulo) {
            $slug = $modulo['slug'];
            $respuesta = $respuestas[$slug] ?? null;

            $enLinea = $respuesta instanceof Respuesta && $respuesta->successful();

            if ($respuesta instanceof \Throwable) {
                Log::info('Módulo sin respuesta en el sondeo de salud.', [
                    'modulo' => $slug,
                    'url' => $modulo['api_salud'],
                    'motivo' => $respuesta->getMessage(),
                ]);
            }

            // El tiempo lo reporta el propio cliente HTTP. Medirlo aquí daría
            // cero, porque para este punto el pool ya terminó.
            $segundos = $respuesta instanceof Respuesta
                ? ($respuesta->handlerStats()['total_time'] ?? null)
                : null;

            $resultado[$slug] = [
                'estado' => $enLinea ? self::EN_LINEA : self::CAIDO,
                'ms' => $segundos === null ? null : (int) round($segundos * 1000),
                'revisado_at' => now()->toIso8601String(),
            ];
        }

        return $resultado;
    }
}
