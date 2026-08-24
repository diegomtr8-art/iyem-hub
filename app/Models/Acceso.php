<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Acceso extends Model
{
    protected $fillable = [
        'user_id',
        'modulo',
        'ip_address',
        'accedido_at',
    ];

    protected function casts(): array
    {
        return [
            'accedido_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
