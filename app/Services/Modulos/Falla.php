<?php

namespace App\Services\Modulos;

/**
 * Por qué no hay datos de un módulo.
 *
 * Existe para que una consulta fallida nunca se pinte como cero: un "0 %" de
 * ocupación y un "no se pudo consultar" significan cosas opuestas, y el
 * primero acaba proyectado en una junta como si fuera un hecho.
 */
final class Falla
{
    public const SIN_RED = 'sin_red';

    public const SIN_CONFIGURAR = 'sin_configurar';

    public const NO_AUTENTICADO = 'no_autenticado';

    public const SIN_PERMISO = 'sin_permiso';

    public const MANTENIMIENTO = 'mantenimiento';

    public const LIMITE = 'limite';

    public const PERIODO = 'periodo';

    public const RESPUESTA = 'respuesta';

    public function __construct(
        public readonly string $tipo,
        public readonly string $mensaje,
        /** Qué revisar, para quien administra la integración. */
        public readonly ?string $accion = null,
        /** Si volver a intentarlo tiene sentido. Un 403 no mejora solo. */
        public readonly bool $reintentable = false,
        /** ISO 8601 del último dato bueno que se obtuvo, si hubo. */
        public readonly ?string $ultimoDato = null,
    ) {}

    public function conUltimoDato(?string $ultimoDato): self
    {
        return new self($this->tipo, $this->mensaje, $this->accion, $this->reintentable, $ultimoDato);
    }

    public function aArreglo(): array
    {
        return [
            'tipo' => $this->tipo,
            'mensaje' => $this->mensaje,
            'accion' => $this->accion,
            'reintentable' => $this->reintentable,
            'ultimo_dato' => $this->ultimoDato,
        ];
    }
}
