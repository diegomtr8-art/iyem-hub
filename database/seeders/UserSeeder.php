<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $passwordSuperAdmin = Str::password(14);

        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@iyem.mx'],
            [
                'name' => 'Super',
                'apellido' => 'Admin',
                'password' => $passwordSuperAdmin,
                'estado' => true,
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->assignRole('Super Admin');

        $this->command->warn('Super Admin creado -> admin@iyem.mx / '.$passwordSuperAdmin);

        $usuariosEjemplo = [
            ['Ana', 'Chan', 'admin.area1@iyem.mx', 'Admin Área'],
            ['Luis', 'Poot', 'admin.area2@iyem.mx', 'Admin Área'],
            ['María', 'Uc', 'supervisor.crea@iyem.mx', 'Supervisor'],
            ['Jorge', 'Canul', 'supervisor.impulsate@iyem.mx', 'Supervisor'],
            ['Sofía', 'Dzul', 'operario.crea1@iyem.mx', 'Operario'],
            ['Pedro', 'Ek', 'operario.crea2@iyem.mx', 'Operario'],
            ['Carmen', 'Chi', 'operario.impulsate@iyem.mx', 'Operario'],
            ['Ricardo', 'Tun', 'operario.nodico@iyem.mx', 'Operario'],
            ['Fernanda', 'Cauich', 'operario.herenciaviva@iyem.mx', 'Operario'],
            ['Miguel', 'Pech', 'operario.juridico@iyem.mx', 'Operario'],
        ];

        foreach ($usuariosEjemplo as [$nombre, $apellido, $email, $rol]) {
            $usuario = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $nombre,
                    'apellido' => $apellido,
                    'password' => Str::password(12),
                    'estado' => true,
                    'email_verified_at' => now(),
                ]
            );
            $usuario->assignRole($rol);
        }
    }
}
