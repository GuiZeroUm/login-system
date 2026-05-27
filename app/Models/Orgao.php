<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Orgao extends Model
{
    use HasFactory;

    protected $table = 'orgaos';

    protected $fillable = [
        'descricao_orgao',
        'sigla_orgao',
        'cnpj',
        'status',
    ];

    public function lotacoes(): HasMany
    {
        return $this->hasMany(Lotacao::class);
    }

    public function sistemas(): BelongsToMany
    {
        return $this->belongsToMany(Sistema::class, 'orgao_sistema');
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(UserLotacao::class);
    }
}
