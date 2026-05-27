# Banco de Dados — Esquema Completo

## Visão geral

O banco usa **PostgreSQL** como padrão. Todas as tabelas devem ser criadas via migrations Laravel.

---

## Tabelas e suas migrations

### 1. `users` — Usuários do sistema

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('password');
    $table->enum('status_usuario', ['S', 'N'])->default('S');
    $table->timestamp('ultimo_login')->nullable();
    $table->rememberToken();   // Usado como token temporário de callback
    $table->timestamps();
    $table->softDeletes();
});
```

**Sobre `remember_token`:** reutilizado como token one-time de callback para outros sistemas. Após o sistema externo validar, é zerado imediatamente.

---

### 2. `sistemas` — Sistemas clientes cadastrados

```php
Schema::create('sistemas', function (Blueprint $table) {
    $table->id();
    $table->string('nome');
    $table->string('slug')->unique();                // Ex: "sistema-financeiro"
    $table->string('url');                           // URL raiz do sistema externo
    $table->string('url_logout')->nullable();        // Para onde redirecionar no logout
    $table->enum('ambiente', ['production', 'homologacao', 'desenvolvimento'])
          ->default('desenvolvimento');
    $table->text('descricao')->nullable();
    $table->string('caminho_logo')->nullable();      // Path ou URL do logo
    $table->string('caminho_ilustracao')->nullable(); // Imagem na tela de login
    $table->boolean('ativo')->default(true);
    $table->timestamps();
});
```

**Como é usado:** quando um sistema externo redireciona para `/login/{slug}`, buscamos pelo `slug` para saber para onde redirecionar após o login. O `caminho_logo` e `caminho_ilustracao` personalizam a tela de login para aquele sistema.

---

### 3. `orgaos` — Órgãos/organizações

```php
Schema::create('orgaos', function (Blueprint $table) {
    $table->id();
    $table->string('descricao_orgao');
    $table->string('sigla_orgao', 20)->nullable();
    $table->string('cnpj', 18)->nullable()->unique();
    $table->enum('status', ['ativo', 'inativo'])->default('ativo');
    $table->timestamps();
});
```

---

### 4. `lotacoes` — Departamentos/setores dentro de órgãos

```php
Schema::create('lotacoes', function (Blueprint $table) {
    $table->id();
    $table->string('nome_lotacao');
    $table->string('sigla_lotacao')->nullable();
    $table->foreignId('orgao_id')->constrained('orgaos');
    $table->integer('nivel_hierarquico')->default(1);
    $table->foreignId('subordinada_id')->nullable()
          ->constrained('lotacoes');                 // Auto-referência para hierarquia
    $table->timestamps();
});
```

---

### 5. `user_lotacoes` — Vínculo usuário ↔ órgão ↔ lotação

Esta é a tabela pivô central. Toda permissão passa por aqui.

```php
Schema::create('user_lotacoes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users');
    $table->foreignId('orgao_id')->constrained('orgaos');
    $table->foreignId('lotacao_id')->nullable()->constrained('lotacoes');
    $table->boolean('lotacao_exercicio')->default(false); // Vínculo principal do usuário
    $table->boolean('administrador')->default(false);    // Admin neste órgão/lotação
    $table->enum('status', ['S', 'N'])->default('S');
    $table->foreignId('created_by')->nullable()->constrained('users');
    $table->foreignId('updated_by')->nullable()->constrained('users');
    $table->foreignId('deleted_by')->nullable()->constrained('users');
    $table->softDeletes();
    $table->timestamps();
});
```

---

### 6. `roles` — Perfis/papéis por sistema

```php
Schema::create('roles', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->foreignId('sistema_id')->constrained('sistemas');
    $table->softDeletes();
    $table->timestamps();
});
```

**Exemplo:** "Administrador", "Editor", "Visualizador" — cada um pertence a um sistema específico.

---

### 7. `permissions` — Permissões disponíveis em cada sistema

```php
Schema::create('permissions', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->foreignId('sistema_id')->constrained('sistemas');
    $table->enum('tipo_crud', ['S', 'N'])->default('N');
    // 'S' = gera 4 sub-permissões (criar/editar/excluir/visualizar)
    // 'N' = gera 1 permissão de acesso simples
    $table->timestamps();
});
```

---

### 8. `role_has_permissions` — Papéis ↔ Permissões

```php
Schema::create('role_has_permissions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('role_id')->constrained('roles');
    $table->foreignId('permission_id')->constrained('permissions');
    $table->tinyInteger('tipo')->default(0);
    // 0 = acesso, 1 = criar, 2 = editar, 3 = excluir, 4 = visualizar
    $table->timestamps();
    $table->unique(['role_id', 'permission_id', 'tipo']);
});
```

---

### 9. `user_perfil` — Usuário (via lotação) ↔ Papel

```php
Schema::create('user_perfil', function (Blueprint $table) {
    $table->id();
    $table->foreignId('role_id')->constrained('roles');
    $table->foreignId('user_lotacao_id')->constrained('user_lotacoes');
    $table->foreignId('created_by')->nullable()->constrained('users');
    $table->foreignId('updated_by')->nullable()->constrained('users');
    $table->foreignId('deleted_by')->nullable()->constrained('users');
    $table->softDeletes();
    $table->timestamps();
    $table->unique(['role_id', 'user_lotacao_id'], 'user_perfil_unique');
});
```

---

### 10. `user_permissions` — Permissões diretas do usuário (por lotação)

Para casos onde um usuário precisa de uma permissão específica sem ter um role completo.

```php
Schema::create('user_permissions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('permission_id')->constrained('permissions');
    $table->foreignId('user_lotacao_id')->constrained('user_lotacoes');
    $table->tinyInteger('tipo')->default(0);
    // 0 = acesso, 1 = criar, 2 = editar, 3 = excluir, 4 = visualizar
    $table->foreignId('created_by')->nullable()->constrained('users');
    $table->foreignId('updated_by')->nullable()->constrained('users');
    $table->foreignId('deleted_by')->nullable()->constrained('users');
    $table->softDeletes();
    $table->timestamps();
});
```

---

### 11. `orgao_sistema` — Quais órgãos têm acesso a quais sistemas

```php
Schema::create('orgao_sistema', function (Blueprint $table) {
    $table->id();
    $table->foreignId('orgao_id')->constrained('orgaos');
    $table->foreignId('sistema_id')->constrained('sistemas');
    $table->timestamps();
    $table->unique(['orgao_id', 'sistema_id']);
});
```

---

### 12. `apis` — Tokens para acesso machine-to-machine

```php
Schema::create('apis', function (Blueprint $table) {
    $table->id();
    $table->string('nome');
    $table->text('token')->unique();
    $table->foreignId('sistema_id')->nullable()->constrained('sistemas');
    $table->boolean('ativo')->default(true);
    $table->timestamps();
});
```

---

### 13. `conexao_sistemas` — Credenciais de banco geradas automaticamente

Criada automaticamente ao provisionar o banco de cada sistema. Ver `docs/09-AUTO-DATABASE.md` para o fluxo completo.

```php
Schema::create('conexao_sistemas', function (Blueprint $table) {
    $table->id();
    $table->foreignId('sistema_id')->unique()->constrained('sistemas')->cascadeOnDelete();
    $table->string('db_host');
    $table->integer('db_port')->default(5432);
    $table->string('db_name');
    $table->string('db_username');
    $table->text('db_password');          // Senha criptografada com Crypt::encrypt
    $table->string('db_connection')->default('pgsql');
    $table->enum('status', ['pendente', 'criado', 'erro'])->default('pendente');
    $table->text('erro_mensagem')->nullable();
    $table->timestamp('criado_em')->nullable();
    $table->timestamps();
});
```

---

### 14. `sessions` — Sessões ativas (driver database)

Gerada pelo próprio Laravel via `php artisan session:table`.

```php
Schema::create('sessions', function (Blueprint $table) {
    $table->string('id')->primary();
    $table->foreignId('user_id')->nullable()->index();
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->longText('payload');
    $table->integer('last_activity')->index();
});
```

---

## Diagrama de relacionamentos

```
users
  └── user_lotacoes (N)
        ├── orgaos (1)
        ├── lotacoes (1)
        ├── user_perfil (N)
        │     └── roles (1)
        │           ├── sistemas (1)
        │           └── role_has_permissions (N)
        │                 └── permissions (1)
        └── user_permissions (N)
              └── permissions (1)

sistemas
  └── conexao_sistemas (1) ← criada automaticamente ao cadastrar o sistema

sistemas
  └── orgao_sistema (N)
        └── orgaos (1)
```

---

## Seeds necessários

### Seed 1: Sistema próprio (o login em si)

```php
Sistema::create([
    'nome'      => 'Login Universal',
    'slug'      => 'login',
    'url'       => env('APP_URL'),
    'ambiente'  => 'production',
    'ativo'     => true,
]);
```

### Seed 2: Permissões do sistema de login

```php
$sistemaId = Sistema::where('slug', 'login')->value('id');

$permissoes = [
    ['name' => 'Sistemas',   'tipo_crud' => 'S'],  // ID 1 → gates 1.1, 1.2, 1.3, 1.4
    ['name' => 'Usuários',   'tipo_crud' => 'S'],  // ID 2 → gates 2.1, 2.2, 2.3, 2.4
    ['name' => 'Órgãos',     'tipo_crud' => 'S'],  // ID 3 → gates 3.1, 3.2, 3.3, 3.4
    ['name' => 'Lotações',   'tipo_crud' => 'S'],  // ID 4 → gates 4.1, 4.2, 4.3, 4.4
    ['name' => 'Perfis',     'tipo_crud' => 'S'],  // ID 5 → gates 5.1, 5.2, 5.3, 5.4
    ['name' => 'Permissões', 'tipo_crud' => 'S'],  // ID 6 → gates 6.1, 6.2, 6.3, 6.4
    ['name' => 'Sessões',    'tipo_crud' => 'N'],  // ID 7 → gate 7
];

foreach ($permissoes as $p) {
    Permission::create([...$p, 'sistema_id' => $sistemaId]);
}
```

### Seed 3: Órgão padrão + usuário administrador

```php
$orgao = Orgao::create([
    'descricao_orgao' => 'Organização Principal',
    'sigla_orgao'     => 'PRINCIPAL',
    'status'          => 'ativo',
]);

$user = User::create([
    'name'           => 'Administrador',
    'email'          => 'admin@login.app',
    'password'       => Hash::make('admin123'),
    'status_usuario' => 'S',
]);

UserLotacao::create([
    'user_id'           => $user->id,
    'orgao_id'          => $orgao->id,
    'administrador'     => true,
    'lotacao_exercicio' => true,
    'status'            => 'S',
    'created_by'        => $user->id,
]);
```

---

## Observações

- `softDeletes()` em: `users`, `user_lotacoes`, `roles`, `user_perfil`, `user_permissions`
- `SESSION_DRIVER=database` obrigatório no `.env` para gerenciar sessões pela tabela
- Os IDs das permissões no seed devem ser **estáveis** — os controllers referenciam por ID numérico (ex: `can:2.4`)
- Índices compostos recomendados: `(user_id, status)` em `user_lotacoes`, `(role_id, permission_id, tipo)` em `role_has_permissions`
