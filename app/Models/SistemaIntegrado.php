<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable as PuedeAutenticarse;
use Illuminate\Contracts\Auth\Authenticatable as ContratoAutenticable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

/**
 * Un sistema satélite autorizado a consumir la API del padrón.
 *
 * Es el "usuario" que Sanctum devuelve en las rutas de `/api/v1`: quien
 * consulta el padrón es el sistema, no la persona que lo opera.
 *
 * Implementa `Authenticatable` porque el guard de Sanctum exige el contrato
 * para poder fijarlo como usuario de la petición. Los métodos de contraseña
 * y de "recuérdame" que trae el trait quedan sin uso: este modelo solo se
 * autentica con token.
 */
class SistemaIntegrado extends Model implements ContratoAutenticable
{
    use HasApiTokens;
    use PuedeAutenticarse;

    protected $table = 'sistemas_integrados';

    protected $fillable = [
        'nombre',
        'slug',
        'url_base',
        'contacto',
        'activo',
        'ultimo_ping',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'ultimo_ping' => 'datetime',
        ];
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(EventoModulo::class, 'sistema_id');
    }

    /**
     * Deja constancia de que el sistema sigue vivo. Se llama en cada
     * petición autenticada, sin tocar `updated_at`.
     */
    public function registrarPing(): void
    {
        $this->forceFill(['ultimo_ping' => now()])->saveQuietly();
    }
}
