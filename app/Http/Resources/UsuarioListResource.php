<?php

namespace App\Http\Resources;

use App\Http\Services\Usuario\AcessoSistemaResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->name,
            'email' => $this->email,
            'status' => $this->status_usuario === 'S' ? 'Ativo' : 'Inativo',
            'administrador_global' => (bool) $this->administrador_global,
            'acesso' => AcessoSistemaResolver::resumoAcessos($this->resource),
            'ultimo_login' => $this->ultimo_login?->diffForHumans(),
        ];
    }
}
