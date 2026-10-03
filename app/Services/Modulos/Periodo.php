<?php

namespace App\Services\Modulos;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Rango de fechas de un tablero de módulo.
 *
 * Viaja en la query string (`?periodo=mes` o `?periodo=personalizado&desde=
 * &hasta=`), igual que los filtros de Consultas 360°: copiar la URL comparte
 * exactamente el mismo resultado.
 *
 * Los días se cuentan en hora de Mérida. La aplicación corre en UTC, y "este
 * mes" consultado a las 19:00 del día 30 no puede ser ya el mes siguiente.
 */
final class Periodo
{
    public const ZONA = 'America/Merida';

    /** Lo mismo que acepta la API de reportes de los módulos por petición. */
    public const DIAS_MAXIMOS = 366;

    public const OPCIONES = [
        'mes' => 'Este mes',
        'mes_anterior' => 'Mes anterior',
        '90_dias' => 'Últimos 90 días',
        'anio' => 'Año en curso',
        'personalizado' => 'Personalizado',
    ];

    public function __construct(
        public readonly string $clave,
        public readonly CarbonImmutable $desde,
        public readonly CarbonImmutable $hasta,
    ) {}

    /**
     * Periodo a partir de la query string. Una clave desconocida cae en "este
     * mes"; un personalizado mal formado sí es un error de quien lo escribió.
     *
     * @throws ValidationException
     */
    public static function desdeConsulta(array $entrada): self
    {
        $clave = (string) ($entrada['periodo'] ?? 'mes');

        if ($clave !== 'personalizado') {
            return self::predefinido(array_key_exists($clave, self::OPCIONES) ? $clave : 'mes');
        }

        Validator::make($entrada, [
            'desde' => ['required', 'date_format:Y-m-d'],
            'hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ], [
            'desde.required' => 'Indica desde qué fecha.',
            'hasta.required' => 'Indica hasta qué fecha.',
            'hasta.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
            '*.date_format' => 'La fecha debe tener el formato AAAA-MM-DD.',
        ])->validate();

        $desde = CarbonImmutable::createFromFormat('Y-m-d', $entrada['desde'], self::ZONA)->startOfDay();
        $hasta = CarbonImmutable::createFromFormat('Y-m-d', $entrada['hasta'], self::ZONA)->startOfDay();

        if ($desde->diffInDays($hasta) + 1 > self::DIAS_MAXIMOS) {
            throw ValidationException::withMessages(['desde' => 'El periodo no puede pasar de un año.']);
        }

        return new self('personalizado', $desde, $hasta);
    }

    public static function predefinido(string $clave, ?CarbonImmutable $hoy = null): self
    {
        $hoy = ($hoy ?? CarbonImmutable::now(self::ZONA))->startOfDay();

        [$desde, $hasta] = match ($clave) {
            'mes_anterior' => [$hoy->subMonthNoOverflow()->startOfMonth(), $hoy->subMonthNoOverflow()->endOfMonth()->startOfDay()],
            '90_dias' => [$hoy->subDays(89), $hoy],
            'anio' => [$hoy->startOfYear(), $hoy],
            default => [$hoy->startOfMonth(), $hoy],
        };

        return new self(array_key_exists($clave, self::OPCIONES) ? $clave : 'mes', $desde, $hasta);
    }

    /** Parámetros para la API del módulo. */
    public function consulta(): array
    {
        return [
            'desde' => $this->desde->toDateString(),
            'hasta' => $this->hasta->toDateString(),
        ];
    }

    /** "1 oct 2026 – 3 oct 2026". */
    public function etiqueta(): string
    {
        return $this->desde->locale('es')->translatedFormat('j M Y')
            .' – '.$this->hasta->locale('es')->translatedFormat('j M Y');
    }

    public function aArreglo(): array
    {
        return [
            'clave' => $this->clave,
            ...$this->consulta(),
            'etiqueta' => $this->etiqueta(),
        ];
    }

    /** Las opciones del selector, en orden. */
    public static function opciones(): array
    {
        return collect(self::OPCIONES)
            ->map(fn (string $nombre, string $clave) => ['clave' => $clave, 'nombre' => $nombre])
            ->values()
            ->all();
    }
}
