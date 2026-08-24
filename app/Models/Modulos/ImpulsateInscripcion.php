<?php

namespace App\Models\Modulos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Persona;

class ImpulsateInscripcion extends Model
{
    use HasFactory;

    protected $table = 'impulsate_inscripciones';

    protected $fillable = [
        'persona_id',
        'programa_id',
        'programa_nombre',
        'fecha_inscripcion',
        'estado',
    ];

    protected $casts = [
        'fecha_inscripcion' => 'datetime',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }
}
