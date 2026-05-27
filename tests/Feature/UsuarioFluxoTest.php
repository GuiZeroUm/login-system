<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\Sistema;
use App\Http\Services\PermissaoService;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

function usuarioAdminComPermissoes(): User
{
    $sistema = Sistema::query()->firstOrCreate(
        ['slug' => 'login'],
        [
            'nome' => 'Login Universal',
            'url' => 'https://login.test',
            'ambiente' => 'production',
            'ativo' => true,
        ],
    );

    foreach ([
        ['id' => 1, 'name' => 'Sistemas', 'tipo_crud' => 'S'],
        ['id' => 2, 'name' => 'Usuários', 'tipo_crud' => 'S'],
    ] as $p) {
        Permission::query()->updateOrCreate(
            ['id' => $p['id'], 'sistema_id' => $sistema->id],
            ['name' => $p['name'], 'tipo_crud' => $p['tipo_crud']],
        );
    }

    $user = User::factory()->create([
        'email' => 'admin-usuario@test.app',
        'password' => Hash::make('senha123'),
        'administrador_global' => true,
    ]);

    PermissaoService::setPermissoes();

    return $user;
}

beforeEach(function () {
    $this->admin = usuarioAdminComPermissoes();
    $this->actingAs($this->admin);
});

it('GET /usuario lista usuários', function () {
    User::factory()->create(['name' => 'Maria Teste']);

    $response = $this->get('/usuario');

    $response->assertOk();
});

it('POST /usuario cria usuário com acesso somente leitura', function () {
    $sistema = Sistema::query()->create([
        'nome' => 'Sistema Teste',
        'slug' => 'sistema-teste-crud',
        'url' => 'https://teste.test/login',
        'ambiente' => 'desenvolvimento',
        'ativo' => true,
    ]);

    $response = $this->post('/usuario', [
        'name' => 'João Silva',
        'email' => 'joao.silva@test.app',
        'password' => 'senha12345',
        'ativo' => true,
        'administrador_global' => false,
        'acessos' => [
            [
                'sistema_id' => $sistema->id,
                'administrador_sistema' => false,
                'perfis_ids' => [],
                'permissoes' => [],
            ],
        ],
    ]);

    $response->assertRedirect(route('usuario.index'));

    $user = User::query()->where('email', 'joao.silva@test.app')->first();
    expect($user)->not->toBeNull();
    expect($user->sistemasAcesso)->toHaveCount(1);
    expect($user->sistemasAcesso->first()->perfis)->toHaveCount(0);
});

it('POST /usuario com administrador global não exige acessos', function () {
    $response = $this->post('/usuario', [
        'name' => 'Admin Local',
        'email' => 'admin.local@test.app',
        'password' => 'senha12345',
        'ativo' => true,
        'administrador_global' => true,
        'acessos' => [],
    ]);

    $response->assertRedirect(route('usuario.index'));

    expect(User::query()->where('email', 'admin.local@test.app')->value('administrador_global'))->toBeTrue();
});

it('DELETE /usuario exclui usuário', function () {
    $user = User::factory()->create();

    $response = $this->delete("/usuario/{$user->id}");

    $response->assertRedirect(route('usuario.index'));
    expect(User::query()->find($user->id))->toBeNull();
    expect(User::withTrashed()->find($user->id))->not->toBeNull();
});

it('POST /usuario sem acessos e sem admin global retorna erro de validação', function () {
    $response = $this->from('/usuario/create')->post('/usuario', [
        'name' => 'Sem Acesso',
        'email' => 'sem.acesso@test.app',
        'password' => 'senha12345',
        'ativo' => true,
        'administrador_global' => false,
        'acessos' => [],
    ]);

    $response->assertSessionHasErrors('acessos');
});
