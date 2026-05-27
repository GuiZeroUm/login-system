<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserLotacao extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'user_lotacoes';

    protected $fillable = [
        'user_id',
        'orgao_id',
        'lotacao_id',
        'lotacao_exercicio',
        'administrador',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'lotacao_exercicio' => 'boolean',
            'administrador' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orgao(): BelongsTo
    {
        return $this->belongsTo(Orgao::class);
    }

    public function lotacao(): BelongsTo
    {
        return $this->belongsTo(Lotacao::class);
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
