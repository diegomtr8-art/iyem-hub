<?php

namespace Database\Seeders;

use App\Models\Persona;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PersonaSeeder extends Seeder
{
    public function run(): void
    {
        $nombres = ['Alejandra', 'Bruno', 'Camila', 'Diego', 'Elena', 'Fabián', 'Gabriela', 'Hugo', 'Itzel', 'Joaquín',
            'Karla', 'Leonardo', 'Mariana', 'Nicolás', 'Olivia', 'Pablo', 'Renata', 'Santiago', 'Ximena', 'Yolanda',
            'Andrea', 'Benjamín', 'Cecilia', 'David', 'Estefanía', 'Francisco', 'Gerardo', 'Helena', 'Iván', 'Julieta',
            'Kevin', 'Lorena', 'Manuel', 'Natalia', 'Óscar', 'Paola', 'Rodrigo', 'Sara', 'Tomás', 'Valentina'];
        $apellidos = ['Balam', 'Cocom', 'Dzib', 'Euan', 'Ferrer', 'Gómez', 'Herrera', 'Iuit', 'Jiménez', 'Kú',
            'López', 'Moo', 'Novelo', 'Ortiz', 'Pinto', 'Quintal', 'Ríos', 'Solís', 'Torres', 'Uribe',
            'Aké', 'Briceño', 'Canul', 'Chan', 'Chi', 'Cauich', 'Dzul', 'Ek', 'Poot', 'Tun',
            'Uc', 'Vela', 'Yam', 'Zapata', 'Alcocer', 'Baas', 'Cetina', 'Duarte', 'Estrella', 'Flota'];
        $municipios = array_keys(config('municipios_yucatan'));
        $niveles = ['Primaria', 'Secundaria', 'Preparatoria', 'Licenciatura', 'Posgrado'];
        $modulos = ['crea', 'impulsate', 'nodico', 'herencia_viva', 'juridico', 'padron'];
        $mediosIngreso = ['referencia', 'redes_sociales', 'evento', 'sitio_web', 'campaña_municipal'];

        $total = 40;

        for ($i = 0; $i < $total; $i++) {
            $nombre = $nombres[$i % count($nombres)];
            $apellido = $apellidos[$i % count($apellidos)];
            $sexo = $i % 2 === 0 ? 'M' : 'F';
            $fechaNacimiento = Carbon::now()->subYears(random_int(18, 65))->subDays(random_int(0, 364));

            Persona::create([
                'nombre_completo' => "{$nombre} {$apellido}",
                'email' => strtolower($nombre.'.'.$apellido).$i.'@ejemplo.com',
                'telefono' => '999'.str_pad((string) random_int(1000000, 9999999), 7, '0', STR_PAD_LEFT),

                'curp' => $this->curpFalso($nombre, $apellido, $fechaNacimiento, $sexo, $i),
                'rfc' => $this->rfcFalso($nombre, $apellido, $fechaNacimiento, $i),

                'calle' => 'Calle '.random_int(10, 90).' #'.random_int(100, 599),
                'codigo_postal' => (string) random_int(97000, 97999),
                'ciudad' => $municipios[$i % count($municipios)],
                'municipio' => $municipios[$i % count($municipios)],
                'estado' => 'Yucatán',
                'pais' => 'México',

                'fecha_nacimiento' => $fechaNacimiento->toDateString(),
                'sexo' => $sexo,

                'nivel_educativo' => $niveles[$i % count($niveles)],
                'habla_maya' => $i % 3 === 0,

                'idioma' => 'es',
                'medio_ingreso' => $mediosIngreso[$i % count($mediosIngreso)],

                'tipo_persona' => 'fisica',
                'estado_persona' => $i % 7 === 0 ? 'inactiva' : 'activa',

                'creado_por_modulo' => $modulos[$i % count($modulos)],
            ]);
        }
    }

    private function soloLetras(string $texto): string
    {
        return preg_replace('/[^A-Za-z]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $texto));
    }

    private function curpFalso(string $nombre, string $apellido, Carbon $fechaNacimiento, string $sexo, int $indice): string
    {
        $apellido = $this->soloLetras($apellido);
        $nombre = $this->soloLetras($nombre);
        $letras = strtoupper(substr($apellido, 0, 2).substr($nombre, 0, 2));
        $fecha = $fechaNacimiento->format('ymd');
        $consonantes = 'BCDFGHJKLMNPQRSTVWXYZ';
        $homoclave = $consonantes[$indice % strlen($consonantes)].$consonantes[($indice + 3) % strlen($consonantes)].$consonantes[($indice + 7) % strlen($consonantes)];
        $alfanumerico = $consonantes[($indice + 1) % strlen($consonantes)];
        $digito = $indice % 10;

        return "{$letras}{$fecha}{$sexo}YU{$homoclave}{$alfanumerico}{$digito}";
    }

    private function rfcFalso(string $nombre, string $apellido, Carbon $fechaNacimiento, int $indice): string
    {
        $apellido = $this->soloLetras($apellido);
        $nombre = $this->soloLetras($nombre);
        $letras = strtoupper(substr($apellido, 0, 3).substr($nombre, 0, 1));
        $fecha = $fechaNacimiento->format('ymd');
        $consonantes = 'BCDFGHJKLMNPQRSTVWXYZ';
        $homoclave = $consonantes[$indice % strlen($consonantes)].($indice % 10).$consonantes[($indice + 5) % strlen($consonantes)];

        return "{$letras}{$fecha}{$homoclave}";
    }
}
