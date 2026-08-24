<?php

namespace App\Models\Modulos;

use App\Models\Persona;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreaSolicitud extends Model
{
    use HasFactory;

    protected $table = 'crea_solicitudes';

    protected $fillable = [
        'persona_id',
        'monto_solicitado',
        'tipo_credito',
        'estado_solicitud',
        'fecha_solicitud',
    ];

    protected $casts = [
        'fecha_solicitud' => 'datetime',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }
}
