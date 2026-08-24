<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\BuscadorController;
use App\Http\Controllers\ConsultasController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PadronController;
use App\Http\Controllers\PadronDuplicadosController;
use App\Http\Controllers\PadronImportacionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/salud', [DashboardController::class, 'salud'])->name('dashboard.salud');
    Route::get('/dashboard/modulos/{slug}', [DashboardController::class, 'acceder'])->name('dashboard.acceder');

    Route::redirect('/perfil', '/user/profile')->name('perfil');

    // Buscador global (paleta de comandos). Responde JSON, no Inertia.
    Route::get('/buscar', BuscadorController::class)
        ->middleware('permission:ver-padron')
        ->name('buscar');

    /*
    |--------------------------------------------------------------------------
    | Padrón Central
    |--------------------------------------------------------------------------
    |
    | Las rutas estáticas van antes de `/padron/{persona}`: si no, Laravel
    | intentaría resolver "mapa" y "crear" como identificadores de persona.
    |
    */
    Route::prefix('padron')->name('padron.')->group(function () {
        Route::middleware('permission:ver-padron')->group(function () {
            Route::get('/', [PadronController::class, 'index'])->name('index');
            Route::get('/mapa', [PadronController::class, 'mapa'])->name('mapa');
        });

        Route::middleware('permission:crear-padron')->group(function () {
            Route::get('/crear', [PadronController::class, 'create'])->name('create');
            Route::post('/', [PadronController::class, 'store'])->name('store');
        });

        // Importación y exportación. Van antes de `/{persona}` para que
        // "importar" y "duplicados" no se lean como identificadores.
        Route::middleware('permission:exportar-padron')
            ->get('/exportar', [PadronImportacionController::class, 'exportar'])
            ->name('exportar');

        Route::middleware('permission:importar-padron')->prefix('importar')->name('importar.')->group(function () {
            Route::get('/', [PadronImportacionController::class, 'index'])->name('index');
            Route::post('/previsualizar', [PadronImportacionController::class, 'previsualizar'])->name('previsualizar');
            Route::post('/{importacion}/confirmar', [PadronImportacionController::class, 'confirmar'])->name('confirmar');
            Route::get('/{importacion}/rechazos', [PadronImportacionController::class, 'rechazos'])->name('rechazos');
        });

        /*
         * Duplicados y fusión: solo Super Admin.
         *
         * Fusionar junta bajo una identidad los trámites de módulos
         * distintos. No es una operación que deba poder hacer cualquiera con
         * permiso de edición.
         */
        Route::middleware(['role:Super Admin', 'permission:fusionar-padron'])
            ->prefix('duplicados')->name('duplicados.')->group(function () {
                Route::get('/', [PadronDuplicadosController::class, 'index'])->name('index');
                Route::post('/fusionar', [PadronDuplicadosController::class, 'fusionar'])->name('fusionar');
                Route::post('/{fusion}/revertir', [PadronDuplicadosController::class, 'revertir'])->name('revertir');
            });

        Route::middleware('permission:ver-padron')->group(function () {
            Route::get('/{persona}', [PadronController::class, 'show'])->name('show');
        });

        Route::middleware('permission:editar-padron')->group(function () {
            Route::put('/{persona}', [PadronController::class, 'update'])->name('update');
            Route::post('/{persona}/etiquetas', [PadronController::class, 'agregarEtiqueta'])->name('etiquetas.store');
            Route::delete('/{persona}/etiquetas/{etiqueta}', [PadronController::class, 'quitarEtiqueta'])->name('etiquetas.destroy');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Consultas 360°
    |--------------------------------------------------------------------------
    |
    | Los filtros viajan en la query string a propósito: así cualquier
    | resultado se comparte copiando la URL.
    |
    */
    Route::prefix('consultas')->name('consultas.')->middleware('permission:ver-consultas')->group(function () {
        Route::get('/', [ConsultasController::class, 'index'])->name('index');
        Route::get('/{clave}/exportar', [ConsultasController::class, 'exportar'])
            ->middleware('permission:exportar-padron')
            ->name('exportar');
    });

    Route::middleware('role:Super Admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');

        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::get('/usuarios/crear', [UsuarioController::class, 'create'])->name('usuarios.create');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
        Route::patch('/usuarios/{usuario}/estado', [UsuarioController::class, 'toggleEstado'])->name('usuarios.estado');
        Route::post('/usuarios/{usuario}/reset-password', [UsuarioController::class, 'resetPassword'])->name('usuarios.reset-password');
    });
});
