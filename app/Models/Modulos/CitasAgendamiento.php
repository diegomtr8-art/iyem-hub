<?php

namespace App\Models\Modulos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Persona;

class CitasAgendamiento extends Model
{
    use HasFactory;

    protected $table = 'citas_agendamientos';

    protected $fillable = [
        'persona_id',
        'tipo_cita',
        'fecha_cita',
        'estado',
        'modulo_destino',
    ];

    protected $casts = [
        'fecha_cita' => 'datetime',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }
}
