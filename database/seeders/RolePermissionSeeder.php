<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Permisos que no dependen de un módulo del catálogo, sino de acciones
     * concretas sobre el padrón. El valor es el módulo al que se atribuyen.
     */
    private const PERMISOS_DE_ACCION = [
        'crear-padron' => 'padron',
        'editar-padron' => 'padron',
        'exportar-padron' => 'padron',
        'importar-padron' => 'padron',
        'fusionar-padron' => 'padron',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $slugs = array_keys(config('modulos'));

        foreach ($slugs as $slug) {
            Permission::updateOrCreate(
                ['name' => "ver-{$slug}"],
                ['modulo' => $slug]
            );
        }

        foreach (self::PERMISOS_DE_ACCION as $nombre => $modulo) {
            Permission::updateOrCreate(
                ['name' => $nombre],
                ['modulo' => $modulo]
            );
        }

        $roles = [
            'Super Admin' => 'Acceso total a la plataforma y al panel de administración.',
            'Admin Área' => 'Administra los módulos de su área asignada.',
            'Supervisor' => 'Supervisa la operación y consulta reportes de sus módulos.',
            'Operario' => 'Acceso operativo a un módulo específico.',
        ];

        foreach ($roles as $nombre => $descripcion) {
            Role::updateOrCreate(['name' => $nombre], ['descripcion' => $descripcion]);
        }

        // Super Admin recibe todo lo que exista, incluidos los permisos que se
        // agreguen después. Por eso se lee de la tabla y no de una lista fija.
        Role::findByName('Super Admin')->syncPermissions(Permission::pluck('name')->all());

        Role::findByName('Admin Área')->syncPermissions([
            'ver-crea', 'ver-impulsate', 'ver-asistencia', 'ver-juridico',
            'ver-indicadores', 'ver-herenciaviva', 'ver-nodico', 'ver-coworkhub',
            'ver-crm', 'ver-padron', 'ver-consultas',
            'crear-padron', 'editar-padron', 'exportar-padron', 'importar-padron',
        ]);

        Role::findByName('Supervisor')->syncPermissions([
            'ver-crea', 'ver-impulsate', 'ver-indicadores', 'ver-herenciaviva',
            'ver-padron', 'ver-consultas',
            'exportar-padron',
        ]);

        Role::findByName('Operario')->syncPermissions([
            'ver-crea', 'ver-padron',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
