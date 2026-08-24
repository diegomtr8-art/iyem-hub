<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PadronController;
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

        Route::middleware('permission:ver-padron')->group(function () {
            Route::get('/{persona}', [PadronController::class, 'show'])->name('show');
        });

        Route::middleware('permission:editar-padron')->group(function () {
            Route::put('/{persona}', [PadronController::class, 'update'])->name('update');
            Route::post('/{persona}/etiquetas', [PadronController::class, 'agregarEtiqueta'])->name('etiquetas.store');
            Route::delete('/{persona}/etiquetas/{etiqueta}', [PadronController::class, 'quitarEtiqueta'])->name('etiquetas.destroy');
        });
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
