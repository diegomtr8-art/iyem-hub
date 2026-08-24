<?php

namespace App\Services\Consultas;

use Illuminate\Support\Collection;

/**
 * Catálogo de las consultas predefinidas.
 *
 * Agregar una consulta nueva es escribir la clase y sumarla a esta lista;
 * el controlador y la interfaz no se tocan.
 */
class RegistroDeConsultas
{
    /** @var array<int, class-string<Consulta>> */
    private const CONSULTAS = [
        PersonasPorMunicipio::class,
        CruceDeModulos::class,
        EmbudoDelEmprendedor::class,
        CoberturaTerritorial::class,
        PersonasSinActividad::class,
        CalidadDeDatos::class,
    ];

    /** @return Collection<string, Consulta> */
    public function todas(): Collection
    {
        return collect(self::CONSULTAS)
            ->map(fn (string $clase) => app($clase))
            ->keyBy(fn (Consulta $consulta) => $consulta->clave());
    }

    public function encontrar(?string $clave): ?Consulta
    {
        return $clave ? $this->todas()->get($clave) : null;
    }

    /**
     * Resumen del catálogo para la pantalla de índice.
     */
    public function catalogo(): array
    {
        return $this->todas()
            ->map(fn (Consulta $consulta) => [
                'clave' => $consulta->clave(),
                'titulo' => $consulta->titulo(),
                'descripcion' => $consulta->descripcion(),
                'icono' => $consulta->icono(),
            ])
            ->values()
            ->all();
    }
}
