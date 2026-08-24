<?php

namespace Database\Seeders;

use App\Models\Modulos\CitasAgendamiento;
use App\Models\Modulos\CreaSolicitud;
use App\Models\Modulos\HerenciaVivaCliente;
use App\Models\Modulos\ImpulsateInscripcion;
use App\Models\Modulos\JuridicoAsesoria;
use App\Models\Modulos\NodicoMembresia;
use App\Models\Persona;
use Faker\Factory as Faker;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Padrón de demostración: 200 personas ficticias y sus registros de módulo.
 *
 * Es lo único que ve el rol Tester. Los nombres salen de un banco de
 * apellidos yucatecos y el resto de los campos, de Faker. Ninguna persona,
 * CURP, RFC, teléfono ni correo corresponde a alguien real.
 *
 * El seeder es idempotente: borra su propio lote (`demo = true`) antes de
 * volver a generarlo, y nunca toca las personas reales del padrón.
 */
class PadronDemoSeeder extends Seeder
{
    public const TOTAL = 200;

    /**
     * Entidad federativa inválida dentro de la CURP.
     *
     * El catálogo oficial de RENAPO no incluye "ZZ". Usarla deja una CURP
     * con la forma correcta (18 caracteres, estructura válida, pasa el
     * regex de captura) pero que no puede pertenecer a nadie: es la garantía
     * de que estos datos jamás colisionan con una persona real.
     */
    private const ENTIDAD_FICTICIA = 'ZZ';

    private const CONSONANTES = 'BCDFGHJKLMNPQRSTVWXYZ';

    /*
     * Banco de nombres propio.
     *
     * La versión instalada de fakerphp/faker no incluye proveedor `es_MX`:
     * al pedirlo, cae en silencio a `en_US` y devuelve nombres como
     * "Libby Sporer VonRueden". Los apellidos de abajo son mayas y mestizos
     * de uso corriente en Yucatán, que es lo que el padrón real contiene.
     * Faker se sigue usando para todo lo demás (fechas, números, booleanos).
     */
    private const NOMBRES_HOMBRE = [
        'José', 'Juan', 'Miguel', 'Luis', 'Carlos', 'Jorge', 'Manuel', 'Pedro', 'Ricardo', 'Fernando',
        'Roberto', 'Andrés', 'Diego', 'Santiago', 'Emilio', 'Gaspar', 'Wilberth', 'Freddy', 'Genaro', 'Eleuterio',
        'Rodrigo', 'Alejandro', 'Marcos', 'Iván', 'Rubén', 'Armando', 'Efraín', 'Gilberto', 'Nicolás', 'Joaquín',
    ];

    private const NOMBRES_MUJER = [
        'María', 'Guadalupe', 'Rosa', 'Elsy', 'Silvia', 'Landy', 'Mireya', 'Candelaria', 'Fátima', 'Leticia',
        'Ana', 'Carmen', 'Sofía', 'Valentina', 'Mariana', 'Alejandra', 'Norma', 'Adriana', 'Beatriz', 'Nayeli',
        'Ximena', 'Paulina', 'Itzel', 'Renata', 'Gabriela', 'Yolanda', 'Concepción', 'Estela', 'Julieta', 'Araceli',
    ];

    private const APELLIDOS = [
        // Apellidos mayas
        'Canul', 'Chan', 'Chi', 'Dzul', 'Ek', 'Poot', 'Tun', 'Uc', 'Balam', 'Cocom',
        'Cauich', 'Kú', 'Moo', 'Yam', 'Baas', 'Aké', 'Iuit', 'Pech', 'Cen', 'May',
        'Puc', 'Noh', 'Ci', 'Tzec', 'Xool', 'Che', 'Uicab', 'Cimé', 'Kauil', 'Batun',
        // Apellidos mestizos frecuentes en la península
        'Novelo', 'Quintal', 'Alcocer', 'Cetina', 'Estrella', 'Solís', 'Briceño', 'Ancona', 'Sabido', 'Rejón',
        'Loría', 'Marrufo', 'Peniche', 'Vadillo', 'Herrera', 'Gómez', 'Ortiz', 'Ríos', 'Torres', 'Zapata',
    ];

    public function run(): void
    {
        $faker = Faker::create('es_MX');
        $faker->seed(20260824); // Reproducible: el mismo padrón demo en cada entorno.

        $this->limpiarLoteAnterior();

        $municipios = array_keys(config('municipios_yucatan'));
        $niveles = ['Primaria', 'Secundaria', 'Preparatoria', 'Licenciatura', 'Posgrado'];
        $mediosIngreso = ['referencia', 'redes_sociales', 'evento', 'sitio_web', 'campaña_municipal'];
        $modulosOrigen = ['crea', 'impulsate', 'nodico', 'herenciaviva', 'juridico', 'padron'];
        $etiquetasPosibles = ['emprendedor', 'artesano', 'vip', 'moroso', 'mayorista', 'joven', 'mujer_emprendedora', 'zona_rural'];

        $this->command?->info('Generando '.self::TOTAL.' personas de demostración...');

        for ($i = 0; $i < self::TOTAL; $i++) {
            $sexo = $faker->randomElement(['M', 'F']);
            $nombre = $faker->randomElement($sexo === 'M' ? self::NOMBRES_HOMBRE : self::NOMBRES_MUJER);
            $paterno = $faker->randomElement(self::APELLIDOS);
            $materno = $faker->randomElement(self::APELLIDOS);
            $fechaNacimiento = Carbon::instance($faker->dateTimeBetween('-65 years', '-18 years'));
            $municipio = $faker->randomElement($municipios);

            $persona = Persona::create([
                'nombre_completo' => "{$nombre} {$paterno} {$materno}",
                'email' => Str::lower(Str::ascii("{$nombre}.{$paterno}{$i}")).'@ejemplo-demo.mx',
                'telefono' => '999'.$faker->numerify('#######'),
                'telefono_secundario' => $faker->boolean(25) ? '999'.$faker->numerify('#######') : null,

                'curp' => $this->curpFicticia($nombre, $paterno, $materno, $fechaNacimiento, $sexo, $i),
                'rfc' => $this->rfcFicticio($paterno, $materno, $nombre, $fechaNacimiento, $i),

                'calle' => 'Calle '.$faker->numberBetween(10, 90).' #'.$faker->numberBetween(100, 599),
                'codigo_postal' => (string) $faker->numberBetween(97000, 97999),
                'ciudad' => $municipio,
                'municipio' => $municipio,
                'localidad' => $faker->boolean(30) ? 'Comisaría '.$faker->randomElement(self::APELLIDOS) : null,
                'estado' => 'Yucatán',
                'pais' => 'México',

                'fecha_nacimiento' => $fechaNacimiento->toDateString(),
                'sexo' => $sexo,

                'nivel_educativo' => $faker->randomElement($niveles),
                'habla_maya' => $faker->boolean(35),

                'facebook_negocio' => $faker->boolean(40) ? 'https://facebook.com/negocio-demo-'.$i : null,
                'instagram_negocio' => $faker->boolean(30) ? 'https://instagram.com/negocio_demo_'.$i : null,

                'idioma' => 'es',
                'medio_ingreso' => $faker->randomElement($mediosIngreso),

                'tipo_persona' => $faker->boolean(12) ? 'moral' : 'fisica',
                'estado_persona' => $faker->boolean(85) ? 'activa' : 'inactiva',

                'creado_por_modulo' => $faker->randomElement($modulosOrigen),
                'demo' => true,
            ]);

            foreach ($faker->randomElements($etiquetasPosibles, $faker->numberBetween(0, 3)) as $etiqueta) {
                $persona->agregarEtiqueta($etiqueta);
            }

            $this->generarVinculos($persona, $faker);
        }

        $this->command?->info('Padrón de demostración listo: '.self::TOTAL.' personas marcadas con demo = true.');
    }

    /**
     * Crea los registros de módulo siguiendo el embudo real del instituto:
     * la persona entra por Impúlsate, algunas piden crédito en CREA, de esas
     * algunas rentan en Nódico y unas pocas terminan vendiendo en Herencia
     * Viva. Sin este sesgo, la consulta del embudo mostraría ruido.
     */
    private function generarVinculos(Persona $persona, Generator $faker): void
    {
        $tomoImpulsate = $faker->boolean(55);
        $pidioCredito = $faker->boolean($tomoImpulsate ? 45 : 12);
        $rentaCoworking = $faker->boolean($pidioCredito ? 30 : 6);
        $vendeEnHerenciaViva = $faker->boolean($rentaCoworking ? 40 : 8);

        if ($tomoImpulsate) {
            ImpulsateInscripcion::create([
                'persona_id' => $persona->id,
                'programa_id' => $faker->numberBetween(1, 8),
                'programa_nombre' => $faker->randomElement([
                    'Impúlsate Básico', 'Finanzas para tu negocio', 'Ventas digitales',
                    'Formalización fiscal', 'Marca y empaque',
                ]),
                'fecha_inscripcion' => $faker->dateTimeBetween('-2 years', '-1 month'),
                'estado' => $faker->randomElement(['registrada', 'activa', 'completada', 'cancelada']),
            ]);
        }

        if ($pidioCredito) {
            CreaSolicitud::create([
                'persona_id' => $persona->id,
                'monto_solicitado' => $faker->randomElement([15000, 25000, 40000, 60000, 100000]),
                'tipo_credito' => $faker->randomElement(['Semilla', 'Crecimiento', 'Equipamiento', 'Capital de trabajo']),
                'estado_solicitud' => $faker->randomElement(['borrador', 'enviada', 'aprobada', 'rechazada', 'desembolsada']),
                'fecha_solicitud' => $faker->dateTimeBetween('-18 months', 'now'),
            ]);
        }

        if ($rentaCoworking) {
            $inicio = Carbon::instance($faker->dateTimeBetween('-14 months', '-1 month'));

            NodicoMembresia::create([
                'persona_id' => $persona->id,
                'tipo_membresia' => $faker->randomElement(['Hot desk', 'Escritorio fijo', 'Oficina privada', 'Sala por hora']),
                'estado_membresia' => $faker->randomElement(['activa', 'pausada', 'cancelada']),
                'fecha_inicio' => $inicio->toDateString(),
                'fecha_fin' => $faker->boolean(60) ? $inicio->copy()->addMonths($faker->numberBetween(1, 12))->toDateString() : null,
            ]);
        }

        if ($vendeEnHerenciaViva) {
            $compras = $faker->numberBetween(1, 40);

            HerenciaVivaCliente::create([
                'persona_id' => $persona->id,
                'numero_cliente' => 'DEMO-'.str_pad((string) $persona->id, 6, '0', STR_PAD_LEFT),
                'fecha_primer_compra' => $faker->dateTimeBetween('-3 years', '-2 months'),
                'total_gastado' => $compras * $faker->numberBetween(180, 2400),
                'numero_compras' => $compras,
                'es_mayorista' => $faker->boolean(20),
            ]);
        }

        if ($faker->boolean(14)) {
            JuridicoAsesoria::create([
                'persona_id' => $persona->id,
                'tipo_asesoria' => $faker->randomElement(['Constitución de sociedad', 'Contrato de arrendamiento', 'Marca registrada', 'Laboral']),
                'fecha_asesoria' => $faker->dateTimeBetween('-2 years', '+1 month'),
                'estado' => $faker->randomElement(['programada', 'completada', 'no_comparecio']),
                'notas' => 'Nota de demostración. No corresponde a un asunto real.',
            ]);
        }

        foreach (range(1, $faker->numberBetween(0, 3)) as $ignorado) {
            CitasAgendamiento::create([
                'persona_id' => $persona->id,
                'tipo_cita' => $faker->randomElement(['Primera asesoría', 'Seguimiento', 'Entrega de documentos', 'Firma']),
                'fecha_cita' => $faker->dateTimeBetween('-1 year', '+2 months'),
                'estado' => $faker->randomElement(['programada', 'realizada', 'cancelada', 'no_asistio']),
                'modulo_destino' => $faker->randomElement(['crea', 'impulsate', 'juridico', 'nodico']),
            ]);
        }
    }

    /**
     * Borra el lote de demostración anterior para que volver a sembrar no
     * duplique. Las cascadas de las llaves foráneas se llevan los registros
     * de módulo, las etiquetas y las auditorías asociadas.
     */
    private function limpiarLoteAnterior(): void
    {
        $anteriores = Persona::query()->sinAislamientoDemo()->where('demo', true);
        $total = $anteriores->count();

        if ($total > 0) {
            $this->command?->warn("Eliminando {$total} personas de demostración del lote anterior.");
            $anteriores->forceDelete();
        }
    }

    /**
     * CURP con estructura válida pero entidad federativa inexistente (ZZ).
     */
    private function curpFicticia(string $nombre, string $paterno, string $materno, Carbon $nacimiento, string $sexo, int $indice): string
    {
        $normaliza = fn (string $texto) => Str::upper(Str::ascii($texto));

        $paterno = $normaliza($paterno);
        $materno = $normaliza($materno) ?: 'X';
        $nombre = $normaliza($nombre);

        $primeraVocal = 'X';
        foreach (str_split(substr($paterno, 1)) as $letra) {
            if (str_contains('AEIOU', $letra)) {
                $primeraVocal = $letra;
                break;
            }
        }

        return $paterno[0]
            .$primeraVocal
            .($materno[0] ?? 'X')
            .$nombre[0]
            .$nacimiento->format('ymd')
            .($sexo === 'M' ? 'H' : 'M')
            .self::ENTIDAD_FICTICIA
            .$this->consonantesInternas($paterno)
            .$this->consonantesInternas($materno)
            .$this->consonantesInternas($nombre)
            .Str::upper(base_convert((string) ($indice % 36), 10, 36))
            .((string) ($indice % 10));
    }

    private function rfcFicticio(string $paterno, string $materno, string $nombre, Carbon $nacimiento, int $indice): string
    {
        $normaliza = fn (string $texto) => Str::upper(Str::ascii($texto));

        $paterno = $normaliza($paterno);
        $materno = $normaliza($materno) ?: 'X';
        $nombre = $normaliza($nombre);

        $primeraVocal = 'X';
        foreach (str_split(substr($paterno, 1)) as $letra) {
            if (str_contains('AEIOU', $letra)) {
                $primeraVocal = $letra;
                break;
            }
        }

        $homoclave = Str::upper(substr(md5("demo-{$indice}-{$paterno}"), 0, 3));

        return $paterno[0].$primeraVocal.($materno[0] ?? 'X').$nombre[0]
            .$nacimiento->format('ymd')
            .$homoclave;
    }

    /**
     * Primera consonante interna del apellido o nombre, como exige la CURP.
     */
    private function consonantesInternas(string $palabra): string
    {
        foreach (str_split(substr($palabra, 1)) as $letra) {
            if (str_contains(self::CONSONANTES, $letra)) {
                return $letra;
            }
        }

        return 'X';
    }
}
