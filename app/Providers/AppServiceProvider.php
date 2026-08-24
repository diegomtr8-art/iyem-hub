<?php

namespace App\Providers;

use App\Models\Persona;
use App\Models\SistemaIntegrado;
use App\Observers\PersonaObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Persona::observe(PersonaObserver::class);

        $this->registrarLimitesDeApi();
    }

    /**
     * Límite de la API del padrón: 120 peticiones por minuto y por sistema.
     *
     * La cuenta va por token y no por IP: varios módulos pueden compartir
     * el mismo servidor de Hostinger, y limitarlos por IP haría que el
     * tráfico de CREA agotara la cuota de Impúlsate.
     */
    private function registrarLimitesDeApi(): void
    {
        RateLimiter::for('api-sistemas', function (Request $request) {
            $sistema = $request->user();

            if ($sistema instanceof SistemaIntegrado) {
                $sistema->registrarPing();

                return Limit::perMinute(120)->by("sistema:{$sistema->id}");
            }

            // Sin token válido, la clave cae a la IP: es lo único que se
            // conoce del cliente y evita que un anónimo golpee la API.
            return Limit::perMinute(30)->by($request->ip());
        });
    }
}
