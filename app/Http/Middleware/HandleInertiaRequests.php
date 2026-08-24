<?php

namespace App\Http\Middleware;

use App\Services\CatalogoModulos;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $usuario = $request->user();
        $catalogo = app(CatalogoModulos::class);

        return [
            ...parent::share($request),

            /*
             * Permisos del usuario, como prop de primer nivel.
             *
             * No van anidados bajo `auth` a propósito: ese prop lo publica
             * `Jetstream\Http\Middleware\ShareInertiaData` por su cuenta, con
             * `Inertia::share()`, y escribirlo aquí lo reemplazaría entero,
             * dejando la aplicación sin `auth.user`.
             *
             * Se envía solo la lista de nombres: es lo único que la interfaz
             * necesita para no ofrecer botones que el servidor va a rechazar.
             */
            'permisos' => fn () => $usuario
                ? $usuario->getAllPermissions()->pluck('name')->values()
                : [],

            'modulosSidebar' => fn () => $catalogo->paraSidebar($usuario),

            'flash' => fn () => [
                'success' => $request->session()->get('flash.success'),
                'error' => $request->session()->get('flash.error'),
            ],
        ];
    }
}
