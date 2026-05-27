<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Formato compatível com integrações que esperam orgaos.lotacoes[].
 */
class UserSistemaAcessoApiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $sistema = $this->sistema;

        return [
            'id' => $this->id,
            'administrador' => (bool) $this->administrador,
            'lotacao_exercicio' => true,
            'status' => $this->status,
            'orgao' => [
                'id' => $sistema->id,
                'descricao_orgao' => $sistema->nome,
                'sigla_orgao' => $sistema->slug,
                'status' => 'ativo',
            ],
            'lotacao' => null,
            'perfis' => UserPerfilResource::collection($this->whenLoaded('perfis')),
            'permissoes' => UserPermissionResource::collection($this->whenLoaded('permissoes')),
        ];
    }
}
