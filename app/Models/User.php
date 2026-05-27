<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'status_usuario',
        'administrador_global',
        'ultimo_login',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'ultimo_login' => 'datetime',
            'administrador_global' => 'boolean',
        ];
    }

    public function lotacoes(): HasMany
    {
        return $this->hasMany(UserLotacao::class)
            ->where('status', 'S')
            ->withoutTrashed();
    }

    public function getAdministradorAttribute(): bool
    {
        return (bool) $this->administrador_global;
    }

    public function lotacoesTodas(): HasMany
    {
        return $this->hasMany(UserLotacao::class)->withoutTrashed();
    }

    public function sistemasAcesso(): HasMany
    {
        return $this->hasMany(UserSistema::class)
            ->where('status', 'S')
            ->withoutTrashed();
    }

    public function sistemasAcessoTodos(): HasMany
    {
        return $this->hasMany(UserSistema::class)->withoutTrashed();
    }
}
