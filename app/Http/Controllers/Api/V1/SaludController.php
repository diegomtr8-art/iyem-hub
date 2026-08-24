<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Persona;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Health check del hub.
 *
 * Lo consultan los sistemas satélite antes de sincronizar, y el propio
 * dashboard cuando el hub se registra a sí mismo como módulo. Es la única
 * ruta de `/api/v1` que no exige token: un semáforo que solo enciende para
 * quien ya tiene credenciales no sirve para diagnosticar.
 */
class SaludController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $baseDeDatos = 'en_linea';

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $error) {
            $baseDeDatos = 'caido';
        }

        $todoBien = $baseDeDatos === 'en_linea';

        return response()->json([
            'estado' => $todoBien ? 'en_linea' : 'degradado',
            'servicio' => 'IYEM Hub — Padrón Central',
            'version_api' => 'v1',
            'base_de_datos' => $baseDeDatos,
            // El conteo solo se incluye si la base responde; si no, la
            // consulta reventaría justo el endpoint que debe seguir vivo
            // para reportar la falla.
            'personas' => $todoBien ? Persona::withoutGlobalScope('aislamiento_demo')->count() : null,
            'hora' => now()->toIso8601String(),
        ], $todoBien ? 200 : 503);
    }
}
