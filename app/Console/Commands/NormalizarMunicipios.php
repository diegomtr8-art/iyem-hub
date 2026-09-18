<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Persona;
// si no existe ningun modelo, podemos interactuar directamente con la BASE DE DATOS sin necesidad
// de un modelo como persona
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NormalizarMunicipios extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:normalizar-municipios';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Comando que revise los municipios ya escritos a mano y sugiera a cuál del catálogo corresponde cada uno';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //saca la lista o catalogo de los municipios
        $municipios= DB::table('Municipios')->get();

        // toma las personas que tienen municipio id null y las guarda 
        $personas = Persona::whereNull('municipio_id')->get();

        if ($personas->isEmpty()) {
            return 0;
        }

        $this->newLine();

        $encontrados = 0;
        $sinCoincidencia = 0;

        // se recorre en la lista de $personas con la variable temporal nueva llamada persona, 
        // es como el for de python
        foreach ($personas as $persona) {
            $municipioTxt = trim($persona->municipio);

            $sugerencia = $municipios->first(function ($m) use ($municipioTxt) {
                return Str::slug($m->nombre) === Str::slug($municipioTxt);
            });

            if ($sugerencia) {
                $encontrados++;
                $this->line("Se encontro conscidencia: ");
                $this->line("[Persona ID: {$persona->id}] Escrito: '{$municipioTxt}' -> Sugerencia: {$sugerencia->nombre} (ID: {$sugerencia->id})");

            } else {
                $sinCoincidencia++;
                $this->warn("No se encontro ninguna sugerencia para el municipio");
            }

            return 0;

        }

    }
}
