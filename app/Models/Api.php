<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Api extends Model
{
    use HasFactory;

    protected $table = 'apis';

    protected $fillable = [
        'nome',
        'token',
        'sistema_id',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    public function sistema(): BelongsTo
    {
        return $this->belongsTo(Sistema::class);
    }
}
