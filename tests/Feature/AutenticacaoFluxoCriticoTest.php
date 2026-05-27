<?php

use App\Models\Orgao;
use App\Models\Sistema;
use App\Models\User;
use App\Models\UserSistema;
use Illuminate\Support\Facades\Crypt;

function criarSistemaTeste(): Sistema
{
    $sistema = Sistema::query()->firstOrCreate(
        ['slug' => 'sistema-teste'],
        [
            'nome' => 'Sistema Teste',
            'url' => 'https://sistema.exemplo.local/entrada',
            'url_logout' => 'https://sistema.exemplo.local/logout',
            'ambiente' => 'desenvolvimento',
            'ativo' => true,
        ],
    );

    $orgao = Orgao::query()->firstOrCreate(
        ['descricao_orgao' => 'Órgão Teste ACL'],
        ['sigla_orgao' => 'OTACL', 'status' => 'ativo'],
    );

    $sistema->orgaos()->syncWithoutDetaching([$orgao->id]);

    return $sistema;
}

function usuarioComAcessoAoSistema(Sistema $sistema, array $attrs = []): User
{
    $user = User::factory()->create($attrs);

    UserSistema::query()->create([
        'user_id' => $user->id,
        'sistema_id' => $sistema->id,
        'administrador' => false,
        'status' => 'S',
    ]);

    return $user;
}

it('GET /login/{slug} retorna 200 e inclui props do sistema quando slug existe e ativo', function () {
    criarSistemaTeste();

    $response = $this->get('/login/sistema-teste');

    $response->assertOk();
});

it('GET /login/{slug} de sistema externo mantém sessão do painel e exibe tela de login', function () {
    criarSistemaTeste();

    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/login/sistema-teste');

    $response->assertOk();
    $this->assertAuthenticatedAs($user);
});

it('GET /login/{slug} inexistente exibe página amigável', function () {
    $response = $this->get('/login/slug-inexistente');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Auth/SlugInvalido')
        ->where('slug', 'slug-inexistente'));
});

it('GET /login com usuário autenticado no hub redireciona para dashboard', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->withHeaders($this->inertiaHeaders())
        ->get('/login');

    $response->assertStatus(409);
    expect($response->headers->get('X-Inertia-Location'))->toContain('/dashboard');
});

it('GET /login carrega branding do sistema login por padrão', function () {
    Sistema::query()->create([
        'nome' => 'Login Universal',
        'slug' => 'login',
        'url' => 'https://login.exemplo.local',
        'url_logout' => null,
        'ambiente' => 'desenvolvimento',
        'ativo' => true,
    ]);

    $response = $this
        ->withHeaders($this->inertiaHeaders())
        ->get('/login');

    $response->assertOk();
    $response->assertJsonPath('props.sistema.slug', 'login');
    $response->assertSessionMissing('return');
});

it('POST /login com credenciais inválidas retorna erros', function () {
    $user = User::factory()->create([
        'email' => 'joao@exemplo.com',
        'password' => bcrypt('senha-correta'),
    ]);

    $response = $this->from('/login')->post('/login', [
        'email' => $user->email,
        'password' => 'senha-incorreta',
    ]);

    $response->assertSessionHasErrors(['email']);
});

it('POST /login com credenciais válidas e com sistema na sessão redireciona (Inertia::location) para sistema->url com callback', function () {
    $sistema = criarSistemaTeste();

    $user = User::factory()->create([
        'email' => 'joao@exemplo.com',
        'password' => bcrypt('senha-correta'),
    ]);

    $this->withSession(['return' => $sistema]);

    $response = $this
        ->withHeaders($this->inertiaHeaders())
        ->post('/login', [
            'email' => $user->email,
            'password' => 'senha-correta',
        ]);

    $response->assertStatus(409);
    $response->assertHeader('X-Inertia-Location');
    expect($response->headers->get('X-Inertia-Location'))
        ->toContain($sistema->url)
        ->toContain('callback=');
});

it('POST /login com usuário já autenticado no hub e retorno externo redireciona para callback', function () {
    $sistema = criarSistemaTeste();

    $user = User::factory()->create([
        'email' => 'admin@exemplo.com',
        'password' => bcrypt('senha-correta'),
    ]);

    $response = $this
        ->actingAs($user)
        ->withSession([
            'return' => $sistema,
            'return_sistema_id' => $sistema->id,
        ])
        ->withHeaders($this->inertiaHeaders())
        ->post('/login', [
            'email' => $user->email,
            'password' => 'senha-correta',
        ]);

    $response->assertStatus(409);
    expect($response->headers->get('X-Inertia-Location'))
        ->toContain($sistema->url)
        ->toContain('callback=');
});

it('POST /login com usuário já autenticado no hub sem retorno externo redireciona para dashboard', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->withHeaders($this->inertiaHeaders())
        ->post('/login', [
            'email' => $user->email,
            'password' => 'qualquer',
        ]);

    $response->assertStatus(409);
    expect($response->headers->get('X-Inertia-Location'))->toContain('/dashboard');
});

it('GET /api/v1/login/{slug}?token=... usuário sem vínculo ao sistema => 403', function () {
    $sistema = criarSistemaTeste();

    $user = User::factory()->create([
        'remember_token' => 'token-sem-acesso',
        'administrador_global' => false,
    ]);

    $payload = Crypt::encrypt([
        'token' => $user->remember_token,
        'session' => session()->getId(),
        'sistema' => 'sistema-teste',
        'validade' => now()->addMinute(),
    ]);

    $response = $this->get('/api/v1/login/sistema-teste?token='.urlencode($payload));

    $response->assertForbidden();
});

it('GET /api/v1/login/{slug}?token=... token inválido => 401', function () {
    criarSistemaTeste();

    $response = $this->get('/api/v1/login/sistema-teste?token=token-invalido');

    $response->assertStatus(401);
});

it('GET /api/v1/login/{slug}?token=... expirado => 401', function () {
    criarSistemaTeste();

    $user = User::factory()->create([
        'remember_token' => 'token-uso-unico',
    ]);

    $payload = Crypt::encrypt([
        'token' => $user->remember_token,
        'session' => session()->getId(),
        'sistema' => 'sistema-teste',
        'validade' => now()->subSecond(),
    ]);

    $response = $this->get('/api/v1/login/sistema-teste?token='.urlencode($payload));

    $response->assertStatus(401);
});

it('GET /api/v1/login/{slug}?token=... slug mismatch => 401', function () {
    criarSistemaTeste();

    $user = User::factory()->create([
        'remember_token' => 'token-uso-unico',
    ]);

    $payload = Crypt::encrypt([
        'token' => $user->remember_token,
        'session' => session()->getId(),
        'sistema' => 'outro-sistema',
        'validade' => now()->addMinute(),
    ]);

    $response = $this->get('/api/v1/login/sistema-teste?token='.urlencode($payload));

    $response->assertStatus(401);
});

it('GET /api/v1/login/{slug}?token=... válido => 200 e revoga remember_token', function () {
    $sistema = criarSistemaTeste();

    $user = usuarioComAcessoAoSistema($sistema, [
        'remember_token' => 'token-uso-unico',
    ]);

    $payload = Crypt::encrypt([
        'token' => $user->remember_token,
        'session' => session()->getId(),
        'sistema' => 'sistema-teste',
        'validade' => now()->addMinute(),
    ]);

    $response = $this->get('/api/v1/login/sistema-teste?token='.urlencode($payload));

    $response->assertOk();
    expect($user->fresh()->remember_token)->toBeNull();
});

it('GET /api/v1/login/{slug} sem token retorna 422 com erro de validação', function () {
    criarSistemaTeste();

    $response = $this->getJson('/api/v1/login/sistema-teste');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['token']);
});

it('GET /api/v1/login/{slug} com slug inexistente retorna 404', function () {
    $payload = Crypt::encrypt([
        'token' => 'token-qualquer',
        'session' => session()->getId(),
        'sistema' => 'slug-inexistente',
        'validade' => now()->addMinute(),
    ]);

    $response = $this->getJson('/api/v1/login/slug-inexistente?token='.urlencode($payload));

    $response->assertNotFound();
});

it('GET /api/v1/login/{slug} com sistema inativo retorna 404', function () {
    Sistema::query()->create([
        'nome' => 'Sistema Inativo',
        'slug' => 'sistema-inativo',
        'url' => 'https://sistema.exemplo.local/entrada',
        'url_logout' => null,
        'ambiente' => 'desenvolvimento',
        'ativo' => false,
    ]);

    $payload = Crypt::encrypt([
        'token' => 'token-qualquer',
        'session' => session()->getId(),
        'sistema' => 'sistema-inativo',
        'validade' => now()->addMinute(),
    ]);

    $response = $this->getJson('/api/v1/login/sistema-inativo?token='.urlencode($payload));

    $response->assertNotFound();
});

it('GET /api/v1/login/{slug}?token=... com validade no limite próximo ainda é aceito', function () {
    $sistema = criarSistemaTeste();

    $user = usuarioComAcessoAoSistema($sistema, [
        'remember_token' => 'token-limite',
    ]);

    $payload = Crypt::encrypt([
        'token' => $user->remember_token,
        'session' => session()->getId(),
        'sistema' => 'sistema-teste',
        'validade' => now()->addSecond(),
    ]);

    $response = $this->get('/api/v1/login/sistema-teste?token='.urlencode($payload));

    $response->assertOk();
    expect($user->fresh()->remember_token)->toBeNull();
});

it('GET /dashboard para usuário autenticado sem permissão retorna 403', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertForbidden();
});

it('GET /dashboard sem autenticação redireciona para login', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect(route('login'));
});

it('POST /login sem campos obrigatórios retorna erro de validação', function () {
    $response = $this->from('/login')->post('/login', []);

    $response->assertSessionHasErrors(['email', 'password']);
});

it('GET /logout invalida sessão e redireciona', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this
        ->withHeaders($this->inertiaHeaders())
        ->get('/logout');

    $response->assertStatus(409);
    $response->assertHeader('X-Inertia-Location', route('login'));
    $this->assertGuest();
});
