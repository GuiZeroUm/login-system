<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserPermissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'permission' => new PermissionResource($this->whenLoaded('permissao')),
            'tipo' => $this->tipo,
        ];
    }
}

