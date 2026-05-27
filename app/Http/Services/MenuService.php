<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Gate;

class MenuService
{
    /**
     * Menu lateral fixo, filtrado pelas gates do PermissaoService.
     *
     * @return list<array<string, mixed>>
     */
    public static function getMenu(): array
    {
        if (! auth()->check()) {
            return [];
        }

        $menu = [
            [
                'menu' => 'Dashboard',
                'route' => 'dashboard',
                'icone' => 'fa-solid fa-house',
                'permissao' => 'sistema',
            ],
            [
                'menu' => 'Sistemas',
                'route' => 'sistema.index',
                'icone' => 'fa-solid fa-server',
                'permissao' => '1.4',
            ],
            [
                'menu' => 'Usuários',
                'icone' => 'fa-solid fa-users',
                'itens' => [
                    [
                        'menu' => 'Usuários',
                        'route' => 'usuario.index',
                        'icone' => 'fa-solid fa-user',
                        'permissao' => '2.4',
                    ],
                    [
                        'menu' => 'Sessões',
                        'route' => 'sessoes.index',
                        'icone' => 'fa-solid fa-clock',
                        'permissao' => '7',
                    ],
                ],
            ],
            [
                'menu' => 'Órgãos',
                'icone' => 'fa-solid fa-building',
                'itens' => [
                    [
                        'menu' => 'Órgãos',
                        'route' => 'orgao.index',
                        'icone' => 'fa-solid fa-building-columns',
                        'permissao' => '3.4',
                    ],
                    [
                        'menu' => 'Lotações',
                        'route' => 'lotacao.index',
                        'icone' => 'fa-solid fa-sitemap',
                        'permissao' => '4.4',
                    ],
                ],
            ],
        ];

        return self::filtrarPorPermissao($menu);
    }

    /**
     * @param  list<array<string, mixed>>  $itens
     * @return list<array<string, mixed>>
     */
    private static function filtrarPorPermissao(array $itens): array
    {
        $filtrados = [];

        foreach ($itens as $item) {
            if (isset($item['itens'])) {
                $subitens = self::filtrarPorPermissao($item['itens']);

                if ($subitens === []) {
                    continue;
                }

                $item['itens'] = $subitens;
                $filtrados[] = $item;

                continue;
            }

            $permissao = $item['permissao'] ?? null;

            if ($permissao === null || Gate::allows($permissao)) {
                $filtrados[] = $item;
            }
        }

        return $filtrados;
    }
}
