<?php

namespace App\Http\Controllers;

use App\Models\Acceso;
use App\Services\CatalogoModulos;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as RespuestaHttp;

class DashboardController extends Controller
{
    public function __construct(private readonly CatalogoModulos $catalogo) {}

    public function index(Request $request): Response
    {
        $usuario = $request->user();

        return Inertia::render('Dashboard', [
            'modulos' => $this->catalogo->paraUsuario($usuario),
            'categorias' => $this->catalogo->categorias($usuario),
            'actividades' => Acceso::query()
                ->where('user_id', $usuario->id)
                ->latest('accedido_at')
                ->limit(10)
                ->get(['modulo', 'ip_address', 'accedido_at']),
        ]);
    }

    public function acceder(Request $request, string $slug): RespuestaHttp
    {
        $modulo = $this->catalogo->encontrar($slug);

        abort_unless($modulo, 404);
        abort_unless($request->user()->can("ver-{$slug}"), 403);

        // Un módulo en desarrollo o planeado no tiene a dónde mandar al usuario.
        abort_unless($modulo['navegable'], 404, 'Ese módulo todavía no está disponible.');

        Acceso::create([
            'user_id' => $request->user()->id,
            'modulo' => $slug,
            'ip_address' => $request->ip(),
            'accedido_at' => now(),
        ]);

        if ($modulo['externo']) {
            return Inertia::location($modulo['url_destino']);
        }

        return redirect($modulo['url_destino']);
    }
}
