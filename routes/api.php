<?php

use App\Http\Controllers\Api\PersonaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->prefix('personas')->group(function () {
    Route::get('/buscar', [PersonaController::class, 'buscar'])->name('api.personas.buscar');
    Route::get('/por-municipio/{municipio}', [PersonaController::class, 'porMunicipio'])->name('api.personas.por-municipio');
    Route::get('/por-etiqueta/{etiqueta}', [PersonaController::class, 'porEtiqueta'])->name('api.personas.por-etiqueta');
});

Route::apiResource('personas', PersonaController::class)
    ->middleware('auth:sanctum')
    ->parameters(['personas' => 'persona']);
