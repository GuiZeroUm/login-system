<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserPermission extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'user_permissions';

    protected $fillable = [
        'permission_id',
        'user_lotacao_id',
        'user_sistema_id',
        'tipo',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function permissao(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'permission_id');
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
