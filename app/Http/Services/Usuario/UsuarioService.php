<?php

namespace App\Http\Services\Usuario;

use App\Http\Requests\UsuarioRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Sistema;
use App\Models\User;
use App\Models\UserPerfil;
use App\Models\UserPermission;
use App\Models\UserSistema;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class UsuarioService
{
    public static function salvar(UsuarioRequest $request, ?User $usuario = null): User
    {
        return DB::transaction(function () use ($request, $usuario) {
            $dados = $request->validated();
            $authId = $request->user()?->id;

            if ($usuario === null) {
                $usuario = User::query()->create([
                    'name' => $dados['name'],
                    'email' => $dados['email'],
                    'password' => $dados['password'],
                    'status_usuario' => ($dados['ativo'] ?? true) ? 'S' : 'N',
                    'administrador_global' => (bool) ($dados['administrador_global'] ?? false),
                ]);
            } else {
                $payload = [
                    'name' => $dados['name'],
                    'email' => $dados['email'],
                    'status_usuario' => ($dados['ativo'] ?? true) ? 'S' : 'N',
                    'administrador_global' => (bool) ($dados['administrador_global'] ?? false),
                ];

                if (! empty($dados['password'])) {
                    $payload['password'] = $dados['password'];
                }

                $usuario->update($payload);
            }

            if ($usuario->administrador_global) {
                self::inativarSistemas($usuario, $authId);

                return $usuario->fresh();
            }

            self::sincronizarAcessos($usuario, $dados['acessos'] ?? [], $authId);

            return $usuario->fresh();
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $acessos
     */
    public static function sincronizarAcessos(User $usuario, array $acessos, ?int $authId): void
    {
        $idsMantidos = [];

        foreach ($acessos as $acesso) {
            $sistema = Sistema::query()->findOrFail($acesso['sistema_id']);

            if (! $sistema->ativo) {
                throw new InvalidArgumentException("O sistema «{$sistema->nome}» está inativo.");
            }

            $vinculo = UserSistema::query()->updateOrCreate(
                [
                    'user_id' => $usuario->id,
                    'sistema_id' => $sistema->id,
                ],
                [
                    'administrador' => (bool) ($acesso['administrador_sistema'] ?? false),
                    'status' => 'S',
                    'created_by' => $authId,
                    'updated_by' => $authId,
                ],
            );

            $idsMantidos[] = $vinculo->id;

            self::sincronizarPerfis($vinculo, $acesso['perfis_ids'] ?? [], $sistema->id, $authId);
            self::sincronizarPermissoesDiretas($vinculo, $acesso['permissoes'] ?? [], $sistema->id, $authId);
        }

        UserSistema::query()
            ->where('user_id', $usuario->id)
            ->whereNotIn('id', $idsMantidos)
            ->update([
                'status' => 'N',
                'updated_by' => $authId,
            ]);
    }

    /**
     * @param  array<int, int>  $perfisIds
     */
    private static function sincronizarPerfis(UserSistema $vinculo, array $perfisIds, int $sistemaId, ?int $authId): void
    {
        $rolesValidos = Role::query()
            ->where('sistema_id', $sistemaId)
            ->whereIn('id', $perfisIds)
            ->pluck('id');

        UserPerfil::query()
            ->where('user_sistema_id', $vinculo->id)
            ->whereHas('role', fn ($q) => $q->where('sistema_id', $sistemaId))
            ->whereNotIn('role_id', $rolesValidos)
            ->get()
            ->each(fn (UserPerfil $p) => $p->delete());

        foreach ($rolesValidos as $roleId) {
            UserPerfil::query()->updateOrCreate(
                [
                    'user_sistema_id' => $vinculo->id,
                    'role_id' => $roleId,
                ],
                [
                    'user_lotacao_id' => null,
                    'created_by' => $authId,
                    'updated_by' => $authId,
                ],
            );
        }
    }

    /**
     * @param  array<int, array{permission_id: int, tipo: int}>  $permissoes
     */
    private static function sincronizarPermissoesDiretas(UserSistema $vinculo, array $permissoes, int $sistemaId, ?int $authId): void
    {
        $permissionIds = Permission::query()
            ->where('sistema_id', $sistemaId)
            ->pluck('id');

        UserPermission::query()
            ->where('user_sistema_id', $vinculo->id)
            ->whereHas('permissao', fn ($q) => $q->where('sistema_id', $sistemaId))
            ->get()
            ->each(fn (UserPermission $p) => $p->delete());

        foreach ($permissoes as $item) {
            if (! $permissionIds->contains($item['permission_id'])) {
                continue;
            }

            UserPermission::query()->updateOrCreate(
                [
                    'user_sistema_id' => $vinculo->id,
                    'permission_id' => $item['permission_id'],
                    'tipo' => $item['tipo'],
                ],
                [
                    'user_lotacao_id' => null,
                    'created_by' => $authId,
                    'updated_by' => $authId,
                ],
            );
        }
    }

    private static function inativarSistemas(User $usuario, ?int $authId): void
    {
        UserSistema::query()
            ->where('user_id', $usuario->id)
            ->update([
                'status' => 'N',
                'updated_by' => $authId,
            ]);
    }

    public static function excluir(User $usuario, ?int $authId): void
    {
        DB::transaction(function () use ($usuario, $authId) {
            UserSistema::query()
                ->where('user_id', $usuario->id)
                ->update([
                    'status' => 'N',
                    'updated_by' => $authId,
                ]);

            $usuario->delete();
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function serializarAcessos(User $usuario): array
    {
        return $usuario->sistemasAcessoTodos()
            ->where('status', 'S')
            ->with(['sistema', 'perfis.role', 'permissoes.permissao'])
            ->get()
            ->map(function (UserSistema $vinculo) {
                $sistemaId = $vinculo->sistema_id;

                return [
                    'sistema_id' => $sistemaId,
                    'administrador_sistema' => (bool) $vinculo->administrador,
                    'perfis_ids' => $vinculo->perfis
                        ->filter(fn ($p) => $p->role?->sistema_id === $sistemaId)
                        ->pluck('role_id')
                        ->values()
                        ->all(),
                    'permissoes' => $vinculo->permissoes
                        ->filter(fn ($up) => $up->permissao?->sistema_id === $sistemaId)
                        ->map(fn ($up) => [
                            'permission_id' => $up->permission_id,
                            'tipo' => $up->tipo,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function dadosFormulario(): array
    {
        $sistemas = Sistema::query()
            ->where('ativo', true)
            ->orderBy('nome')
            ->get(['id', 'nome', 'slug', 'ambiente']);

        $catalogo = [];

        foreach ($sistemas as $sistema) {
            $catalogo[$sistema->id] = [
                'roles' => Role::query()
                    ->where('sistema_id', $sistema->id)
                    ->orderBy('name')
                    ->get(['id', 'name']),
                'permissions' => Permission::query()
                    ->where('sistema_id', $sistema->id)
                    ->orderBy('name')
                    ->get(['id', 'name', 'tipo_crud']),
            ];
        }

        return [
            'sistemas' => $sistemas,
            'catalogo' => $catalogo,
        ];
    }
}
