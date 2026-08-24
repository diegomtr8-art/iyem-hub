<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SistemaIntegrado;
use App\Services\SsoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Canje de tickets de inicio de sesión único.
 *
 * Lo llama el módulo satélite cuando recibe a un usuario que viene del hub
 * con un ticket en la URL. Ver `docs/SSO.md`.
 */
class SsoController extends Controller
{
    public function __construct(private readonly SsoService $sso) {}

    public function validar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'ticket' => ['required', 'string', 'size:64'],
        ]);

        $sistema = $request->user();

        if (! $sistema instanceof SistemaIntegrado) {
            return response()->json(['message' => 'Este endpoint solo lo consumen los sistemas integrados.'], 403);
        }

        $identidad = $this->sso->canjearTicket($datos['ticket'], $sistema);

        if ($identidad === null) {
            // Un solo mensaje para todos los motivos —vencido, ya usado,
            // de otro módulo, usuario dado de baja— para no darle pistas a
            // quien esté probando tickets al azar.
            return response()->json(['message' => 'Ticket inválido o vencido.'], 401);
        }

        return response()->json(['data' => $identidad]);
    }
}
