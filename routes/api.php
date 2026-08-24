<?php

use App\Http\Controllers\Api\V1\EventoController;
use App\Http\Controllers\Api\V1\PersonaController;
use App\Http\Controllers\Api\V1\SaludController;
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
    });
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
