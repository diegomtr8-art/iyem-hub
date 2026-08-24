<?php

namespace App\Models\Modulos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Persona;

class HerenciaVivaCliente extends Model
{
    use HasFactory;

    protected $table = 'herencia_viva_clientes';

    protected $fillable = [
        'persona_id',
        'numero_cliente',
        'fecha_primer_compra',
        'total_gastado',
        'numero_compras',
        'es_mayorista',
    ];

    protected $casts = [
        'fecha_primer_compra' => 'datetime',
        'es_mayorista' => 'boolean',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }
}
