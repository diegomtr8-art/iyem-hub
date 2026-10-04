<?php

namespace App\Services\Modulos;

use App\Services\Modulos\Adaptadores\NodicoAdaptador;

/**
 * Qué adaptador traduce la API de cada módulo con tablero.
 *
 * Sumar un módulo es escribir su adaptador, agregarlo a esta lista y poner su
 * clave en `config/modulos.php` → `tablero`. La página y el controlador no se
 * tocan.
 */
class RegistroDeAdaptadores
{
    /** @var array<string, class-string<AdaptadorDeModulo>> */
    private const ADAPTADORES = [
        'nodico' => NodicoAdaptador::class,
    ];

    public static function existe(?string $clave): bool
    {
        return $clave !== null && array_key_exists($clave, self::ADAPTADORES);
    }

    /** El adaptador del módulo `$slug`, o `null` si no tiene tablero. */
    public function paraModulo(string $slug): ?AdaptadorDeModulo
    {
        $clave = config("modulos.{$slug}.tablero");

        if (! self::existe($clave)) {
            return null;
        }

        return app()->make(self::ADAPTADORES[$clave], ['slug' => $slug]);
    }
}
