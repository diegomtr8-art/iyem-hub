<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonaFusion extends Model
{
    protected $table = 'personas_fusiones';

    protected $fillable = [
        'principal_id',
        'duplicada_id',
        'snapshot_principal',
        'snapshot_duplicada',
        'vinculos_movidos',
        'etiquetas_movidas',
        'campos_completados',
        'usuario_id',
        'criterio',
        'motivo',
        'revertible_hasta',
        'revertida_at',
        'revertida_por',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_principal' => 'array',
            'snapshot_duplicada' => 'array',
            'vinculos_movidos' => 'array',
            'etiquetas_movidas' => 'array',
            'campos_completados' => 'array',
            'revertible_hasta' => 'datetime',
            'revertida_at' => 'datetime',
        ];
    }

    public function principal(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'principal_id');
    }

    public function duplicada(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'duplicada_id')->withTrashed();
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /** ¿Todavía se puede deshacer? */
    public function esRevertible(): bool
    {
        return $this->revertida_at === null && $this->revertible_hasta->isFuture();
    }

    public function scopeVigentes($query)
    {
        return $query->whereNull('revertida_at')->where('revertible_hasta', '>', now());
    }
}
