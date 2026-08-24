<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierra al rol Tester las puertas que su lista de permisos no alcanza a
 * cubrir, porque las abre Jetstream y no `spatie/laravel-permission`.
 *
 * En concreto: los tokens de API. Un Tester con un token personal podría
 * consultar la API con su propia identidad y saltarse el enmascarado que se
 * aplica en la sesión web, así que la ruta se bloquea de entrada.
 */
class RestringeTester
{
    /**
     * Prefijos de ruta vedados al rol Tester.
     */
    private const RUTAS_BLOQUEADAS = [
        'user/api-tokens',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario && $usuario->esTester() && $request->is([...self::RUTAS_BLOQUEADAS, ...array_map(
            fn (string $ruta) => "{$ruta}/*",
            self::RUTAS_BLOQUEADAS
        )])) {
            abort(403, 'El modo de pruebas no permite administrar tokens de API.');
        }

        return $next($request);
    }
}
