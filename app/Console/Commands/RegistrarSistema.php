<?php

namespace App\Console\Commands;

use App\Models\SistemaIntegrado;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;

/**
 * Da de alta un sistema satélite y le emite su token de acceso a la API.
 */
class RegistrarSistema extends Command
{
    protected $signature = 'sistemas:registrar
                            {slug : Identificador del sistema, por ejemplo "crea"}
                            {--nombre= : Nombre visible. Por omisión, el slug capitalizado}
                            {--url= : URL base del sistema}
                            {--contacto= : Correo del responsable técnico}
                            {--habilidades=padron:leer,padron:escribir,eventos:escribir : Lista separada por comas}
                            {--revocar : Revoca los tokens anteriores antes de emitir el nuevo}';

    protected $description = 'Registra un sistema satélite en el padrón central y emite su token de API';

    public function handle(): int
    {
        $slug = str($this->argument('slug'))->slug()->value();

        $habilidades = collect(explode(',', $this->option('habilidades')))
            ->map(fn (string $habilidad) => trim($habilidad))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($habilidades === []) {
            $this->error('Hay que darle al menos una habilidad al token.');

            return self::FAILURE;
        }

        $sistema = SistemaIntegrado::updateOrCreate(
            ['slug' => $slug],
            [
                'nombre' => $this->option('nombre') ?: str($slug)->replace('-', ' ')->title()->value(),
                'url_base' => $this->option('url'),
                'contacto' => $this->option('contacto'),
                'activo' => true,
            ]
        );

        $tokensPrevios = $sistema->tokens()->count();

        if ($tokensPrevios > 0) {
            $revocar = $this->option('revocar')
                || confirm("El sistema ya tiene {$tokensPrevios} token(s). ¿Revocarlos?", default: false);

            if ($revocar) {
                $sistema->tokens()->delete();
                $this->warn("Se revocaron {$tokensPrevios} token(s) anteriores.");
            }
        }

        $token = $sistema->createToken("{$slug}-api", $habilidades);

        $this->newLine();
        $this->info("Sistema «{$sistema->nombre}» listo.");
        $this->table(
            ['Campo', 'Valor'],
            [
                ['Slug', $sistema->slug],
                ['URL base', $sistema->url_base ?: '—'],
                ['Contacto', $sistema->contacto ?: '—'],
                ['Habilidades', implode(', ', $habilidades)],
            ]
        );

        $this->newLine();
        $this->line('Token de acceso (se muestra una sola vez):');
        $this->line("<fg=yellow>{$token->plainTextToken}</>");
        $this->newLine();
        $this->comment('Guárdalo en el .env del sistema satélite como IYEM_PADRON_TOKEN.');
        $this->comment('Si se pierde, hay que emitir uno nuevo: el hub no lo almacena en claro.');

        return self::SUCCESS;
    }
}
