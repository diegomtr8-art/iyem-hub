<?php

namespace App\Services;

use App\Models\Acceso;
use App\Models\Modulos\CitasAgendamiento;
use App\Models\Modulos\CreaSolicitud;
use App\Models\Modulos\HerenciaVivaCliente;
use App\Models\Modulos\ImpulsateInscripcion;
use App\Models\Modulos\JuridicoAsesoria;
use App\Models\Modulos\NodicoMembresia;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Cifras que encabezan el dashboard y el mini-dato de cada tarjeta.
 *
 * Todo se calcula contra la base local. Los módulos externos todavía no
 * reportan sus cifras al hub: cuando lo hagan (vía `POST /api/v1/eventos`),
 * este servicio es el lugar donde se sumarán.
 */
class IndicadoresHub
{
    /**
     * Qué cuenta como "trámite activo" en cada módulo. Un trámite activo es
     * el que todavía espera una acción de alguien; los cancelados, los
     * rechazados y los ya cerrados no cuentan.
     */
    private const TRAMITES_ACTIVOS = [
        [CreaSolicitud::class, 'estado_solicitud', ['enviada', 'aprobada']],
        [ImpulsateInscripcion::class, 'estado', ['registrada', 'activa']],
        [NodicoMembresia::class, 'estado_membresia', ['activa']],
        [JuridicoAsesoria::class, 'estado', ['programada']],
        [CitasAgendamiento::class, 'estado', ['programada']],
    ];

    public function __construct(private readonly CatalogoModulos $catalogo) {}

    /**
     * Los KPIs del encabezado.
     */
    public function globales(User $usuario): array
    {
        $modulos = $this->catalogo->paraUsuario($usuario);

        return [
            'personas' => Persona::count(),
            'tramites_activos' => $this->tramitesActivos(),
            'modulos_visibles' => $modulos->count(),
            'modulos_navegables' => $modulos->where('navegable', true)->count(),
            'accesos_24h' => Acceso::where('accedido_at', '>=', now()->subDay())->count(),
        ];
    }

    /**
     * Un dato corto por módulo, para que la tarjeta diga algo más que su
     * nombre. Los módulos de los que el hub todavía no sabe nada se omiten:
     * la tarjeta simplemente no muestra la línea.
     *
     * @return array<string, array{valor: int, etiqueta: string}>
     */
    public function porModulo(): array
    {
        $datos = [
            'crea' => [
                'valor' => $this->contar(CreaSolicitud::class, 'estado_solicitud', ['enviada', 'aprobada']),
                'etiqueta' => 'solicitudes en curso',
            ],
            'impulsate' => [
                'valor' => $this->contar(ImpulsateInscripcion::class, 'estado', ['registrada', 'activa']),
                'etiqueta' => 'inscripciones activas',
            ],
            'nodico' => [
                'valor' => $this->contar(NodicoMembresia::class, 'estado_membresia', ['activa']),
                'etiqueta' => 'membresías activas',
            ],
            'herenciaviva' => [
                'valor' => $this->contar(HerenciaVivaCliente::class),
                'etiqueta' => 'clientes registrados',
            ],
            'juridico' => [
                'valor' => $this->contar(JuridicoAsesoria::class, 'estado', ['programada']),
                'etiqueta' => 'asesorías programadas',
            ],
            'padron' => [
                'valor' => Persona::count(),
                'etiqueta' => 'personas',
            ],
        ];

        return array_filter($datos, fn (array $dato) => $dato['valor'] > 0);
    }

    private function tramitesActivos(): int
    {
        $total = 0;

        foreach (self::TRAMITES_ACTIVOS as [$modelo, $columna, $estados]) {
            $total += $this->contar($modelo, $columna, $estados);
        }

        return $total;
    }

    /**
     * @param  class-string<Model>  $modelo
     */
    private function contar(string $modelo, ?string $columna = null, array $estados = []): int
    {
        $consulta = $modelo::query();

        if ($columna !== null && $estados !== []) {
            $consulta->whereIn($columna, $estados);
        }

        if ($alcance = $this->alcanceDePersonas()) {
            $consulta->whereIn('persona_id', $alcance);
        }

        return $consulta->count();
    }

    /**
     * Las tablas de módulo no tienen columna `demo`, así que el aislamiento
     * del rol Tester no las alcanza por sí solo. Sin esta restricción, un
     * Tester vería el conteo real de solicitudes y membresías: datos reales
     * filtrados en forma de agregado.
     *
     * Solo se aplica cuando hace falta, para no cargar una subconsulta en
     * cada cifra del dashboard del resto de los usuarios.
     */
    private function alcanceDePersonas(): ?Builder
    {
        $usuario = Auth::user();

        return $usuario instanceof User && $usuario->esTester()
            ? Persona::query()->select('id')
            : null;
    }
}
