# Setup e Configuração

## Requisitos

- PHP 8.3+
- PostgreSQL 14+ (recomendado) ou MySQL 8+
- Node.js 20+
- Composer 2+

---

## Variáveis de ambiente (.env)

```env
APP_NAME="Login Universal"
APP_ENV=local
APP_KEY=                        # Gerado com: php artisan key:generate
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=America/Sao_Paulo
APP_LOCALE=pt_BR

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=login_universal
DB_USERNAME=postgres
DB_PASSWORD=secret

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_COOKIE=login_universal_session

CORS_ALLOWED_ORIGINS=http://localhost:3000,https://meu-outro-sistema.com
```

---

## Instalação passo a passo

```bash
# 1. Dependências PHP
composer install

# 2. Dependências JS
npm install

# 3. Configurar .env
cp .env.example .env
php artisan key:generate

# 4. Criar tabela de sessões e rodar migrations
php artisan session:table
php artisan migrate

# 5. Rodar seeders (cria sistema, permissões e usuário admin)
php artisan db:seed

# 6. Build do frontend
npm run build
# Desenvolvimento:
npm run dev
```

---

## Packages adicionais a instalar

```bash
# Nenhum pacote extra obrigatório além do starter kit.
# O projeto usa apenas o que já vem no Laravel 13 + Inertia Vue.
```

---

## Configurações necessárias

### config/session.php

```php
'driver' => env('SESSION_DRIVER', 'database'),
```

### bootstrap/app.php — Middleware Inertia + redirect de guests

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\HandleInertiaRequests::class,
    ]);

    $middleware->redirectGuestsTo(fn () => route('login'));
})
```

### config/cors.php

```php
return [
    'paths'           => ['api/*'],
    'allowed_methods' => ['GET', 'POST'],
    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', '*')),
    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
];
```

---

## Usuário admin padrão (criado pelo seeder)

```
Email: admin@login.app
Senha: admin123
```

**Altere imediatamente após o primeiro acesso.**

---

## Cadastrar um sistema externo

Via interface web (login com admin → Sistemas → Novo) ou via tinker:

```php
\App\Models\Sistema::create([
    'nome'      => 'Meu Sistema',
    'slug'      => 'meu-sistema',
    'url'       => 'https://meu-sistema.com',
    'ambiente'  => 'production',
    'ativo'     => true,
]);
```

---

## Servidor de desenvolvimento

```bash
composer run dev
```

Inicia em paralelo:
- `php artisan serve` → porta 8000
- `npm run dev` → Vite HMR
- `php artisan queue:listen`
- `php artisan pail` (logs em tempo real)

---

## Deploy em produção

### Variáveis adicionais para produção

```env
APP_ENV=production
APP_DEBUG=false

SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true

CORS_ALLOWED_ORIGINS=https://sistema-a.com,https://sistema-b.com
```

### Comandos pós-deploy

```bash
composer install --no-dev --optimize-autoloader
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
```

---

## Checklist pós-instalação

- [ ] Migrations rodaram sem erros
- [ ] Seeders criaram sistema "Login", permissões e usuário admin
- [ ] Login com `admin@login.app` funciona
- [ ] Redireciona para dashboard após login direto
- [ ] `/login/{slug}` com slug cadastrado funciona e salva sistema na sessão
- [ ] API `/api/v1/login/{slug}?token=invalido` retorna 401
- [ ] CORS configurado para os sistemas clientes
- [ ] Sessões sendo salvas na tabela `sessions`
