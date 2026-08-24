<?php

use App\Http\Controllers\Api\PersonaController as PersonaControllerLegado;
use App\Http\Controllers\Api\V1\EventoController;
use App\Http\Controllers\Api\V1\PersonaController;
use App\Http\Controllers\Api\V1\SaludController;
use App\Http\Controllers\Api\V1\SsoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API del Padrón Central — v1
|--------------------------------------------------------------------------
|
| La consumen los sistemas satélite del IYEM. Cada uno se autentica con su
| propio token de Sanctum (tabla `sistemas_integrados`), no con la cuenta de
| un empleado: si mañana esa persona se va del instituto, la integración no
| se cae con ella.
|
| Las habilidades del token acotan qué puede hacer cada sistema:
|
|   padron:leer      Consultar el padrón.
|   padron:escribir  Dar de alta, actualizar y resolver personas.
|   eventos:escribir Reportar hechos ocurridos en el módulo.
|
| Documentación con ejemplos de curl: docs/API_PADRON.md
|
*/

Route::prefix('v1')->name('api.v1.')->group(function () {

    // Sin token a propósito: un semáforo que solo enciende para quien ya
    // tiene credenciales no sirve para diagnosticar una caída.
    Route::get('/salud', SaludController::class)->name('salud');

    Route::middleware(['auth:sanctum', 'throttle:api-sistemas'])->group(function () {

        Route::middleware('abilities:padron:leer')->group(function () {
            Route::get('/personas', [PersonaController::class, 'index'])->name('personas.index');
            Route::get('/personas/buscar', [PersonaController::class, 'buscar'])->name('personas.buscar');
            Route::get('/personas/{persona}', [PersonaController::class, 'show'])->name('personas.show');
            Route::get('/personas/{persona}/vinculos', [PersonaController::class, 'vinculos'])->name('personas.vinculos');
        });

        Route::middleware('abilities:padron:escribir')->group(function () {
            Route::post('/personas', [PersonaController::class, 'store'])->name('personas.store');
            Route::put('/personas/{persona}', [PersonaController::class, 'update'])->name('personas.update');
            Route::patch('/personas/{persona}', [PersonaController::class, 'update']);
            Route::post('/personas/resolver', [PersonaController::class, 'resolver'])->name('personas.resolver');
        });

        Route::middleware('abilities:eventos:escribir')
            ->post('/eventos', [EventoController::class, 'store'])
            ->name('eventos.store');

        /*
         * Inicio de sesión único (fase 1: el hub emite, el módulo canjea).
         * Ningún módulo satélite lo consume todavía. Ver docs/SSO.md.
         */
        Route::middleware('abilities:sso:validar')
            ->post('/sso/validar', [SsoController::class, 'validar'])
            ->name('sso.validar');
    });
});

/*
|--------------------------------------------------------------------------
| Rutas legadas — DEPRECADAS
|--------------------------------------------------------------------------
|
| Las rutas sin versionar que existían antes de `/api/v1`. Se conservan
| porque no hay forma de saber desde aquí si algún sistema satélite en
| producción todavía las llama: quitarlas de golpe lo rompería en silencio,
| y el costo de mantenerlas es este bloque.
|
| Se marcan con la cabecera `Deprecation` para que quien las siga usando lo
| note en sus propios registros.
|
| RETIRAR cuando se confirme que ningún módulo las consume. Revisar con:
|
|     SELECT name, last_used_at FROM personal_access_tokens;
|
*/
Route::middleware(['auth:sanctum', 'throttle:api-sistemas'])
    ->group(function () {
        Route::prefix('personas')->group(function () {
            Route::get('/buscar', [PersonaControllerLegado::class, 'buscar'])->name('api.personas.buscar');
            Route::get('/por-municipio/{municipio}', [PersonaControllerLegado::class, 'porMunicipio'])->name('api.personas.por-municipio');
            Route::get('/por-etiqueta/{etiqueta}', [PersonaControllerLegado::class, 'porEtiqueta'])->name('api.personas.por-etiqueta');
        });

        Route::apiResource('personas', PersonaControllerLegado::class)
            ->parameters(['personas' => 'persona']);
    });

/*
|--------------------------------------------------------------------------
| Sesión del usuario
|--------------------------------------------------------------------------
|
| Usada por el propio hub, no por los sistemas satélite.
|
*/
Route::get('/user', fn (Request $request) => $request->user())->middleware('auth:sanctum');
