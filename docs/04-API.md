# API de Integração — Endpoints para Sistemas Externos

## Visão geral

Esta API é o mecanismo pelo qual sistemas externos trocam o token de callback recebido pelos dados completos do usuário. Também inclui endpoints para consultar órgãos, lotações e verificar sessões ativas.

---

## Autenticação na API

### Endpoint público (validação de token)
`/api/v1/login/{slug}` — **não requer autenticação**. O token criptografado é a própria credencial.

### Endpoints protegidos (machine-to-machine)
Usam Bearer Token via tabela `apis`:

```
Authorization: Bearer {token_da_api}
```

Middleware `ApiAuth` descriptografa o token e busca na tabela `apis`.

---

## Endpoints

### `GET /api/v1/login/{slug}?token={payload}`

**Validar token de callback e retornar dados do usuário.**

Este é o endpoint principal. O sistema externo chama este endpoint após receber `?callback={payload}` no redirect.

**Parâmetros:**
- `slug` (path) — identificador do sistema no cadastro
- `token` (query) — payload criptografado recebido no callback

**Fluxo interno (ValidarService::validar):**

```php
public static function validar(string $slug, string $tokenEncryptado): array|JsonResponse
{
    // 1. Descriptografar
    try {
        $payload = Crypt::decrypt($tokenEncryptado);
    } catch (DecryptException) {
        return response()->json(['message' => 'Token inválido'], 401);
    }

    // 2. Verificar validade (1 minuto)
    if (now()->isAfter($payload['validade'])) {
        return response()->json(['message' => 'Token expirado'], 401);
    }

    // 3. Verificar correspondência de sistema
    if ($payload['sistema'] !== $slug) {
        return response()->json(['message' => 'Token não pertence a este sistema'], 401);
    }

    // 4. Buscar usuário pelo remember_token
    $usuario = User::where('remember_token', $payload['token'])->first();
    if (!$usuario) {
        return response()->json(['message' => 'Usuário não encontrado'], 401);
    }

    // 5. Revogar token (uso único)
    $usuario->remember_token = null;
    $usuario->save();

    // 6. Retornar dados
    return new UserResource($usuario);
}
```

**Resposta de sucesso (200):**

```json
{
    "id": 42,
    "nome": "João Silva",
    "email": "joao@exemplo.com",
    "email_funcional": "joao.silva@orgao.gov.br",
    "status_usuario": "S",
    "login_usuario": "joao.silva",
    "usuario_interno": "S",
    "cpf": "12345678900",
    "origem": "GOVBR",
    "nivel": "Ouro",
    "foto": "data:image/jpeg;base64,...",
    "ultimo_login": "2024-01-15T10:30:00-05:00",

    "acl_token": "session_id_ativo",

    "orgaos": {
        "lotacoes": [
            {
                "id": 1,
                "administrador": false,
                "lotacao_exercicio": true,
                "orgao": {
                    "id": 10,
                    "descricao_orgao": "SECRETARIA DE EDUCAÇÃO",
                    "sigla_orgao": "SEDUC",
                    "cnpj": "00.000.000/0001-00"
                },
                "lotacao": {
                    "id": 50,
                    "nome_lotacao": "DIRETORIA DE PLANEJAMENTO",
                    "sigla_lotacao": "DIPLAN",
                    "nivel_hierarquico": 2
                },
                "perfis": [
                    {
                        "id": 1,
                        "role": {
                            "id": 5,
                            "name": "Analista",
                            "sistema": {
                                "id": 2,
                                "nome": "Sistema Financeiro",
                                "slug": "financeiro"
                            },
                            "permissoes": [
                                {
                                    "id": 1,
                                    "permission": {
                                        "id": 10,
                                        "name": "Relatórios",
                                        "tipo_crud": "N"
                                    },
                                    "tipo": 0
                                }
                            ]
                        }
                    }
                ],
                "permissoes": []
            }
        ]
    },

    "sistemas": [
        {
            "id": 2,
            "nome": "Sistema Financeiro",
            "slug": "financeiro",
            "url": "https://financeiro.exemplo.gov.br",
            "ambiente": "production"
        }
    ]
}
```

**Respostas de erro:**
- `401` — Token inválido, expirado ou já utilizado
- `404` — Sistema não encontrado

---

### `GET /api/v1/check/{acl_token}`

**Verificar se uma sessão ainda está ativa.**

O `acl_token` é o `session_id` retornado no campo `acl_token` do UserResource.

```php
// Verifica se a sessão ainda existe na tabela sessions
$sessao = DB::table('sessions')
    ->where('id', $aclToken)
    ->first();

if (!$sessao) {
    return response()->json(['ativo' => false], 200);
}

return response()->json([
    'ativo'        => true,
    'user_id'      => $sessao->user_id,
    'last_activity' => $sessao->last_activity,
]);
```

---

### `GET /api/v1/orgaos/{slug}/{orgao_id?}`

**Listar órgãos disponíveis para um sistema.**

Retorna órgãos que têm vinculação com o sistema informado.

```json
[
    {
        "id": 10,
        "descricao_orgao": "SECRETARIA DE EDUCAÇÃO",
        "sigla_orgao": "SEDUC",
        "cnpj": "00.000.000/0001-00",
        "status": "ativo"
    }
]
```

---

### `GET /api/v1/lotacoes/{slug}/{orgao_id?}`

**Listar lotações de um órgão.**

```json
[
    {
        "id": 50,
        "cod_lotacao": "DIPLAN.001",
        "nome_lotacao": "DIRETORIA DE PLANEJAMENTO",
        "sigla_lotacao": "DIPLAN",
        "nivel_hierarquico": 2,
        "subordinada_id": 48
    }
]
```

---

### `GET /api/v1/unidades/{slug}/{orgao_id?}`

**Listar unidades de um órgão.**

---

### `POST /api/sistema` *(requer ApiAuth)*

**Listar sistemas cadastrados.** Para uso machine-to-machine.

---

## UserResource — Implementação

Arquivo: `app/Http/Resources/UserResource.php`

```php
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'nome'                  => $this->name,
            'email'                 => $this->email,
            'email_funcional'       => $this->email_funcional,
            'status_usuario'        => $this->status_usuario,
            'login_usuario'         => $this->login_usuario,
            'usuario_interno'       => $this->usuario_interno,
            'cpf'                   => $this->cpf,
            'origem'                => $this->origem,
            'nivel'                 => $this->nivel,
            'foto'                  => $this->foto_perfil,
            'ultimo_login'          => $this->ultimo_login,
            'acl_token'             => session()->getId(),

            'orgaos' => [
                'lotacoes' => UserLotacaoResource::collection(
                    $this->lotacoes()->with([
                        'orgao',
                        'lotacao',
                        'perfis.role.sistema',
                        'perfis.role.permissions.permissao',
                        'permissoes.permissao',
                    ])->get()
                ),
            ],

            'sistemas' => SistemaResource::collection(
                Sistema::whereHas('orgaos', function ($q) {
                    $q->whereIn('orgao_id', 
                        $this->lotacoes->pluck('orgao_id')
                    );
                })->orWhere('id', session('return')?->id)->get()
            ),
        ];
    }
}
```

---

## Middleware ApiAuth

Arquivo: `app/Http/Middleware/ApiAuth.php`

```php
class ApiAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearerToken = $request->bearerToken();

        if (!$bearerToken) {
            return response()->json(['message' => 'Token não fornecido'], 401);
        }

        try {
            $tokenDecryptado = Crypt::decrypt($bearerToken);
        } catch (DecryptException) {
            return response()->json(['message' => 'Token inválido'], 401);
        }

        $api = Api::where('token', $tokenDecryptado)
            ->where('ativo', true)
            ->first();

        if (!$api) {
            return response()->json(['message' => 'Token não autorizado'], 401);
        }

        return $next($request);
    }
}
```

---

## Exemplo de integração em sistema externo

### PHP / Laravel

```php
// No sistema externo que recebe ?callback={token}

class LoginCallbackController extends Controller
{
    public function __invoke(Request $request, string $slug)
    {
        $token = $request->query('callback');

        if (!$token) {
            return redirect()->route('login')->with('error', 'Token não recebido');
        }

        $response = Http::get(config('acl.url') . "/api/v1/login/{$slug}", [
            'token' => $token,
        ]);

        if ($response->failed()) {
            return redirect()->route('login')->with('error', 'Falha na autenticação');
        }

        $usuario = $response->json();

        // Armazenar na sessão local
        session(['usuario' => $usuario]);
        session(['acl_token' => $usuario['acl_token']]);

        // Sincronizar permissões localmente (opcional)
        $this->sincronizarPermissoes($usuario);

        return redirect()->route('dashboard');
    }

    private function sincronizarPermissoes(array $usuario): void
    {
        // Extrair perfis do usuário para o sistema atual
        $meuSlug = config('acl.slug'); // 'sistema-financeiro'

        $lotacoes = $usuario['orgaos']['lotacoes'] ?? [];

        foreach ($lotacoes as $lotacao) {
            foreach ($lotacao['perfis'] ?? [] as $perfil) {
                $role = $perfil['role'] ?? null;
                if ($role && $role['sistema']['slug'] === $meuSlug) {
                    // Aplicar permissões do role localmente
                }
            }
        }
    }
}
```

### JavaScript / Node.js

```javascript
// Recebe: GET /callback?callback={token}
async function handleCallback(req, res) {
    const token = req.query.callback;
    const slug  = process.env.ACL_SLUG;

    const response = await fetch(`${process.env.ACL_URL}/api/v1/login/${slug}?token=${token}`);

    if (!response.ok) {
        return res.redirect('/login?error=auth_failed');
    }

    const usuario = await response.json();

    req.session.usuario   = usuario;
    req.session.aclToken  = usuario.acl_token;

    res.redirect('/dashboard');
}
```

---

## Configurações de CORS

Para que sistemas externos possam consumir a API, configurar em `config/cors.php`:

```php
return [
    'paths'   => ['api/*'],
    'allowed_methods' => ['GET', 'POST'],
    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', '*')),
    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
];
```

Adicionar no `.env`:
```env
CORS_ALLOWED_ORIGINS=https://sistema-a.gov.br,https://sistema-b.gov.br
```
