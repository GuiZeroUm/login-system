<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrgaoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'descricao_orgao' => $this->descricao_orgao,
            'sigla_orgao' => $this->sigla_orgao,
            'cnpj' => $this->cnpj,
            'status' => $this->status,
        ];
    }
}

