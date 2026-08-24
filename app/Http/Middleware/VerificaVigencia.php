<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierra la sesión de las cuentas cuya vigencia ya pasó.
 *
 * Aplica a las cuentas temporales (pruebas, servicio social). Una cuenta sin
 * `expira_at` no caduca nunca y este middleware la deja pasar de largo.
 */
class VerificaVigencia
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario && ! $usuario->vigente()) {
            $venció = $usuario->expira_at->translatedFormat('d \d\e F \d\e Y');

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $aviso = "Tu cuenta de acceso venció el {$venció}. Solicita una nueva vigencia al administrador.";

            if ($request->expectsJson()) {
                return response()->json(['message' => $aviso], 403);
            }

            return redirect()->route('login')->withErrors(['email' => $aviso]);
        }

        return $next($request);
    }
}
