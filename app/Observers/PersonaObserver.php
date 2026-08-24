<?php

namespace App\Observers;

use App\Models\Persona;

class PersonaObserver
{
    public function created(Persona $persona): void
    {
        $persona->marcarAuditoria(
            campo: '__creado__',
            valor_anterior: null,
            valor_nuevo: $persona->creado_por_modulo,
            usuario_id: auth()->id(),
            modulo: $persona->creado_por_modulo
        );
    }

    public function updated(Persona $persona): void
    {
        foreach ($persona->getChanges() as $campo => $valorNuevo) {
            if (in_array($campo, ['updated_at'], true)) {
                continue;
            }

            $persona->marcarAuditoria(
                campo: $campo,
                valor_anterior: (string) $persona->getOriginal($campo),
                valor_nuevo: (string) $valorNuevo,
                usuario_id: auth()->id(),
                modulo: $persona->creado_por_modulo
            );
        }
    }

    public function deleted(Persona $persona): void
    {
        $persona->marcarAuditoria(
            campo: '__eliminado__',
            valor_anterior: 'activa',
            valor_nuevo: $persona->trashed() ? 'eliminada_logica' : 'eliminada_permanente',
            usuario_id: auth()->id(),
            modulo: $persona->creado_por_modulo
        );
    }
}
