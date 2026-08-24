<?php

namespace App\Models\Modulos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Persona;

class JuridicoAsesoria extends Model
{
    use HasFactory;

    protected $table = 'juridico_asesorias';

    protected $fillable = [
        'persona_id',
        'tipo_asesoria',
        'fecha_asesoria',
        'estado',
        'notas',
    ];

    protected $casts = [
        'fecha_asesoria' => 'datetime',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }
}
