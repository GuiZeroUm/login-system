<?php

namespace Database\Seeders;

use App\Models\Orgao;
use App\Models\Sistema;
use App\Models\User;
use App\Models\UserLotacao;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CoreSeeder extends Seeder
{
    public function run(): void
    {
        $sistema = Sistema::query()->firstWhere('slug', 'login');

        if (! $sistema) {
            $sistema = Sistema::query()->create([
                'slug' => 'login',
                'nome' => 'Login Universal',
                'url' => config('app.url'),
                'url_logout' => config('app.url').'/logout/login',
                'descricao' => 'Sistema centralizado de autenticação e controle de acesso.',
                'ambiente' => 'production',
                'ativo' => true,
            ]);
        }

        $permissoes = [
            ['id' => 1, 'name' => 'Sistemas', 'tipo_crud' => 'S'],
            ['id' => 2, 'name' => 'Usuários', 'tipo_crud' => 'S'],
            ['id' => 3, 'name' => 'Órgãos', 'tipo_crud' => 'S'],
            ['id' => 4, 'name' => 'Lotações', 'tipo_crud' => 'S'],
            ['id' => 5, 'name' => 'Perfis', 'tipo_crud' => 'S'],
            ['id' => 6, 'name' => 'Permissões', 'tipo_crud' => 'S'],
            ['id' => 7, 'name' => 'Sessões', 'tipo_crud' => 'N'],
        ];

        foreach ($permissoes as $p) {
            DB::table('permissions')->updateOrInsert(
                ['id' => $p['id']],
                [
                    'name' => $p['name'],
                    'tipo_crud' => $p['tipo_crud'],
                    'sistema_id' => $sistema->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        $orgao = Orgao::query()->updateOrCreate(
            ['descricao_orgao' => 'Organização Principal'],
            [
                'sigla_orgao' => 'PRINCIPAL',
                'status' => 'ativo',
            ],
        );

        $user = User::query()->updateOrCreate(
            ['email' => 'admin@login.app'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('admin123'),
                'status_usuario' => 'S',
                'administrador_global' => true,
            ],
        );

        UserLotacao::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'orgao_id' => $orgao->id,
                'lotacao_id' => null,
            ],
            [
                'administrador' => false,
                'lotacao_exercicio' => true,
                'status' => 'S',
                'created_by' => $user->id,
            ],
        );
    }
}

