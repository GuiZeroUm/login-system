# Integração de Sistemas Externos — Padrão Real (baseado no CAISAN)

> Este documento descreve como um sistema externo **de verdade** se integra ao Login Universal, usando o CAISAN (`/home/guilherme-santos/projetos/login-system/caisan/`) como referência concreta. O CAISAN é um projeto Laravel 12 + Vue 3 + Inertia que consume a API do ACL para autenticação e permissões.

---

## Visão geral do padrão

O sistema externo **não implementa login próprio**. Ele delega 100% para o Login Universal via:

1. Um **pacote composer** (`sead/acl-auth`) que encapsula toda a lógica de integração
2. Duas **variáveis de ambiente** (`ACL_URL` e `ACL_SLUG`)
3. Uma **rota de callback** que recebe o token após login
4. Um **banco de dados local mínimo** — apenas para sincronizar os dados do usuário

No nosso sistema novo, o equivalente ao pacote `sead/acl-auth` será um pacote próprio ou a integração direta via HTTP — o padrão é o mesmo.

---

## Variáveis de ambiente necessárias no sistema externo

```env
# URL base do Login Universal
ACL_URL=https://login.app

# Slug deste sistema cadastrado no Login Universal
ACL_SLUG=meu-sistema
```

---

## Fluxo completo (como o CAISAN faz)

```
1. Usuário acessa meu-sistema.com/dashboard (rota protegida)
   ↓ middleware 'auth' detecta que não está logado
   ↓ redireciona para GET /login

2. AutenticacaoController@index — sem callback na URL
   ↓
   return redirect(env('ACL_URL') . '/login/' . env('ACL_SLUG'));
   // Exemplo: https://login.app/login/caisan

3. Login Universal exibe tela de login com identidade visual do CAISAN
   Usuário digita email + senha

4. Login Universal autentica, gera token criptografado
   ↓
   Redireciona para: meu-sistema.com/login?callback={token_criptografado}

5. AutenticacaoController@index — COM callback na URL
   ↓
   AutenticacaoService::getData($token)
   ↓
   HTTP POST para: https://login.app/api/v1/login/caisan
   Body: { token: "{token}", sistema: "caisan" }
   ↓
   Login Universal valida, revoga token, retorna JSON do usuário

6. AutenticacaoService processa a resposta:
   - updateOrCreate do usuário local (tabela usuarios)
   - Popula session('user') com perfis e permissões
   - Cria cookie SSO-USER-ACL
   - auth()->loginUsingId($user->id)

7. Usuário está logado — redireciona para /dashboard
```

---

## Controller de autenticação (sistema externo)

```php
// app/Http/Controllers/AutenticacaoController.php

class AutenticacaoController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('callback')) {
            $autenticar = new AutenticacaoService($request->get('callback'));
            $autenticar->login();

            if (auth()->check()) {
                return redirect()->route('dashboard');
            }

            return redirect()->route('login')
                ->with('error', 'Não foi possível autenticar. Verifique suas permissões.');
        }

        // Sem callback: redirecionar para o Login Universal
        return redirect(env('ACL_URL') . '/login/' . env('ACL_SLUG'));
    }

    public function logout()
    {
        auth()->logout();
        session(['user' => null]);
        setcookie('SSO-USER-ACL', '', time() - 3600, '/');

        // Redireciona para logout centralizado
        return redirect(env('ACL_URL') . '/logout/' . env('ACL_SLUG'));
    }
}
```

---

## AutenticacaoService (sistema externo)

Este service é o que o sistema externo usa para trocar o token pela API.

```php
// app/Http/Services/AutenticacaoService.php

class AutenticacaoService
{
    public function __construct(private string $token) {}

    public function login(): bool
    {
        $dados = $this->getData();

        if (!$dados) return false;

        // Cria/atualiza usuário local
        $user = Usuario::updateOrCreate(
            ['email' => $dados['email']],
            [
                'name'  => $dados['nome'],
                'email' => $dados['email'],
                // Outros campos que o Login Universal retorna
            ]
        );

        // Popula sessão com permissões
        session([
            'user' => [
                'perfis'      => $this->extrairPerfis($dados),
                'permissoes'  => $this->extrairPermissoes($dados),
                'orgao_atual' => $dados['orgaos']['lotacoes'][0]['orgao']['id'] ?? null,
            ],
        ]);

        // Cookie SSO (opcional — para logout federado)
        setcookie('SSO-USER-ACL', $dados['id'], time() + (86400 * 30), '/');

        auth()->loginUsingId($user->id);

        return true;
    }

    private function getData(): ?array
    {
        $response = Http::timeout(10)->get(
            env('ACL_URL') . '/api/v1/login/' . env('ACL_SLUG'),
            ['token' => $this->token]
        );

        if ($response->failed()) return null;

        $dados = $response->json();

        // Validar se o usuário tem permissão neste sistema
        if (empty($dados['orgaos']['lotacoes'])) {
            abort(403, 'Você não tem permissão para acessar este sistema.');
        }

        return $dados;
    }

    private function extrairPerfis(array $dados): array
    {
        $perfis = [];
        foreach ($dados['orgaos']['lotacoes'] as $lotacao) {
            $orgaoId = $lotacao['orgao']['id'];
            foreach ($lotacao['perfis'] as $perfil) {
                $perfis[$orgaoId][] = $perfil['role']['name'];
            }
        }
        return $perfis;
    }

    private function extrairPermissoes(array $dados): array
    {
        $permissoes = [];
        foreach ($dados['orgaos']['lotacoes'] as $lotacao) {
            $orgaoId = $lotacao['orgao']['id'];

            // Permissões via roles
            foreach ($lotacao['perfis'] as $perfil) {
                foreach ($perfil['role']['permissoes'] ?? [] as $rp) {
                    $perm = $rp['permission'];
                    if ($perm['tipo_crud'] === 'S') {
                        $permissoes[$orgaoId][$perm['id'] . '.' . $rp['tipo']] = true;
                    } else {
                        $permissoes[$orgaoId][$perm['id']] = true;
                    }
                }
            }

            // Permissões diretas
            foreach ($lotacao['permissoes'] as $up) {
                $perm = $up['permission'];
                if ($perm['tipo_crud'] === 'S') {
                    $permissoes[$orgaoId][$perm['id'] . '.' . $up['tipo']] = true;
                } else {
                    $permissoes[$orgaoId][$perm['id']] = true;
                }
            }

            // Admin
            if ($lotacao['administrador']) {
                $permissoes[$orgaoId]['administrador'] = true;
            }
        }
        return $permissoes;
    }
}
```

---

## Estrutura da sessão no sistema externo

Após login bem-sucedido, `session('user')` tem:

```php
session('user') = [
    'perfis' => [
        10 => ['Gestor', 'Analista'],    // orgao_id => nomes dos perfis
        20 => ['Admin'],
    ],
    'permissoes' => [
        10 => [                           // orgao_id => permissões
            '5'            => true,       // permissão simples (acesso)
            '7.1'          => true,       // CRUD: criar
            '7.2'          => true,       // CRUD: editar
            '7.4'          => true,       // CRUD: visualizar
            'administrador' => true,      // flag de admin
        ],
    ],
    'orgao_atual' => 10,                  // órgão ativo no momento
]
```

**Regra importante:** Permissões são sempre verificadas contra `orgao_atual`. Ao trocar de órgão, `session('user')['orgao_atual']` deve ser atualizado.

---

## Gates no sistema externo (PermissionServiceProvider)

```php
// app/Providers/PermissionServiceProvider.php

class PermissionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Gate de admin
        Gate::define('administrador', function (User $user) {
            $orgao = session('user')['orgao_atual'] ?? null;
            return $orgao &&
                   (session('user')['permissoes'][$orgao]['administrador'] ?? false);
        });

        // Gates por permissão cadastrada no Login Universal
        // (buscadas localmente ou via API na inicialização)
        $permissoes = Permissao::all(); // tabela local sincronizada do Login Universal

        foreach ($permissoes as $permissao) {
            Gate::define((string) $permissao->id, function (User $user) use ($permissao) {
                if (Gate::allows('administrador')) return true;
                $orgao = session('user')['orgao_atual'] ?? null;
                return $orgao &&
                       (session('user')['permissoes'][$orgao][$permissao->id] ?? false);
            });

            if ($permissao->tipo_crud === 'S') {
                for ($i = 1; $i <= 4; $i++) {
                    $gate = $permissao->id . '.' . $i;
                    Gate::define($gate, function (User $user) use ($gate) {
                        if (Gate::allows('administrador')) return true;
                        $orgao = session('user')['orgao_atual'] ?? null;
                        return $orgao &&
                               (session('user')['permissoes'][$orgao][$gate] ?? false);
                    });
                }
            }
        }
    }
}
```

---

## Catálogo de permissões por feature (padrão CAISAN)

O CAISAN usa um padrão muito elegante: um catálogo que mapeia nomes legíveis de features para os IDs numéricos dos gates. Isso desacopla o frontend dos IDs:

```php
// app/Http/Services/PermissaoAcoesCatalogo.php

class PermissaoAcoesCatalogo
{
    /**
     * Mapeia features do sistema para IDs de permissão do Login Universal.
     * IDs vêm da tabela 'permissoes' e devem ser os mesmos cadastrados no Login Universal.
     */
    public static function gates(): array
    {
        return [
            'programas' => [
                'criar'     => '7.1',   // permissao_id=7, tipo=1 (criar)
                'editar'    => '7.2',
                'excluir'   => '7.3',
                'visualizar' => '7.4',
                'aprovar'   => '5',     // permissao_id=5 (simples)
            ],
            'relatorios' => [
                'visualizar' => '8',
            ],
            'admin' => 'administrador',
        ];
    }
}
```

### Uso no frontend (Vue)

```javascript
// mixin.js — disponível globalmente em todos os componentes

can(permissao) {
    // Verifica diretamente pelo gate ID
    return usePage().props.gates?.[permissao] ?? false
},

podeAcao(caminho) {
    // Verifica pelo nome legível da feature
    // Exemplo: podeAcao('programas.criar') → can('7.1')
    const partes = caminho.split('.')
    let catalogo = usePage().props.permissoesAcoes
    for (const parte of partes) {
        catalogo = catalogo?.[parte]
    }
    const gate = catalogo
    return gate ? this.can(gate) : false
}
```

```vue
<!-- Uso nos templates -->
<button v-if="can('administrador')">Admin</button>
<button v-if="podeAcao('programas.criar')">Criar Programa</button>
<Link v-if="can('7.4')">Ver Lista</Link>
```

---

## Compartilhar permissões com o frontend (HandleInertiaRequests)

```php
public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'user'             => fn () => $request->user()?->toArray(),
        'gates'            => fn () => PermissaoService::get(),
        'permissoesAcoes'  => fn () => PermissaoAcoesCatalogo::gates(),
        'flash' => [
            'toast' => fn () => $request->session()->get('toast'),
        ],
    ];
}
```

### PermissaoService::get()

```php
class PermissaoService
{
    public static function get(): array
    {
        if (!auth()->check()) return [];

        $gates    = [];
        $permissoes = Permissao::all();

        foreach ($permissoes as $permissao) {
            $gates[(string) $permissao->id] = Gate::allows((string) $permissao->id);

            if ($permissao->tipo_crud === 'S') {
                for ($i = 1; $i <= 4; $i++) {
                    $gate          = $permissao->id . '.' . $i;
                    $gates[$gate]  = Gate::allows($gate);
                }
            }
        }

        $gates['administrador'] = Gate::allows('administrador');

        return $gates;
    }
}
```

---

## Banco de dados local do sistema externo

O sistema externo tem um banco próprio, **independente** do Login Universal. Ele guarda:

1. **Usuários sincronizados** — tabela `usuarios` com campos básicos espelhados do ACL
2. **Dados do domínio** — tudo que é específico do negócio (no CAISAN: questionários, programas, etc.)

### Tabela `usuarios` (mínima)

```sql
CREATE TABLE usuarios (
    id         BIGSERIAL PRIMARY KEY,
    name       VARCHAR NOT NULL,
    email      VARCHAR UNIQUE NOT NULL,
    -- Outros campos opcionais vindo do Login Universal
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Relação entre auth e banco local

```
Login Universal (banco central)
    ↕  HTTP API (token exchange)
Sistema Externo (banco próprio)
    └─ tabela usuarios (espelho mínimo)
    └─ tabela permissoes (cópia das permissões do sistema)
    └─ tabelas do domínio (específicas do negócio)
```

---

## Auth config no sistema externo

```php
// config/auth.php

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model'  => App\Models\Usuario::class, // model local
    ],
],

'guards' => [
    'web' => [
        'driver'   => 'session',
        'provider' => 'users',
    ],
],
```

---

## Middleware de proteção de rotas

```php
// routes/web.php

Route::get('/login', [AutenticacaoController::class, 'index'])->name('login');
Route::post('/logout', [AutenticacaoController::class, 'logout'])->name('logout');

// Rotas protegidas
Route::middleware('auth')->group(function () {
    Route::get('/', fn () => Inertia::render('Dashboard'))->name('dashboard');
    // ... demais rotas
});
```

---

## Checklist para integrar um novo sistema

```
□ Cadastrar o sistema no Login Universal (nome, slug, url, permissões, roles)
□ Adicionar ACL_URL e ACL_SLUG no .env do sistema externo
□ Criar rota GET /login apontando para AutenticacaoController
□ Criar AutenticacaoService que chama /api/v1/login/{slug}
□ Criar tabela usuarios local (mínima)
□ Criar PermissionServiceProvider com os gates
□ Criar PermissaoAcoesCatalogo com mapeamento de features → gates
□ Atualizar HandleInertiaRequests para compartilhar gates e permissoesAcoes
□ Criar PermissaoService::get() para resolver gates do usuário atual
□ Implementar can() e podeAcao() no frontend (mixin ou composable)
□ Testar fluxo completo: login → callback → dashboard → permissões
```
