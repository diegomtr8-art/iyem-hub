<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    public function index(): Response
    {
        $usuarios = User::query()
            ->with('roles:id,name')
            ->orderByDesc('last_login')
            ->get(['id', 'name', 'apellido', 'email', 'estado', 'last_login']);

        return Inertia::render('Admin/Index', [
            'usuarios' => $usuarios,
            'totales' => [
                'usuarios' => User::count(),
                'activos' => User::where('estado', true)->count(),
                'roles' => Role::count(),
            ],
        ]);
    }
}
