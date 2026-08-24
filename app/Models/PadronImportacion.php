<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PadronImportacion extends Model
{
    protected $table = 'padron_importaciones';

    protected $fillable = [
        'usuario_id',
        'archivo_original',
        'ruta_archivo',
        'ruta_rechazos',
        'total_filas',
        'filas_creadas',
        'filas_actualizadas',
        'filas_rechazadas',
        'mapeo',
        'estado',
        'mensaje',
    ];

    protected function casts(): array
    {
        return [
            'mapeo' => 'array',
            'total_filas' => 'integer',
            'filas_creadas' => 'integer',
            'filas_actualizadas' => 'integer',
            'filas_rechazadas' => 'integer',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function tieneRechazos(): bool
    {
        return $this->filas_rechazadas > 0 && $this->ruta_rechazos !== null;
    }
}
