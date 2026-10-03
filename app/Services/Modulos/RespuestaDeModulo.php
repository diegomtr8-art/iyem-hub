<?php

namespace App\Services\Modulos;

/**
 * Lo que devuelve `ClienteDeModulo`: o los datos (el contenido de `data`, ya
 * sin la envoltura) o la falla. Nunca los dos, nunca ninguno.
 */
final class RespuestaDeModulo
{
    private function __construct(
        public readonly ?array $datos,
        public readonly ?Falla $falla,
        /** ISO 8601 del momento en que el módulo dio estos datos. */
        public readonly ?string $obtenidoEn,
    ) {}

    public static function exito(array $datos, string $obtenidoEn): self
    {
        return new self($datos, null, $obtenidoEn);
    }

    public static function fallo(Falla $falla): self
    {
        return new self(null, $falla, null);
    }

    public function ok(): bool
    {
        return $this->falla === null;
    }
}
