<?php

namespace App\Services;

use App\Models\Persona;
use App\Models\PersonaFusion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Fusiona dos fichas que resultaron ser la misma persona, y deshace la
 * fusión si estuvo mal.
 *
 * Es la operación más delicada del padrón: junta bajo una sola identidad
 * trámites que venían de módulos distintos. Si se equivoca, se mezcló el
 * historial de dos personas reales, y eso no se arregla a mano.
 *
 * De ahí las tres reglas:
 *
 *   1. Antes de tocar nada se guarda el estado completo de ambas fichas.
 *   2. La duplicada se borra en lógico, nunca en físico.
 *   3. Durante 30 días la fusión se revierte y todo vuelve a su lugar.
 */
class FusionadorPersonas
{
    public const DIAS_PARA_REVERTIR = 30;

    /**
     * Campos con restricción UNIQUE en `personas`.
     *
     * No se pueden copiar a la principal mientras la duplicada los siga
     * ocupando: la baja lógica no libera el índice único, así que el UPDATE
     * choca contra la restricción. Se vacían en la duplicada antes de
     * transferirlos, y al revertir se le devuelven desde el snapshot.
     */
    private const CAMPOS_UNICOS = ['curp', 'email'];

    /**
     * Tablas de módulo cuyos registros cambian de dueño en la fusión.
     */
    private const TABLAS_DE_MODULO = [
        'crea_solicitudes',
        'impulsate_inscripciones',
        'nodico_membresias',
        'herencia_viva_clientes',
        'juridico_asesorias',
        'citas_agendamientos',
        'eventos_modulo',
    ];

    /**
     * Junta `$duplicada` dentro de `$principal`.
     *
     * La principal manda: solo se le copian los campos que tenía vacíos. Si
     * las dos fichas discrepan en un dato, se conserva el de la principal y
     * el de la duplicada queda guardado en el snapshot, no se pierde.
     */
    public function fusionar(
        Persona $principal,
        Persona $duplicada,
        User $usuario,
        ?string $criterio = null,
        ?string $motivo = null,
    ): PersonaFusion {
        if ($principal->id === $duplicada->id) {
            throw new \InvalidArgumentException('No se puede fusionar una persona consigo misma.');
        }

        return DB::transaction(function () use ($principal, $duplicada, $usuario, $criterio, $motivo) {
            $snapshotPrincipal = $this->fotografiar($principal);
            $snapshotDuplicada = $this->fotografiar($duplicada);

            $camposCompletados = $this->completarCampos($principal, $duplicada, $usuario);
            $vinculosMovidos = $this->moverVinculos($principal, $duplicada);
            $etiquetasMovidas = $this->moverEtiquetas($principal, $duplicada);

            // La auditoría de la duplicada se conserva bajo la principal:
            // el historial de cambios es parte de lo que se está fusionando.
            DB::table('personas_auditorias')
                ->where('persona_id', $duplicada->id)
                ->update(['persona_id' => $principal->id]);

            $principal->marcarAuditoria(
                campo: '__fusion__',
                valor_anterior: "persona #{$duplicada->id}",
                valor_nuevo: "absorbida en persona #{$principal->id}",
                usuario_id: $usuario->id,
                modulo: 'padron'
            );

            // Baja lógica: la ficha sigue ahí por si hay que revertir.
            $duplicada->update(['estado_persona' => 'inactiva']);
            $duplicada->delete();

            return PersonaFusion::create([
                'principal_id' => $principal->id,
                'duplicada_id' => $duplicada->id,
                'snapshot_principal' => $snapshotPrincipal,
                'snapshot_duplicada' => $snapshotDuplicada,
                'vinculos_movidos' => $vinculosMovidos,
                'etiquetas_movidas' => $etiquetasMovidas,
                'campos_completados' => $camposCompletados,
                'usuario_id' => $usuario->id,
                'criterio' => $criterio,
                'motivo' => $motivo,
                'revertible_hasta' => now()->addDays(self::DIAS_PARA_REVERTIR),
            ]);
        });
    }

    /**
     * Deshace una fusión: devuelve cada registro a su dueño original y
     * revive la ficha duplicada.
     */
    public function revertir(PersonaFusion $fusion, User $usuario): void
    {
        if ($fusion->revertida_at !== null) {
            throw new \RuntimeException('Esta fusión ya fue revertida.');
        }

        if (! $fusion->esRevertible()) {
            throw new \RuntimeException(
                'La ventana de '.self::DIAS_PARA_REVERTIR.' días para revertir esta fusión ya cerró.'
            );
        }

        DB::transaction(function () use ($fusion, $usuario) {
            $duplicada = Persona::withTrashed()
                ->sinAislamientoDemo()
                ->findOrFail($fusion->duplicada_id);

            $principal = Persona::sinAislamientoDemo()->findOrFail($fusion->principal_id);

            $duplicada->restore();

            // Los vínculos vuelven exactamente a las filas que se movieron:
            // se guardaron sus identificadores, no solo el conteo, porque
            // entre la fusión y la reversión pueden haberse creado registros
            // nuevos que sí pertenecen a la principal.
            foreach ($fusion->vinculos_movidos ?? [] as $tabla => $identificadores) {
                if ($identificadores === []) {
                    continue;
                }

                DB::table($tabla)
                    ->whereIn('id', $identificadores)
                    ->update(['persona_id' => $duplicada->id]);
            }

            foreach ($fusion->etiquetas_movidas ?? [] as $etiqueta) {
                DB::table('personas_etiquetas')
                    ->where('persona_id', $principal->id)
                    ->where('etiqueta', $etiqueta)
                    ->delete();

                DB::table('personas_etiquetas')->insertOrIgnore([
                    'persona_id' => $duplicada->id,
                    'etiqueta' => $etiqueta,
                ]);
            }

            /*
             * Los campos que se completaron con datos de la duplicada se
             * vuelven a vaciar: eran de ella, no de la principal.
             *
             * El orden importa. Primero se liberan en la principal y solo
             * después se le devuelven a la duplicada; al revés, los campos
             * con índice único chocarían contra la restricción.
             */
            $completados = $fusion->campos_completados ?? [];

            if ($completados !== []) {
                $principal->forceFill(array_fill_keys(array_keys($completados), null))->save();
            }

            $duplicada->forceFill([
                ...$completados,
                // El estado que tenía la duplicada antes de la fusión.
                'estado_persona' => $fusion->snapshot_duplicada['estado_persona'] ?? 'activa',
            ])->save();

            $principal->marcarAuditoria(
                campo: '__fusion_revertida__',
                valor_anterior: "absorbida persona #{$duplicada->id}",
                valor_nuevo: 'fusión deshecha',
                usuario_id: $usuario->id,
                modulo: 'padron'
            );

            $fusion->update([
                'revertida_at' => now(),
                'revertida_por' => $usuario->id,
            ]);
        });
    }

    /**
     * Estado completo de una ficha, para poder reconstruirla.
     */
    private function fotografiar(Persona $persona): array
    {
        return [
            ...$persona->getAttributes(),
            'etiquetas' => $persona->etiquetas()->pluck('etiqueta')->all(),
        ];
    }

    /**
     * Copia a la principal solo los campos que tenía vacíos.
     *
     * @return array<string, mixed> Campos que se llenaron, para poder deshacerlo.
     */
    private function completarCampos(Persona $principal, Persona $duplicada, User $usuario): array
    {
        $completados = [];

        foreach ($principal->getFillable() as $campo) {
            if (in_array($campo, ['demo', 'creado_por_modulo', 'estado_persona'], true)) {
                continue;
            }

            $valorDuplicada = $duplicada->getRawOriginal($campo);

            if (blank($principal->getRawOriginal($campo)) && filled($valorDuplicada)) {
                $completados[$campo] = $valorDuplicada;
            }
        }

        if ($completados === []) {
            return [];
        }

        // Primero se liberan en la duplicada los campos con índice único;
        // si no, el UPDATE de la principal choca contra la restricción.
        $aLiberar = array_intersect(array_keys($completados), self::CAMPOS_UNICOS);

        if ($aLiberar !== []) {
            $duplicada->forceFill(array_fill_keys($aLiberar, null))->saveQuietly();
        }

        $principal->forceFill($completados)->save();

        foreach ($completados as $campo => $valor) {
            $principal->marcarAuditoria(
                campo: $campo,
                valor_anterior: null,
                valor_nuevo: (string) $valor,
                usuario_id: $usuario->id,
                modulo: 'padron'
            );
        }

        return $completados;
    }

    /**
     * Pasa los registros de módulo de la duplicada a la principal.
     *
     * Guarda los identificadores movidos, no solo el conteo: al revertir
     * hay que devolver exactamente esas filas, y entre una cosa y otra
     * pueden haberse creado registros nuevos que sí son de la principal.
     *
     * @return array<string, array<int, int>>
     */
    private function moverVinculos(Persona $principal, Persona $duplicada): array
    {
        $movidos = [];

        foreach (self::TABLAS_DE_MODULO as $tabla) {
            $identificadores = DB::table($tabla)
                ->where('persona_id', $duplicada->id)
                ->pluck('id')
                ->all();

            if ($identificadores === []) {
                continue;
            }

            DB::table($tabla)
                ->whereIn('id', $identificadores)
                ->update(['persona_id' => $principal->id]);

            $movidos[$tabla] = $identificadores;
        }

        return $movidos;
    }

    /**
     * @return array<int, string> Etiquetas que la principal no tenía.
     */
    private function moverEtiquetas(Persona $principal, Persona $duplicada): array
    {
        $deLaPrincipal = $principal->etiquetas()->pluck('etiqueta')->all();
        $deLaDuplicada = $duplicada->etiquetas()->pluck('etiqueta')->all();

        $nuevas = array_values(array_diff($deLaDuplicada, $deLaPrincipal));

        foreach ($nuevas as $etiqueta) {
            $principal->agregarEtiqueta($etiqueta);
        }

        DB::table('personas_etiquetas')->where('persona_id', $duplicada->id)->delete();

        return $nuevas;
    }
}
