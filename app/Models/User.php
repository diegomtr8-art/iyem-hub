<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use HasRoles;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'apellido',
        'email',
        'password',
        'estado',
        'expira_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
        'nombre_completo',
        'rol_actual',
        'es_super_admin',
        'es_tester',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'estado' => 'boolean',
            'last_login' => 'datetime',
            'expira_at' => 'datetime',
        ];
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->name} {$this->apellido}");
    }

    public function esSuperAdmin(): bool
    {
        return $this->hasRole('Super Admin');
    }

    public function getRolActualAttribute(): ?string
    {
        return $this->getRoleNames()->first();
    }

    public function getEsSuperAdminAttribute(): bool
    {
        return $this->esSuperAdmin();
    }

    public function esTester(): bool
    {
        return $this->hasRole('Tester');
    }

    public function getEsTesterAttribute(): bool
    {
        return $this->esTester();
    }

    /**
     * Una cuenta sin `expira_at` no caduca. Con fecha, deja de ser válida
     * en cuanto esa fecha queda atrás.
     */
    public function vigente(): bool
    {
        return $this->expira_at === null || $this->expira_at->isFuture();
    }
}
