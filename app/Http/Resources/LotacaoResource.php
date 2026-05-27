<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LotacaoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome_lotacao' => $this->nome_lotacao,
            'sigla_lotacao' => $this->sigla_lotacao,
            'nivel_hierarquico' => $this->nivel_hierarquico,
            'subordinada_id' => $this->subordinada_id,
        ];
    }
}

