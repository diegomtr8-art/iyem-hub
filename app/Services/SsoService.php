<?php

namespace App\Services;

use App\Models\SistemaIntegrado;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Inicio de sesión único entre el hub y los módulos satélite.
 *
 * FASE 1 — lo que hace hoy
 *
 * Emite y canjea tickets de un solo uso. El hub genera un ticket cuando el
 * usuario abre un módulo; el módulo lo canjea contra el hub y recibe la
 * identidad de quien entró.
 *
 * FASE 2 — lo que falta
 *
 * Que los módulos satélite lo consuman. Este servicio deja la base lista y
 * `docs/SSO.md` explica cómo integrarlo, pero ningún módulo se ha tocado
 * todavía: hacerlo exige coordinar seis despliegues y eso se decide aparte.
 *
 * POR QUÉ TICKETS Y NO OAUTH
 *
 * Se descartó `laravel/passport`. Un servidor OAuth completo resuelve
 * problemas que el IYEM no tiene —clientes de terceros, consentimiento del
 * usuario, tokens de refresco de larga vida— y a cambio suma un flujo que
 * hay que mantener. Aquí los seis clientes son del propio instituto y
 * corren en su infraestructura: un ticket firmado de 60 segundos y un solo
 * uso cubre el caso con una fracción de la superficie de ataque.
 */
class SsoService
{
    /**
     * Vida del ticket.
     *
     * Sesenta segundos alcanzan de sobra para un redireccionamiento entre
     * dominios y no dejan margen para reutilizar un ticket capturado de un
     * historial o de un log de proxy.
     */
    public const SEGUNDOS_DE_VIDA = 60;

    private const PREFIJO_DE_CACHE = 'sso:ticket:';

    /**
     * Emite un ticket para que `$usuario` entre a `$modulo`.
     *
     * El ticket es opaco: no lleva datos del usuario, solo una referencia.
     * Lo que se guarda vive en la caché del servidor, así que interceptarlo
     * no revela nada por sí solo.
     */
    public function generarTicket(User $usuario, string $modulo): string
    {
        $modulos = array_keys(config('modulos'));

        if (! in_array($modulo, $modulos, true)) {
            throw new \InvalidArgumentException("El módulo «{$modulo}» no existe en el catálogo.");
        }

        if (! $usuario->can("ver-{$modulo}")) {
            throw new \RuntimeException("El usuario no tiene permiso para entrar a «{$modulo}».");
        }

        $ticket = Str::random(64);

        Cache::put(
            self::PREFIJO_DE_CACHE.hash('sha256', $ticket),
            [
                'usuario_id' => $usuario->id,
                'modulo' => $modulo,
                'emitido_at' => now()->toIso8601String(),
            ],
            now()->addSeconds(self::SEGUNDOS_DE_VIDA)
        );

        return $ticket;
    }

    /**
     * Canjea un ticket. Devuelve la identidad de quien entró, o `null` si el
     * ticket no existe, ya venció, ya se usó, o no es de ese sistema.
     *
     * El canje **consume** el ticket: un segundo intento con el mismo valor
     * falla siempre. Eso vuelve inútil capturarlo del historial del
     * navegador o de un registro de proxy.
     */
    public function canjearTicket(string $ticket, SistemaIntegrado $sistema): ?array
    {
        $clave = self::PREFIJO_DE_CACHE.hash('sha256', $ticket);

        $datos = Cache::pull($clave);

        if ($datos === null) {
            return null;
        }

        // El ticket sirve solo para el módulo que lo pidió: uno emitido para
        // CREA no abre Jurídico aunque el token del sistema sea válido.
        if ($datos['modulo'] !== $sistema->slug) {
            return null;
        }

        $usuario = User::find($datos['usuario_id']);

        if (! $usuario || ! $usuario->estado || ! $usuario->vigente()) {
            return null;
        }

        return [
            'usuario' => [
                'id' => $usuario->id,
                'nombre_completo' => $usuario->nombre_completo,
                'email' => $usuario->email,
                'rol' => $usuario->rol_actual,
                'permisos' => $usuario->getAllPermissions()->pluck('name')->values()->all(),
            ],
            'modulo' => $datos['modulo'],
            'emitido_at' => $datos['emitido_at'],
        ];
    }
}
