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
    Route::get('/dashboard/modulos/{slug}', [DashboardController::class, 'acceder'])->name('dashboard.acceder');

    Route::redirect('/perfil', '/user/profile')->name('perfil');

    Route::get('/padron', [PadronController::class, 'index'])
        ->middleware('permission:ver-padron')
        ->name('padron.index');
    Route::get('/padron/mapa', [PadronController::class, 'mapa'])
        ->middleware('permission:ver-padron')
        ->name('padron.mapa');
    Route::get('/padron/crear', [PadronController::class, 'create'])
        ->middleware('permission:crear-padron')
        ->name('padron.create');
    Route::post('/padron', [PadronController::class, 'store'])
        ->middleware('permission:crear-padron')
        ->name('padron.store');
    Route::put('/padron/{persona}', [PadronController::class, 'update'])
        ->middleware('permission:crear-padron')
        ->name('padron.update');

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
