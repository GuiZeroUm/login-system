<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserPerfil extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'user_perfil';

    protected $fillable = [
        'role_id',
        'user_lotacao_id',
        'user_sistema_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function userLotacao(): BelongsTo
    {
        return $this->belongsTo(UserLotacao::class, 'user_lotacao_id');
    }

    public function userSistema(): BelongsTo
    {
        return $this->belongsTo(UserSistema::class, 'user_sistema_id');
    }
}
