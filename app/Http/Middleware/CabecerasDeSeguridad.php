<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad de la respuesta.
 *
 * Son baratas y cierran ataques que no dependen de un error de código
 * nuestro, sino de cómo el navegador interpreta la respuesta.
 */
class CabecerasDeSeguridad
{
    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        // El hub no debe poder incrustarse en un iframe ajeno: sin esto,
        // un sitio externo podría superponer controles invisibles sobre la
        // pantalla del padrón y capturar los clics del usuario.
        $respuesta->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Impide que el navegador adivine el tipo de un archivo servido y
        // termine ejecutando como script algo que se subió como imagen.
        $respuesta->headers->set('X-Content-Type-Options', 'nosniff');

        // Al salir hacia otro dominio solo se manda el origen, nunca la ruta
        // completa: una URL de consulta lleva filtros y hasta el ID de una
        // persona, y eso no tiene por qué viajar en el Referer.
        $respuesta->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Ninguna pantalla usa cámara, micrófono ni ubicación del dispositivo.
        $respuesta->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=()'
        );

        // HSTS solo bajo HTTPS. Mandarlo en el XAMPP local dejaría el
        // navegador forzando https://localhost durante meses.
        if ($request->secure()) {
            $respuesta->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $respuesta;
    }
}
