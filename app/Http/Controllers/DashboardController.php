<?php

namespace App\Http\Controllers;

use App\Models\Acceso;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $modulos = collect(config('modulos'))
            ->filter(fn ($modulo, $slug) => $user->can("ver-{$slug}"))
            ->map(fn ($modulo, $slug) => [...$modulo, 'slug' => $slug])
            ->values();

        $actividades = Acceso::query()
            ->where('user_id', $user->id)
            ->latest('accedido_at')
            ->limit(5)
            ->get(['modulo', 'accedido_at']);

        return Inertia::render('Dashboard', [
            'modulos' => $modulos,
            'actividades' => $actividades,
        ]);
    }

    public function acceder(Request $request, string $slug)
    {
        $modulo = config("modulos.{$slug}");

        abort_unless($modulo, 404);
        abort_unless($request->user()->can("ver-{$slug}"), 403);

        Acceso::create([
            'user_id' => $request->user()->id,
            'modulo' => $slug,
            'ip_address' => $request->ip(),
            'accedido_at' => now(),
        ]);

        if ($modulo['externo']) {
            return Inertia::location($modulo['url']);
        }

        return redirect($modulo['url']);
    }
}
