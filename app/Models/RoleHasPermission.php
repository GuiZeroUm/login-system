<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleHasPermission extends Model
{
    use HasFactory;

    protected $table = 'role_has_permissions';

    protected $fillable = [
        'role_id',
        'permission_id',
        'tipo',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function permissao(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'permission_id');
    }
}
