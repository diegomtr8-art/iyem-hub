<?php

namespace App\Models\Modulos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Persona;

class NodicoMembresia extends Model
{
    use HasFactory;

    protected $table = 'nodico_membresias';

    protected $fillable = [
        'persona_id',
        'tipo_membresia',
        'estado_membresia',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }
}
