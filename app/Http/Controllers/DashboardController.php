<?php

namespace App\Http\Controllers;

use App\Models\Acceso;
use App\Services\CatalogoModulos;
use App\Services\IndicadoresHub;
use App\Services\SaludModulos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as RespuestaHttp;

class DashboardController extends Controller
{
    public function __construct(
        private readonly CatalogoModulos $catalogo,
        private readonly IndicadoresHub $indicadores,
        private readonly SaludModulos $salud,
    ) {}

    public function index(Request $request): Response
    {
        $usuario = $request->user();
        $datosPorModulo = $this->indicadores->porModulo();

        $modulos = $this->catalogo->paraUsuario($usuario)
            ->map(fn (array $modulo) => [
                ...$modulo,
                'dato' => $datosPorModulo[$modulo['slug']] ?? null,
            ]);

        return Inertia::render('Dashboard', [
            'modulos' => $modulos,
            'categorias' => $this->catalogo->categorias($usuario),
            'indicadores' => $this->indicadores->globales($usuario),
            'actividades' => $this->actividadesDe($usuario->id),
            // La bitácora completa solo la ve quien administra la plataforma.
            'actividadesPlataforma' => $usuario->esSuperAdmin()
                ? $this->actividadesDePlataforma()
                : null,
        ]);
    }

    /**
     * Semáforo de los módulos.
     *
     * Endpoint aparte, consultado desde el navegador ya que la página está
     * pintada: sondear diez subdominios durante el render dejaría el
     * dashboard en blanco cada vez que uno de ellos no contestara.
     */
    public function salud(Request $request): JsonResponse
    {
        return response()->json([
            'salud' => $this->salud->estado(),
            'vigencia_minutos' => SaludModulos::MINUTOS_DE_CACHE,
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

    private function actividadesDe(int $usuarioId): Collection
    {
        return Acceso::query()
            ->where('user_id', $usuarioId)
            ->latest('accedido_at')
            ->limit(10)
            ->get(['id', 'modulo', 'ip_address', 'accedido_at']);
    }

    private function actividadesDePlataforma(): Collection
    {
        return Acceso::query()
            ->with('user:id,name,apellido')
            ->latest('accedido_at')
            ->limit(30)
            ->get(['id', 'user_id', 'modulo', 'ip_address', 'accedido_at'])
            ->map(fn (Acceso $acceso) => [
                'id' => $acceso->id,
                'modulo' => $acceso->modulo,
                'ip_address' => $acceso->ip_address,
                'accedido_at' => $acceso->accedido_at,
                'usuario' => trim("{$acceso->user?->name} {$acceso->user?->apellido}") ?: 'Usuario eliminado',
            ]);
    }
}
