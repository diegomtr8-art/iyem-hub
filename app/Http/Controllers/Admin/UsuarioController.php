<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UsuarioController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Usuarios', [
            'usuarios' => User::with('roles:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'apellido', 'email', 'estado', 'last_login']),
            'roles' => Role::orderBy('name')->get(['id', 'name', 'descripcion']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/CrearUsuario', [
            'roles' => Role::orderBy('name')->get(['id', 'name', 'descripcion']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'apellido' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
        ]);

        $password = Str::password(12);

        $usuario = User::create([
            'name' => $data['name'],
            'apellido' => $data['apellido'] ?? null,
            'email' => $data['email'],
            'password' => $password,
            'estado' => true,
        ]);

        $usuario->assignRole($data['role']);

        // TODO: notificar por correo la contraseña temporal (Fortify::features password reset ya habilitado).

        return redirect()->route('admin.usuarios.index')->with('flash', [
            'password_temporal' => $password,
            'usuario_creado' => $usuario->email,
        ]);
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'apellido' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario->id)],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
        ]);

        $usuario->update([
            'name' => $data['name'],
            'apellido' => $data['apellido'] ?? null,
            'email' => $data['email'],
        ]);

        $usuario->syncRoles([$data['role']]);

        return back()->with('flash', ['success' => 'Usuario actualizado correctamente.']);
    }

    public function toggleEstado(User $usuario): RedirectResponse
    {
        abort_if($usuario->esSuperAdmin(), 403, 'El Super Admin no puede ser deshabilitado.');

        $usuario->update(['estado' => ! $usuario->estado]);

        return back()->with('flash', [
            'success' => $usuario->estado ? 'Usuario habilitado.' : 'Usuario deshabilitado.',
        ]);
    }

    public function resetPassword(User $usuario): RedirectResponse
    {
        $password = Str::password(12);

        $usuario->update(['password' => $password]);

        return back()->with('flash', [
            'password_temporal' => $password,
            'usuario_creado' => $usuario->email,
        ]);
    }
}
