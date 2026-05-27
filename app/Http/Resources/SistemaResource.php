<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SistemaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'slug' => $this->slug,
            'url' => $this->url,
            'url_logout' => $this->url_logout,
            'ambiente' => $this->ambiente,
            'descricao' => $this->descricao,
            'login_nome' => $this->login_nome,
            'tema_login' => $this->tema_login ?? 'escuro',
            'login_subtitulo' => $this->login_subtitulo,
            'login_painel_eyebrow' => $this->login_painel_eyebrow,
            'login_painel_titulo' => $this->login_painel_titulo,
            'login_painel_descricao' => $this->login_painel_descricao,
            'exibir_logo_topo' => $this->exibir_logo_topo ?? true,
            'exibir_bloco_inferior' => $this->exibir_bloco_inferior ?? true,
            'exibir_degrade_ilustracao' => $this->exibir_degrade_ilustracao ?? true,
            'caminho_logo' => $this->caminho_logo,
            'caminho_logo_url' => $this->caminho_logo ? asset('storage/'.$this->caminho_logo) : null,
            'caminho_ilustracao' => $this->caminho_ilustracao,
            'caminho_ilustracao_url' => $this->caminho_ilustracao ? asset('storage/'.$this->caminho_ilustracao) : null,
            'ativo' => (bool) $this->ativo,
            'permissions_synced_at' => $this->permissions_synced_at?->toIso8601String(),
            'permissions_count' => $this->whenCounted('permissions'),
            'roles_count' => $this->whenCounted('roles'),
            'banco' => $this->whenLoaded('banco', fn () => [
                'tipo' => $this->banco?->tipo,
                'host' => $this->banco?->host,
                'porta' => $this->banco?->porta,
                'nome_banco' => $this->banco?->nome_banco,
                'usuario' => $this->banco?->usuario,
                // Não retornamos senha descriptografada por segurança.
                'senha' => '',
            ]),
            'orgaos_ids' => $this->whenLoaded('orgaos', fn () => $this->orgaos->pluck('id')->values()->all()),
            'perfis' => $this->whenLoaded('roles', fn () => $this->roles->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'permissoes' => $role->permissions->map(fn ($item) => [
                    'permission_id' => $item->permission_id,
                    'tipo' => $item->tipo,
                ])->values()->all(),
            ])->values()->all()),
        ];
    }
}

