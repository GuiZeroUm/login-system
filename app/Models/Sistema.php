<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sistema extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'slug',
        'url',
        'url_logout',
        'ambiente',
        'descricao',
        'login_nome',
        'tema_login',
        'login_subtitulo',
        'login_painel_eyebrow',
        'login_painel_titulo',
        'login_painel_descricao',
        'exibir_logo_topo',
        'exibir_bloco_inferior',
        'exibir_degrade_ilustracao',
        'caminho_logo',
        'caminho_ilustracao',
        'ativo',
        'permissions_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'exibir_logo_topo' => 'boolean',
            'exibir_bloco_inferior' => 'boolean',
            'exibir_degrade_ilustracao' => 'boolean',
            'permissions_synced_at' => 'datetime',
        ];
    }

    public function orgaos(): BelongsToMany
    {
        return $this->belongsToMany(Orgao::class, 'orgao_sistema');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }

    public function apis(): HasMany
    {
        return $this->hasMany(Api::class);
    }

    public function banco(): HasOne
    {
        return $this->hasOne(SistemaBanco::class);
    }

    public function scopeSearch($query, ?string $search): void
    {
        if ($search) {
            $query->where('nome', 'like', '%'.$search.'%');
        }
    }

    public function scopeAmbiente($query, ?string $ambiente): void
    {
        if ($ambiente) {
            $query->where('ambiente', $ambiente);
        }
    }
}
