<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $modulos = array_keys(config('modulos'));

        foreach ($modulos as $slug) {
            Permission::firstOrCreate(
                ['name' => "ver-{$slug}"],
                ['modulo' => $slug]
            );
        }

        Permission::firstOrCreate(
            ['name' => 'crear-padron'],
            ['modulo' => 'padron']
        );

        $roles = [
            'Super Admin' => 'Acceso total a la plataforma y al panel de administración.',
            'Admin Área' => 'Administra los módulos de su área asignada.',
            'Supervisor' => 'Supervisa la operación y consulta reportes de sus módulos.',
            'Operario' => 'Acceso operativo a un módulo específico.',
        ];

        foreach ($roles as $nombre => $descripcion) {
            Role::firstOrCreate(['name' => $nombre], ['descripcion' => $descripcion]);
        }

        $todosLosPermisos = Permission::pluck('name')->all();

        Role::findByName('Super Admin')->syncPermissions($todosLosPermisos);

        Role::findByName('Admin Área')->syncPermissions([
            'ver-crea', 'ver-impulsate', 'ver-nodico', 'ver-herenciaviva', 'ver-juridico',
            'ver-padron', 'crear-padron',
        ]);

        Role::findByName('Supervisor')->syncPermissions([
            'ver-crea', 'ver-impulsate', 'ver-padron',
        ]);

        Role::findByName('Operario')->syncPermissions([
            'ver-crea',
        ]);
    }
}
