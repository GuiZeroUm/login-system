<?php

namespace App\Http\Resources;

use App\Models\Sistema;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function __construct(
        $resource,
        private ?Sistema $sistemaContexto = null,
        private ?array $acessoSistema = null,
    ) {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $vinculos = $this->sistemasAcesso()
            ->with([
                'sistema',
                'perfis.role.sistema',
                'perfis.role.permissions.permissao',
                'permissoes.permissao',
            ])
            ->get();

        $sistemaIds = $vinculos->pluck('sistema_id')->unique()->values();

        $sistemas = Sistema::query()
            ->whereIn('id', $sistemaIds)
            ->when(session('return')?->id, fn ($q) => $q->orWhere('id', session('return')->id))
            ->get();

        $acesso = $this->acessoSistema;

        if ($acesso === null && $this->sistemaContexto) {
            $acesso = \App\Http\Services\Usuario\AcessoSistemaResolver::resolver(
                $this->resource,
                $this->sistemaContexto,
            );
        }

        return [
            'id' => $this->id,
            'nome' => $this->name,
            'email' => $this->email,
            'administrador_global' => (bool) $this->administrador_global,
            'acesso_sistema' => $this->sistemaContexto ? [
                'slug' => $this->sistemaContexto->slug,
                'permitido' => $acesso['permitido'] ?? false,
                'somente_leitura' => $acesso['somente_leitura'] ?? false,
                'administrador_sistema' => $acesso['administrador_sistema'] ?? false,
            ] : null,
            'email_funcional' => null,
            'status_usuario' => $this->status_usuario,
            'login_usuario' => null,
            'usuario_interno' => null,
            'cpf' => null,
            'origem' => null,
            'nivel' => null,
            'foto' => null,
            'ultimo_login' => $this->ultimo_login,
            'acl_token' => session()->getId(),
            'orgaos' => [
                'lotacoes' => UserSistemaAcessoApiResource::collection($vinculos),
            ],
            'sistemas' => SistemaResource::collection($sistemas),
        ];
    }
}

