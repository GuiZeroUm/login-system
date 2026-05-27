<?php

namespace App\Http\Services;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class PermissaoService
{
    public static function setPermissoes(): void
    {
        Gate::define('administrador', fn (User $user): bool => $user->administrador);

        Gate::define('sistema', function (User $user): bool {
            return Gate::allows('administrador') || $user->lotacoes()
                ->where('status', 'S')
                ->where(function ($q) {
                    $q->whereHas('perfis.role.permissions')
                        ->orWhereHas('permissoes');
                })
                ->exists();
        });

        if (!Schema::hasTable('permissions')) {
            return;
        }

        foreach (Permission::query()->get() as $permissao) {
            if ($permissao->tipo_crud === 'S') {
                foreach ([1, 2, 3, 4] as $tipo) {
                    Gate::define("{$permissao->id}.{$tipo}", fn (User $user): bool => self::checkPermissao($user, $permissao->id, $tipo));
                }
                continue;
            }

            Gate::define((string) $permissao->id, fn (User $user): bool => self::checkPermissao($user, $permissao->id, 0));
        }
    }

    private static function checkPermissao(User $user, int $permissionId, int $tipo): bool
    {
        if (Gate::allows('administrador')) {
            return true;
        }

        $temDireto = $user->lotacoes()
            ->where('status', 'S')
            ->whereHas('permissoes', fn ($q) => $q->where('permission_id', $permissionId)->where('tipo', $tipo))
            ->exists();

        if ($temDireto) {
            return true;
        }

        return $user->lotacoes()
            ->where('status', 'S')
            ->whereHas('perfis.role.permissions', fn ($q) => $q->where('permission_id', $permissionId)->where('tipo', $tipo))
            ->exists();
    }

    public static function getPermissions(): array
    {
        if (!auth()->check() || !Schema::hasTable('permissions')) {
            return [];
        }

        $gates = [];

        foreach (Permission::query()->get() as $permissao) {
            if ($permissao->tipo_crud === 'S') {
                foreach ([1, 2, 3, 4] as $tipo) {
                    $key = "{$permissao->id}.{$tipo}";
                    $gates[$key] = Gate::allows($key);
                }
                continue;
            }

            $key = (string) $permissao->id;
            $gates[$key] = Gate::allows($key);
        }

        $gates['administrador'] = Gate::allows('administrador');
        $gates['sistema'] = Gate::allows('sistema');

        return $gates;
    }
}

