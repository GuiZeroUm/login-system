<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SistemaBanco extends Model
{
    use HasFactory;

    protected $fillable = [
        'sistema_id',
        'tipo',
        'host',
        'porta',
        'nome_banco',
        'usuario',
        'senha',
    ];

    protected function casts(): array
    {
        return [
            'porta' => 'integer',
        ];
    }

    public function sistema(): BelongsTo
    {
        return $this->belongsTo(Sistema::class);
    }
}

