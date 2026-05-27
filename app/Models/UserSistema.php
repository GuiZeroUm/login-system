<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserSistema extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'sistema_id',
        'administrador',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'administrador' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sistema(): BelongsTo
    {
        return $this->belongsTo(Sistema::class);
    }

    public function perfis(): HasMany
    {
        return $this->hasMany(UserPerfil::class)->withoutTrashed();
    }

    public function permissoes(): HasMany
    {
        return $this->hasMany(UserPermission::class)->withoutTrashed();
    }
}
