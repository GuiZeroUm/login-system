# Autenticação — Fluxo Completo

## Visão geral

O sistema usa autenticação própria com **email e senha**. Não há dependência de provedores externos.

---

## Fluxo de login

```
1. GET /login/{slug?}
   → Controller busca o Sistema pelo slug (se fornecido)
   → Salva na sessão: session(['return' => $sistema])
   → Se já autenticado: pula direto para o passo 5
   → Se não: renderiza tela de login (Inertia)

2. Usuário preenche email + senha e submete
   POST /login { email, password }

3. AutenticacaoController@store
   → Auth::attempt(['email' => $email, 'password' => $password])
   → Se falha: retorna erro de credenciais
   → Se sucesso: chama AutenticacaoService::login()

4. AutenticacaoService::login()
   → Verifica se há sistema na sessão ('return')
   → Se sim: gera token de callback e redireciona para o sistema externo
   → Se não: redireciona para /dashboard (login direto no sistema de login)

5. Sistema externo recebe ?callback={token}
   → Troca o token pela API (ver 04-API.md)
```

---

## AutenticacaoService

Arquivo: `app/Http/Services/Autenticacao/AutenticacaoService.php`

```php
class AutenticacaoService
{
    public static function login(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $user    = auth()->user();
        $sistema = session('return');

        $user->ultimo_login = now();

        if ($sistema) {
            $token               = uniqid();
            $user->remember_token = $token;
            $user->save();

            $payload = Crypt::encrypt([
                'token'    => $token,
                'session'  => session()->getId(),
                'sistema'  => $sistema->slug,
                'validade' => now()->addMinutes(1), // Token expira em 1 minuto
            ]);

            return Inertia::location($sistema->url . '?callback=' . urlencode($payload));
        }

        $user->save();

        return Inertia::location(route('dashboard'));
    }
}
```

---

## AutenticacaoController

Arquivo: `app/Http/Controllers/AutenticacaoController.php`

```php
class AutenticacaoController extends Controller
{
    public function index(string $slug = null): Response
    {
        // Se já está autenticado, processa login direto
        if (auth()->check()) {
            return AutenticacaoService::login(request());
        }

        $sistema = null;
        if ($slug) {
            $sistema = Sistema::where('slug', $slug)->where('ativo', true)->first();
            if ($sistema) {
                session(['return' => $sistema]);
            }
        }

        return Inertia::render('Auth/Login', [
            'sistema' => $sistema ? new SistemaResource($sistema) : null,
        ]);
    }

    public function store(LoginRequest $request): \Symfony\Component\HttpFoundation\Response
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        return AutenticacaoService::login($request);
    }

    public function logout(string $slug = null): \Symfony\Component\HttpFoundation\Response
    {
        $sistema = $slug ? Sistema::where('slug', $slug)->first() : null;

        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        if ($sistema?->url_logout) {
            return Inertia::location($sistema->url_logout);
        }

        return Inertia::location(route('login'));
    }
}
```

---

## LoginRequest

Arquivo: `app/Http/Requests/LoginRequest.php`

```php
class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}
```

---

## Routes de autenticação

```php
// routes/web.php

Route::middleware('guest')->group(function () {
    Route::get('/login/{slug?}', [AutenticacaoController::class, 'index'])->name('login');
    Route::post('/login', [AutenticacaoController::class, 'store'])->name('login.store');
});

Route::get('/logout/{slug?}', [AutenticacaoController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');
```

---

## Model User

O model `User` precisa ter os campos necessários para o sistema. Arquivo: `app/Models/User.php`

```php
class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'status_usuario',
        'ultimo_login',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'    => 'hashed',
            'ultimo_login' => 'datetime',
        ];
    }

    public function lotacoes(): HasMany
    {
        return $this->hasMany(UserLotacao::class)
            ->where('status', 'S')
            ->withoutTrashed();
    }

    public function getAdministradorAttribute(): bool
    {
        return $this->lotacoes()->where('administrador', true)->exists();
    }
}
```

---

## Fluxo de logout

O logout pode ser acionado de três formas:

1. **Logout do próprio sistema de login:** `GET /logout` — invalida sessão e redireciona para `/login`
2. **Logout a partir de sistema externo:** `GET /logout/{slug}` — invalida sessão e redireciona para `sistema->url_logout`
3. **Expiração de sessão:** o driver `database` limpa sessões expiradas automaticamente via scheduler do Laravel

---

## Criação e reset de senha

Para o MVP, o usuário admin cria contas manualmente. Para um sistema completo, implementar:

- `POST /forgot-password` → Enviar email com link de reset
- `GET /reset-password/{token}` → Formulário de nova senha
- `POST /reset-password` → Processar o reset

O Laravel tem scaffolding pronto via `Password::sendResetLink()` e `Password::reset()`.

---

## Middleware de autenticação

Em `bootstrap/app.php`, garantir que visitantes não autenticados sejam redirecionados para a rota `login`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\HandleInertiaRequests::class,
    ]);

    $middleware->redirectGuestsTo(fn () => route('login'));
})
```
