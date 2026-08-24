<?php

namespace App\Providers;

use App\Models\Persona;
use App\Observers\PersonaObserver;
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
    }
}
