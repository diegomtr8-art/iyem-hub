<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventoModulo extends Model
{
    protected $table = 'eventos_modulo';

    protected $fillable = [
        'persona_id',
        'modulo',
        'tipo',
        'titulo',
        'detalle',
        'estado',
        'referencia_externa',
        'carga',
        'ocurrio_at',
        'sistema_id',
    ];

    protected function casts(): array
    {
        return [
            'carga' => 'array',
            'ocurrio_at' => 'datetime',
        ];
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    public function sistema(): BelongsTo
    {
        return $this->belongsTo(SistemaIntegrado::class, 'sistema_id');
    }
}
