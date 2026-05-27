<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'sistema_id',
    ];

    public function sistema(): BelongsTo
    {
        return $this->belongsTo(Sistema::class);
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(RoleHasPermission::class);
    }
}
