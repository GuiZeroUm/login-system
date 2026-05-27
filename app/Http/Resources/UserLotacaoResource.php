<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserLotacaoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'administrador' => (bool) $this->administrador,
            'lotacao_exercicio' => (bool) $this->lotacao_exercicio,
            'status' => $this->status,
            'orgao' => new OrgaoResource($this->whenLoaded('orgao')),
            'lotacao' => $this->lotacao_id ? new LotacaoResource($this->whenLoaded('lotacao')) : null,
            'perfis' => UserPerfilResource::collection($this->whenLoaded('perfis')),
            'permissoes' => UserPermissionResource::collection($this->whenLoaded('permissoes')),
        ];
    }
}

