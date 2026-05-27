# Integração — Como sistemas externos se conectam

## Visão geral do fluxo de integração

```
[Sistema Externo]  →  [Login Universal]  →  [Sistema Externo]
       1                    2-5                    6-7

1. Usuário acessa o sistema externo, que não tem login próprio
2. Sistema externo redireciona para /login/{slug}
3. Usuário se autentica (Gov.br, LDAP ou local)
4. Login Universal gera token e redireciona de volta
5. Sistema externo recebe ?callback={token}
6. Sistema externo troca token pela API
7. Sistema externo recebe dados + permissões e inicia sessão local
```

---

## Pré-requisitos para integrar um novo sistema

1. Cadastrar o sistema no Login Universal:
   - `nome`, `slug` (identificador único), `url` (base URL do sistema externo)
   - Opcionalmente: `url_logout`, `logo`, `ilustracao`

2. Criar as permissões necessárias para o sistema

3. Criar pelo menos um role e vincular usuários

4. Configurar a URL de callback no sistema externo

---

## Passo a passo de integração

### 1. Redirecionar para o Login Universal

Quando um usuário não autenticado tenta acessar o sistema externo:

```
GET https://login.app/login/{slug}
```

O `{slug}` é o identificador do sistema cadastrado. Exemplo: `financeiro`, `rh`, `protocolo`.

O Login Universal armazenará o sistema na sessão e, após o login, redirecionará de volta.

### 2. Receber o callback

Após o login bem-sucedido, o Login Universal redireciona para:

```
{$sistema->url}?callback={token_criptografado}
```

O sistema externo precisa ter uma rota que capture esse `?callback`.

### 3. Trocar o token pelos dados do usuário

O token é **de uso único** e **expira em 1 minuto**. Trocar imediatamente:

```
GET https://login.app/api/v1/login/{slug}?token={callback_value}
```

Resposta: objeto JSON com dados completos do usuário (ver `04-API.md`).

### 4. Iniciar sessão no sistema externo

Com os dados recebidos, criar a sessão local e sincronizar permissões.

---

## Exemplo completo em Laravel

### Configuração no `.env` do sistema externo

```env
ACL_URL=https://login.app
ACL_SLUG=meu-sistema
```

### Middleware de autenticação

```php
// app/Http/Middleware/AclAuth.php

class AclAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->has('usuario_acl')) {
            // Salva URL atual para redirecionar após login
            session(['url_intended' => $request->fullUrl()]);

            $aclUrl  = config('acl.url');
            $aclSlug = config('acl.slug');

            return redirect("{$aclUrl}/login/{$aclSlug}");
        }

        return $next($request);
    }
}
```

### Controller de callback

```php
// app/Http/Controllers/Auth/CallbackController.php

class CallbackController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $token = $request->query('callback');

        if (!$token) {
            return redirect(config('acl.url') . '/login/' . config('acl.slug'))
                ->with('error', 'Autenticação necessária');
        }

        $response = Http::timeout(10)
            ->get(config('acl.url') . '/api/v1/login/' . config('acl.slug'), [
                'token' => $token,
            ]);

        if ($response->failed()) {
            return redirect(config('acl.url') . '/login/' . config('acl.slug'))
                ->with('error', 'Falha na autenticação');
        }

        $usuario = $response->json();

        // Salvar dados na sessão
        session([
            'usuario_acl' => $usuario,
            'acl_token'   => $usuario['acl_token'],
        ]);

        // Sincronizar permissões localmente (opcional mas recomendado)
        $this->syncPermissoes($usuario);

        // Redirecionar para onde o usuário queria ir
        $intended = session()->pull('url_intended', route('dashboard'));

        return redirect($intended);
    }

    private function syncPermissoes(array $usuario): void
    {
        $slug      = config('acl.slug');
        $permissoes = [];

        foreach ($usuario['orgaos']['lotacoes'] ?? [] as $lotacao) {
            foreach ($lotacao['perfis'] ?? [] as $perfil) {
                $role = $perfil['role'] ?? null;
                if (!$role) continue;

                // Filtrar apenas permissões do sistema atual
                if ($role['sistema']['slug'] !== $slug) continue;

                foreach ($role['permissoes'] ?? [] as $rp) {
                    $permissoes[] = [
                        'nome' => $rp['permission']['name'],
                        'tipo' => $rp['tipo'],
                    ];
                }
            }

            // Permissões diretas do usuário na lotação
            foreach ($lotacao['permissoes'] ?? [] as $up) {
                $permissoes[] = [
                    'nome' => $up['permission']['name'],
                    'tipo' => $up['tipo'],
                ];
            }
        }

        session(['permissoes_acl' => $permissoes]);
    }
}
```

### Routes no sistema externo

```php
// routes/web.php

Route::get('/auth/callback', CallbackController::class)->name('auth.callback');

Route::middleware(AclAuth::class)->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    // demais rotas protegidas
});
```

### Configuração do ACL no sistema externo

```php
// config/acl.php

return [
    'url'  => env('ACL_URL', 'https://login.app'),
    'slug' => env('ACL_SLUG', 'meu-sistema'),
];
```

---

## Verificação periódica de sessão

O sistema externo pode verificar periodicamente se a sessão ACL ainda está ativa:

```php
// Em um middleware ou job agendado

$aclToken = session('acl_token');

if ($aclToken) {
    $response = Http::get(config('acl.url') . "/api/v1/check/{$aclToken}");

    if ($response->json('ativo') === false) {
        // Sessão expirou no ACL
        session()->forget(['usuario_acl', 'acl_token', 'permissoes_acl']);
        return redirect(config('acl.url') . '/login/' . config('acl.slug'));
    }
}
```

---

## Helper de permissões no sistema externo

```php
// app/Helpers/AclPermission.php

class AclPermission
{
    public static function can(string $nome, int $tipo = 0): bool
    {
        $permissoes = session('permissoes_acl', []);

        return collect($permissoes)->some(function ($p) use ($nome, $tipo) {
            return $p['nome'] === $nome && ($p['tipo'] === $tipo || $p['tipo'] === 0);
        });
    }

    public static function usuario(): array|null
    {
        return session('usuario_acl');
    }

    public static function orgaos(): array
    {
        return self::usuario()['orgaos']['lotacoes'] ?? [];
    }
}
```

---

## Logout federado

Quando o usuário faz logout em qualquer sistema, todos os sistemas devem ser notificados (ou a sessão central deve ser invalidada):

### No sistema externo

```php
// LogoutController.php

public function __invoke(): RedirectResponse
{
    $slug    = config('acl.slug');
    $aclUrl  = config('acl.url');

    session()->invalidate();
    session()->regenerateToken();

    // Redirecionar para logout centralizado
    return redirect("{$aclUrl}/logout/{$slug}");
}
```

### No Login Universal (AutenticacaoController@logout)

```php
public function logout(string $slug = null): Response
{
    $sistema = $slug ? Sistema::where('slug', $slug)->first() : null;

    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    if ($sistema?->url_logout) {
        return Inertia::location($sistema->url_logout);
    }

    return Inertia::location(route('login'));
}
```

---

## Múltiplos sistemas simultâneos

Um usuário pode estar logado em múltiplos sistemas ao mesmo tempo (cada um com sua sessão local). O Login Universal mantém **uma única sessão central** via cookie SSO. Quando um sistema faz GET `/login/{slug}` e o usuário já está autenticado no ACL, o fluxo pula direto para o passo de geração de token (sem reautenticação).

---

## Segurança

1. **Token de callback é de uso único** — após validado, `remember_token` é zerado
2. **Token expira em 1 minuto** — janela mínima para evitar replay attacks
3. **Token é criptografado** com `APP_KEY` do Laravel (`Crypt::encrypt`)
4. **HTTPS obrigatório** em produção — o token em query string deve trafegar criptografado
5. **CORS configurado** para aceitar apenas origens conhecidas
6. **Slug do sistema validado** dentro do payload criptografado — não é possível usar token de um sistema em outro
