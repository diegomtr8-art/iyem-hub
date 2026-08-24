<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonaEtiqueta extends Model
{
    protected $table = 'personas_etiquetas';

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = null;

    protected $fillable = [
        'persona_id',
        'etiqueta',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }
}
