<?php

namespace Database\Seeders;

use App\Models\Sistema;
use Illuminate\Database\Seeder;

class LoginUniversalSistemaSeeder extends Seeder
{
    public function run(): void
    {
        Sistema::query()->updateOrCreate(
            ['slug' => 'login'],
            [
                'nome' => 'Login Universal',
                'url' => config('app.url'),
                'url_logout' => config('app.url').'/logout/login',
                'descricao' => 'Sistema centralizado de autenticação e controle de acesso.',
                'login_nome' => 'Login Universal',
                'tema_login' => 'escuro',
                'login_subtitulo' => 'Use sua conta corporativa para acessar.',
                'login_painel_eyebrow' => 'VOCÊ ESTÁ ENTRANDO EM',
                'login_painel_titulo' => 'Login Universal',
                'login_painel_descricao' => 'Sistema centralizado de autenticação e controle de acesso.',
                'exibir_logo_topo' => true,
                'exibir_bloco_inferior' => true,
                'exibir_degrade_ilustracao' => true,
                'ambiente' => 'production',
                'ativo' => true,
            ],
        );
    }
}

