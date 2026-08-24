<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonaAuditoria extends Model
{
    use HasFactory;

    protected $table = 'personas_auditorias';

    public $timestamps = false;

    protected $fillable = [
        'persona_id',
        'campo_modificado',
        'valor_anterior',
        'valor_nuevo',
        'usuario_id',
        'modulo_origen',
        'fecha_cambio',
    ];

    protected $casts = [
        'fecha_cambio' => 'datetime',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }
}
