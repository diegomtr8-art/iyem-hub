<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Lee `config/modulos.php` y lo normaliza para la interfaz.
 *
 * Existe para que el dashboard, el sidebar y la API no repitan cada uno su
 * propia interpretación del catálogo (orden, permisos, qué es navegable).
 */
class CatalogoModulos
{
    /**
     * Estados en los que un módulo realmente se puede visitar. Un módulo en
     * `desarrollo` o `planeado` se muestra en el dashboard con su badge, pero
     * no se enlaza: mandar al usuario a una URL que todavía no existe sería
     * peor que decirle que aún no está lista.
     */
    public const ESTADOS_NAVEGABLES = ['produccion', 'beta'];

    public const ESTADOS = ['produccion', 'beta', 'desarrollo', 'planeado'];

    public const CATEGORIAS = ['financiero', 'operativo', 'comercial', 'institucional'];

    /**
     * Catálogo completo, ordenado, sin filtrar por permisos.
     */
    public function todos(): Collection
    {
        return collect(config('modulos'))
            ->map(fn (array $modulo, string $slug) => $this->normalizar($modulo, $slug))
            ->sortBy('orden')
            ->values();
    }

    /**
     * Solo los módulos que el usuario tiene permiso de ver.
     */
    public function paraUsuario(?User $usuario): Collection
    {
        if (! $usuario) {
            return collect();
        }

        return $this->todos()->filter(
            fn (array $modulo) => $usuario->can("ver-{$modulo['slug']}")
        )->values();
    }

    /**
     * Versión reducida para el sidebar: solo lo que se puede abrir.
     */
    public function paraSidebar(?User $usuario): Collection
    {
        return $this->paraUsuario($usuario)
            ->filter(fn (array $modulo) => $modulo['navegable'])
            ->map(fn (array $modulo) => [
                'slug' => $modulo['slug'],
                'nombre' => $modulo['nombre'],
                'url' => $modulo['url'],
                'icono' => $modulo['icono'],
            ])
            ->values();
    }

    /**
     * Un módulo por slug, ya normalizado. `null` si el slug no existe.
     */
    public function encontrar(string $slug): ?array
    {
        $modulo = config("modulos.{$slug}");

        return $modulo ? $this->normalizar($modulo, $slug) : null;
    }

    /**
     * Categorías presentes en el catálogo, en el orden canónico, con su conteo.
     */
    public function categorias(?User $usuario = null): Collection
    {
        $modulos = $usuario ? $this->paraUsuario($usuario) : $this->todos();

        return collect(self::CATEGORIAS)
            ->map(fn (string $categoria) => [
                'clave' => $categoria,
                'nombre' => ucfirst($categoria),
                'total' => $modulos->where('categoria', $categoria)->count(),
            ])
            ->filter(fn (array $categoria) => $categoria['total'] > 0)
            ->values();
    }

    private function normalizar(array $modulo, string $slug): array
    {
        $navegable = in_array($modulo['estado'] ?? 'produccion', self::ESTADOS_NAVEGABLES, true);
        $externo = (bool) ($modulo['externo'] ?? false);

        return [
            'slug' => $slug,
            'nombre' => $modulo['nombre'],
            'descripcion' => $modulo['descripcion'],
            'icono' => $modulo['icono'] ?? 'squares-2x2',
            // Los externos pasan por `dashboard.acceder` para dejar registro en
            // `accesos` antes de salir del hub. Los internos van directo.
            'url' => $navegable
                ? ($externo ? route('dashboard.acceder', $slug) : $modulo['url'])
                : null,
            'url_destino' => $modulo['url'],
            'externo' => $externo,
            'estado' => $modulo['estado'] ?? 'produccion',
            'categoria' => $modulo['categoria'] ?? 'institucional',
            'responsable' => $modulo['responsable'] ?? null,
            'api_salud' => $modulo['api_salud'] ?? null,
            'color' => $modulo['color'] ?? 'iyem-primario',
            'orden' => $modulo['orden'] ?? 99,
            'navegable' => $navegable,
        ];
    }
}
