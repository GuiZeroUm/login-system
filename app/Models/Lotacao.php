<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lotacao extends Model
{
    use HasFactory;

    protected $table = 'lotacoes';

    protected $fillable = [
        'nome_lotacao',
        'sigla_lotacao',
        'orgao_id',
        'nivel_hierarquico',
        'subordinada_id',
    ];

    public function orgao(): BelongsTo
    {
        return $this->belongsTo(Orgao::class);
    }

    public function subordinada(): BelongsTo
    {
        return $this->belongsTo(self::class, 'subordinada_id');
    }

    public function subordinadas(): HasMany
    {
        return $this->hasMany(self::class, 'subordinada_id');
    }
}
