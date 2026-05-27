<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sistema_id',
        'tipo_crud',
    ];

    public function sistema(): BelongsTo
    {
        return $this->belongsTo(Sistema::class);
    }
}
