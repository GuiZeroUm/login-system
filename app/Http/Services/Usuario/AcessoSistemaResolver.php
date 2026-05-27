<?php

namespace App\Http\Services\Usuario;

use App\Models\Sistema;
use App\Models\User;
use Illuminate\Support\Collection;

class AcessoSistemaResolver
{
    /**
     * @return array{permitido: bool, somente_leitura: bool, administrador_sistema: bool}
     */
    public static function resolver(User $user, Sistema $sistema): array
    {
        if ($user->administrador_global) {
            return [
                'permitido' => true,
                'somente_leitura' => false,
                'administrador_sistema' => true,
            ];
        }

        $vinculo = $user->sistemasAcesso()
            ->where('sistema_id', $sistema->id)
            ->with(['perfis.role', 'permissoes.permissao'])
            ->first();

        if ($vinculo === null) {
            return [
                'permitido' => false,
                'somente_leitura' => false,
                'administrador_sistema' => false,
            ];
        }

        if ($vinculo->administrador) {
            return [
                'permitido' => true,
                'somente_leitura' => false,
                'administrador_sistema' => true,
            ];
        }

        $temPermissaoExplicita = $vinculo->perfis->isNotEmpty() || $vinculo->permissoes->isNotEmpty();

        return [
            'permitido' => true,
            'somente_leitura' => ! $temPermissaoExplicita,
            'administrador_sistema' => false,
        ];
    }

    public static function resumoAcessos(User $user): string
    {
        if ($user->administrador_global) {
            return 'Administrador global';
        }

        $sistemas = self::sistemasComAcesso($user);

        if ($sistemas->isEmpty()) {
            return 'Nenhum sistema';
        }

        $nomes = $sistemas->pluck('nome')->take(2)->all();

        if ($sistemas->count() > 2) {
            return $nomes[0].' +'.($sistemas->count() - 1);
        }

        return implode(', ', $nomes);
    }

    /**
     * @return Collection<int, Sistema>
     */
    public static function sistemasComAcesso(User $user): Collection
    {
        if ($user->administrador_global) {
            return Sistema::query()->where('ativo', true)->orderBy('nome')->get();
        }

        return Sistema::query()
            ->where('ativo', true)
            ->whereIn('id', $user->sistemasAcesso()->pluck('sistema_id'))
            ->orderBy('nome')
            ->get();
    }
}
