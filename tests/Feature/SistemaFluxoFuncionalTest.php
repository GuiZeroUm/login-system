<?php

use App\Models\Orgao;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Sistema;
use App\Models\SistemaBanco;
use App\Models\User;
use App\Http\Services\PermissaoService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function adminSistema(): User
{
    test()->seed(DatabaseSeeder::class);
    PermissaoService::setPermissoes();

    /** @var User $user */
    $user = User::query()->where('email', 'admin@login.app')->firstOrFail();

    return $user;
}

it('garante que o sistema padrão login universal existe via seed', function () {
    adminSistema();

    $sistema = Sistema::query()->where('slug', 'login')->first();

    expect($sistema)->not->toBeNull();
    expect($sistema->nome)->toBe('Login Universal');
    expect($sistema->ativo)->toBeTrue();
});

it('cria sistema apenas com dados básicos sem enviar banco vazio', function () {
    $admin = adminSistema();

    $response = $this->actingAs($admin)->post('/sistema', [
        'nome' => 'Sistema Sem Banco',
        'slug' => 'sem-banco',
        'url' => 'https://sem-banco.exemplo.local',
        'ambiente' => 'desenvolvimento',
        'ativo' => true,
    ]);

    $response->assertRedirect('/sistema');
    expect(Sistema::query()->where('slug', 'sem-banco')->exists())->toBeTrue();
});

it('cria sistema com branding, conexão de banco e relacionamento com orgãos', function () {
    Storage::fake('public');
    $admin = adminSistema();

    $orgao = Orgao::query()->create([
        'descricao_orgao' => 'Secretaria de Teste',
        'sigla_orgao' => 'SET',
        'status' => 'ativo',
    ]);

    $response = $this->actingAs($admin)->post('/sistema', [
        'nome' => 'Sistema Financeiro',
        'slug' => 'financeiro',
        'url' => 'https://financeiro.exemplo.local',
        'url_logout' => 'https://financeiro.exemplo.local/logout',
        'ambiente' => 'production',
        'descricao' => 'Módulo financeiro',
        'ativo' => true,
        'upload_caminho_logo' => UploadedFile::fake()->image('logo.png'),
        'upload_caminho_ilustracao' => UploadedFile::fake()->image('ilustracao.png'),
        'banco' => [
            'tipo' => 'postgresql',
            'host' => '10.1.1.10',
            'porta' => 5432,
            'nome_banco' => 'financeiro_db',
            'usuario' => 'financeiro_user',
            'senha' => 'segredo123',
        ],
        'orgaos_ids' => [$orgao->id],
    ]);

    $response->assertRedirect('/sistema');

    $sistema = Sistema::query()->where('slug', 'financeiro')->firstOrFail();

    expect($sistema->caminho_logo)->not->toBeNull();
    expect($sistema->caminho_ilustracao)->not->toBeNull();
    $banco = SistemaBanco::query()->where('sistema_id', $sistema->id)->first();
    expect($banco)->not->toBeNull();
    expect($banco->host)->toBe('10.1.1.10');
    expect($banco->nome_banco)->toBe('financeiro_db');

    $this->assertDatabaseHas('orgao_sistema', [
        'orgao_id' => $orgao->id,
        'sistema_id' => $sistema->id,
    ]);
});

it('atualiza sistema existente carregando e persistindo perfis/permissoes', function () {
    $admin = adminSistema();

    $create = $this->actingAs($admin)->post('/sistema', [
        'nome' => 'Sistema RH',
        'slug' => 'rh',
        'url' => 'https://rh.exemplo.local',
        'ambiente' => 'homologacao',
        'ativo' => true,
    ]);
    $create->assertRedirect('/sistema');

    $sistema = Sistema::query()->where('slug', 'rh')->firstOrFail();
    $permissao = Permission::query()->create([
        'sistema_id' => $sistema->id,
        'name' => 'Permissão RH',
        'tipo_crud' => 'N',
    ]);

    $orgao = Orgao::query()->create([
        'descricao_orgao' => 'Secretaria RH',
        'sigla_orgao' => 'SRH',
        'status' => 'ativo',
    ]);

    $update = $this->actingAs($admin)->put("/sistema/{$sistema->id}", [
        'nome' => 'Sistema RH Atualizado',
        'slug' => 'rh',
        'url' => 'https://rh.exemplo.local',
        'url_logout' => '',
        'ambiente' => 'production',
        'descricao' => 'Atualizado',
        'ativo' => true,
        'banco' => [
            'tipo' => 'postgresql',
            'host' => '10.20.0.2',
            'porta' => 5432,
            'nome_banco' => 'rh_db',
            'usuario' => 'rh_user',
            'senha' => 'rh_secret',
        ],
        'orgaos_ids' => [$orgao->id],
        'perfis' => [
            [
                'name' => 'Gestor RH',
                'permissoes' => [
                    ['permission_id' => $permissao->id, 'tipo' => 0],
                ],
            ],
        ],
    ]);

    $update->assertRedirect('/sistema');

    $sistema->refresh();
    expect($sistema->nome)->toBe('Sistema RH Atualizado');

    $perfil = Role::query()->where('sistema_id', $sistema->id)->where('name', 'Gestor RH')->first();
    expect($perfil)->not->toBeNull();

    $this->assertDatabaseHas('role_has_permissions', [
        'role_id' => $perfil->id,
        'permission_id' => $permissao->id,
        'tipo' => 0,
    ]);
    $this->assertDatabaseHas('orgao_sistema', [
        'orgao_id' => $orgao->id,
        'sistema_id' => $sistema->id,
    ]);

    $response = $this
        ->actingAs($admin)
        ->withHeaders($this->inertiaHeaders())
        ->get("/sistema/{$sistema->id}/edit");

    $response->assertOk();
    $response->assertJsonPath('props.dados.id', $sistema->id);
    $this->assertDatabaseHas('sistema_bancos', [
        'sistema_id' => $sistema->id,
        'host' => '10.20.0.2',
    ]);
});

it('inativa sem excluir e permite reativar sistema', function () {
    $admin = adminSistema();

    $sistema = Sistema::query()->create([
        'nome' => 'Sistema Contratos',
        'slug' => 'contratos',
        'url' => 'https://contratos.exemplo.local',
        'ambiente' => 'desenvolvimento',
        'ativo' => true,
    ]);

    $delete = $this->actingAs($admin)->delete("/sistema/{$sistema->id}");
    $delete->assertRedirect('/sistema');

    expect($sistema->fresh()->ativo)->toBeFalse();

    $reativar = $this->actingAs($admin)->patch("/sistema/{$sistema->id}/reativar");
    $reativar->assertRedirect('/sistema');

    expect($sistema->fresh()->ativo)->toBeTrue();
});

it('retorna erro amigável ao sincronizar permissões sem banco configurado', function () {
    $admin = adminSistema();

    $sistema = Sistema::query()->create([
        'nome' => 'Sistema Sem Banco',
        'slug' => 'sem-banco',
        'url' => 'https://sem-banco.exemplo.local',
        'ambiente' => 'desenvolvimento',
        'ativo' => true,
    ]);

    $response = $this
        ->actingAs($admin)
        ->from('/sistema')
        ->post("/sistema/{$sistema->id}/sincronizar-permissoes");

    $response->assertRedirect('/sistema');
    $response->assertSessionHas('error');
});

it('atualiza personalização de login sem exigir dados de outras abas', function () {
    Storage::fake('public');
    $admin = adminSistema();

    $sistema = Sistema::query()->create([
        'nome' => 'Portal Cidadão',
        'slug' => 'portal-cidadao',
        'url' => 'https://portal.exemplo.local',
        'ambiente' => 'desenvolvimento',
        'ativo' => true,
    ]);

    $response = $this
        ->actingAs($admin)
        ->patch("/sistema/{$sistema->id}/personalizacao", [
            'upload_caminho_logo' => UploadedFile::fake()->image('nova-logo.png'),
            'upload_caminho_ilustracao' => UploadedFile::fake()->image('nova-ilustracao.png'),
            'login_nome' => 'Acesso Cidadão',
            'tema_login' => 'claro',
            'login_subtitulo' => 'Acesse com seu e-mail institucional.',
            'login_painel_eyebrow' => 'PORTAL',
            'login_painel_titulo' => 'Portal Cidadão',
            'login_painel_descricao' => 'Serviços digitais para o cidadão.',
            'exibir_logo_topo' => false,
            'exibir_bloco_inferior' => true,
            'exibir_degrade_ilustracao' => false,
        ]);

    $response->assertRedirect("/sistema/{$sistema->id}/edit?aba=login");

    $sistema->refresh();
    expect($sistema->caminho_logo)->not->toBeNull();
    expect($sistema->caminho_ilustracao)->not->toBeNull();
    expect($sistema->login_nome)->toBe('Acesso Cidadão');
    expect($sistema->tema_login)->toBe('claro');
    expect($sistema->login_subtitulo)->toBe('Acesse com seu e-mail institucional.');
    expect($sistema->login_painel_titulo)->toBe('Portal Cidadão');
    expect($sistema->exibir_logo_topo)->toBeFalse();
    expect($sistema->exibir_degrade_ilustracao)->toBeFalse();
});

it('testa conexão de banco com credenciais inválidas retorna erro', function () {
    $admin = adminSistema();

    $response = $this->actingAs($admin)->postJson('/sistema/testar-banco', [
        'banco' => [
            'tipo' => 'postgresql',
            'host' => '127.0.0.1',
            'porta' => 1,
            'nome_banco' => 'inexistente',
            'usuario' => 'invalido',
            'senha' => 'invalido',
        ],
    ]);

    $response->assertUnprocessable();
    $response->assertJsonPath('ok', false);
});

