# Criação Automática de Banco de Dados por Sistema

## Contexto e motivação

No ACL original (`/home/guilherme-santos/projetos/login-system/acl/`), quando um administrador cadastra um novo sistema, a conexão de banco de dados é configurada **manualmente**: o admin entra na aba "Banco de Dados" do formulário do sistema e preenche IP, nome do banco, usuário, senha e porta à mão.

No nosso sistema, **esse processo é automático**: ao criar um novo sistema, o Login Universal:
1. Cria automaticamente um banco PostgreSQL dedicado para aquele sistema
2. Cria um usuário de banco com permissões restritas
3. Gera uma senha segura aleatória
4. Armazena tudo criptografado na tabela `conexao_sistemas`
5. Exibe as credenciais uma única vez para o administrador

O administrador do sistema externo recebe as credenciais prontas e as coloca no `.env` de sua aplicação.

---

## Tabela `conexao_sistemas`

```php
Schema::create('conexao_sistemas', function (Blueprint $table) {
    $table->id();
    $table->foreignId('sistema_id')->unique()->constrained('sistemas')->cascadeOnDelete();
    $table->string('db_host');
    $table->integer('db_port')->default(5432);
    $table->string('db_name');        // Nome do banco criado
    $table->string('db_username');    // Usuário criado para este banco
    $table->text('db_password');      // Senha CRIPTOGRAFADA (Crypt::encrypt)
    $table->string('db_connection')->default('pgsql');
    $table->enum('status', ['pendente', 'criado', 'erro'])->default('pendente');
    $table->text('erro_mensagem')->nullable(); // Log de erro se falhar
    $table->timestamp('criado_em')->nullable();
    $table->timestamps();
});
```

---

## Variáveis de ambiente necessárias

```env
# Servidor PostgreSQL onde os bancos dos sistemas serão criados
# (pode ser o mesmo servidor do Login Universal ou um servidor dedicado)
DB_SISTEMAS_HOST=127.0.0.1
DB_SISTEMAS_PORT=5432
DB_SISTEMAS_ADMIN_USER=postgres          # Superusuário para criar bancos/roles
DB_SISTEMAS_ADMIN_PASSWORD=senha_admin
```

---

## DatabaseService — o coração da feature

Arquivo: `app/Http/Services/Sistema/DatabaseService.php`

```php
class DatabaseService
{
    /**
     * Cria banco de dados e usuário para um sistema recém-cadastrado.
     * Chamado automaticamente pelo SistemaObserver após criação.
     */
    public static function provisionar(Sistema $sistema): ConexaoSistema
    {
        $dbName   = self::sanitizarNome($sistema->slug);
        $dbUser   = 'usr_' . $dbName;              // ex: usr_sistema_financeiro
        $dbPass   = self::gerarSenha();             // Senha segura aleatória

        try {
            $pdo = self::conexaoAdmin();

            // 1. Criar o banco
            $pdo->exec("CREATE DATABASE \"{$dbName}\"");

            // 2. Criar usuário dedicado
            $pdo->exec("CREATE USER \"{$dbUser}\" WITH PASSWORD '{$dbPass}'");

            // 3. Conceder permissões apenas no banco do sistema
            $pdo->exec("GRANT ALL PRIVILEGES ON DATABASE \"{$dbName}\" TO \"{$dbUser}\"");

            // 4. Conectar ao banco criado e configurar schema padrão
            $pdoDb = self::conexaoNoBanco($dbName);
            $pdoDb->exec("GRANT ALL ON SCHEMA public TO \"{$dbUser}\"");

            // 5. Salvar credenciais criptografadas
            $conexao = ConexaoSistema::create([
                'sistema_id'    => $sistema->id,
                'db_host'       => env('DB_SISTEMAS_HOST', '127.0.0.1'),
                'db_port'       => env('DB_SISTEMAS_PORT', 5432),
                'db_name'       => $dbName,
                'db_username'   => $dbUser,
                'db_password'   => Crypt::encrypt($dbPass), // Criptografar antes de salvar
                'db_connection' => 'pgsql',
                'status'        => 'criado',
                'criado_em'     => now(),
            ]);

            return $conexao;

        } catch (\Exception $e) {
            // Salva o erro mas não quebra o cadastro do sistema
            ConexaoSistema::create([
                'sistema_id'    => $sistema->id,
                'db_host'       => env('DB_SISTEMAS_HOST', '127.0.0.1'),
                'db_port'       => env('DB_SISTEMAS_PORT', 5432),
                'db_name'       => $dbName,
                'db_username'   => $dbUser,
                'db_password'   => Crypt::encrypt($dbPass),
                'db_connection' => 'pgsql',
                'status'        => 'erro',
                'erro_mensagem' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Remove banco e usuário quando um sistema é excluído.
     * Chamado pelo SistemaObserver antes da deleção.
     * ATENÇÃO: operação irreversível — exige confirmação dupla no frontend.
     */
    public static function destruir(Sistema $sistema): void
    {
        $conexao = $sistema->conexao;
        if (!$conexao || $conexao->status !== 'criado') return;

        $pdo    = self::conexaoAdmin();
        $dbName = $conexao->db_name;
        $dbUser = $conexao->db_username;

        // Forçar desconexão de todos os clientes antes de dropar
        $pdo->exec("
            SELECT pg_terminate_backend(pid)
            FROM pg_stat_activity
            WHERE datname = '{$dbName}' AND pid <> pg_backend_pid()
        ");

        $pdo->exec("DROP DATABASE IF EXISTS \"{$dbName}\"");
        $pdo->exec("DROP USER IF EXISTS \"{$dbUser}\"");

        $conexao->delete();
    }

    /**
     * Reconectar e recriar banco se o provisionamento anterior falhou.
     */
    public static function retentar(Sistema $sistema): ConexaoSistema
    {
        $sistema->conexao?->delete();
        return self::provisionar($sistema);
    }

    // ─── Helpers privados ────────────────────────────────────────────────────

    private static function conexaoAdmin(): \PDO
    {
        return new \PDO(
            sprintf(
                'pgsql:host=%s;port=%s;dbname=postgres',
                env('DB_SISTEMAS_HOST'),
                env('DB_SISTEMAS_PORT', 5432)
            ),
            env('DB_SISTEMAS_ADMIN_USER'),
            env('DB_SISTEMAS_ADMIN_PASSWORD'),
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
    }

    private static function conexaoNoBanco(string $dbName): \PDO
    {
        return new \PDO(
            sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                env('DB_SISTEMAS_HOST'),
                env('DB_SISTEMAS_PORT', 5432),
                $dbName
            ),
            env('DB_SISTEMAS_ADMIN_USER'),
            env('DB_SISTEMAS_ADMIN_PASSWORD'),
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
    }

    private static function sanitizarNome(string $slug): string
    {
        // slug 'sistema-financeiro' → 'sistema_financeiro'
        return preg_replace('/[^a-z0-9_]/', '_', strtolower($slug));
    }

    private static function gerarSenha(int $tamanho = 24): string
    {
        // Senha forte: letras, números e símbolos seguros para connection strings
        return substr(
            str_replace(['+', '/', '='], ['A', 'B', 'C'], base64_encode(random_bytes(32))),
            0,
            $tamanho
        );
    }
}
```

---

## SistemaObserver — dispara a criação automaticamente

```php
// app/Observers/SistemaObserver.php

class SistemaObserver
{
    public function created(Sistema $sistema): void
    {
        try {
            DatabaseService::provisionar($sistema);
        } catch (\Exception $e) {
            // Log do erro mas não impede a criação do sistema
            Log::error("Falha ao provisionar banco para sistema [{$sistema->slug}]: " . $e->getMessage());
        }
    }

    public function deleting(Sistema $sistema): void
    {
        // Só deleta banco se o sistema for definitivamente removido (não soft delete)
        if (!$sistema->isForceDeleting()) return;

        try {
            DatabaseService::destruir($sistema);
        } catch (\Exception $e) {
            Log::error("Falha ao destruir banco do sistema [{$sistema->slug}]: " . $e->getMessage());
        }
    }
}
```

### Registrar o observer

```php
// app/Providers/AppServiceProvider.php

public function boot(): void
{
    Sistema::observe(SistemaObserver::class);
    // ...
}
```

---

## SistemaController — exibir credenciais após criação

As credenciais são exibidas **uma única vez** logo após o cadastro. Depois, a senha não é mais exibida em texto plano (só o admin DB pode recuperar).

```php
// app/Http/Controllers/SistemaController.php

public function store(SistemaRequest $request): \Symfony\Component\HttpFoundation\Response
{
    $sistema = Sistema::create($request->validated());
    // O SistemaObserver dispara DatabaseService::provisionar() automaticamente

    $conexao = $sistema->fresh()->conexao;

    if ($conexao && $conexao->status === 'criado') {
        // Retorna credenciais em texto plano APENAS neste momento
        return Inertia::render('Sistema/CredenciaisDatabase', [
            'sistema' => new SistemaResource($sistema),
            'credenciais' => [
                'host'     => $conexao->db_host,
                'port'     => $conexao->db_port,
                'database' => $conexao->db_name,
                'username' => $conexao->db_username,
                'password' => Crypt::decrypt($conexao->db_password), // Descriptografar SÓ aqui
                'aviso'    => 'Salve estas credenciais agora. A senha não será exibida novamente.',
            ],
        ]);
    }

    // Se falhou a criação do banco, redireciona com aviso
    return redirect()->route('sistema.index')
        ->with('warning', 'Sistema criado, mas houve erro ao provisionar o banco. Tente novamente na aba "Banco de Dados".');
}
```

---

## Tela de Credenciais (Vue) — exibição única

Página renderizada logo após criação do sistema: `pages/Sistema/CredenciaisDatabase.vue`

```vue
<template>
    <AppLayout title="Banco criado">
        <div class="max-w-lg mx-auto mt-12">
            <!-- Aviso de exibição única -->
            <div class="mb-6 p-4 bg-[#0f1011] border border-[#d97706] rounded-xl">
                <p class="text-[#d97706] font-medium text-sm">⚠ Atenção</p>
                <p class="text-[#d0d6e0] text-sm mt-1">
                    {{ credenciais.aviso }}
                </p>
            </div>

            <div class="bg-[#0f1011] border border-[#23252a] rounded-xl p-6 space-y-4">
                <h2 class="text-[#f7f8f8] font-semibold text-lg">
                    Credenciais do banco — {{ sistema.nome }}
                </h2>

                <CredencialItem label="Host"     :value="credenciais.host" />
                <CredencialItem label="Porta"    :value="credenciais.port" />
                <CredencialItem label="Database" :value="credenciais.database" />
                <CredencialItem label="Usuário"  :value="credenciais.username" />
                <CredencialItem label="Senha"    :value="credenciais.password" :secret="true" />

                <!-- Bloco .env pronto para copiar -->
                <div class="mt-6">
                    <p class="text-[#8a8f98] text-xs mb-2">Cole no .env do sistema externo:</p>
                    <pre class="bg-[#18191a] border border-[#23252a] rounded-lg p-4 text-[#f7f8f8] text-xs overflow-x-auto">DB_CONNECTION=pgsql
DB_HOST={{ credenciais.host }}
DB_PORT={{ credenciais.port }}
DB_DATABASE={{ credenciais.database }}
DB_USERNAME={{ credenciais.username }}
DB_PASSWORD={{ credenciais.password }}</pre>
                    <button @click="copiar" class="mt-2 text-[#5e6ad2] text-xs hover:text-[#828fff]">
                        Copiar bloco .env
                    </button>
                </div>
            </div>

            <div class="mt-6 flex gap-3">
                <Link :href="route('sistema.index')" class="...">
                    Ir para lista de sistemas
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
defineProps<{
    sistema: { id: number; nome: string; slug: string }
    credenciais: {
        host: string
        port: number
        database: string
        username: string
        password: string
        aviso: string
    }
}>()

function copiar() {
    const bloco = `DB_CONNECTION=pgsql\nDB_HOST=${credenciais.host}\n...`
    navigator.clipboard.writeText(bloco)
}
</script>
```

---

## Endpoint para retentar provisionamento com erro

```php
// routes/web.php
Route::post('/sistema/{sistema}/provisionar-banco', [SistemaController::class, 'provisionarBanco'])
    ->middleware(['auth', 'can:1.2'])
    ->name('sistema.provisionar-banco');

// Controller
public function provisionarBanco(Sistema $sistema): RedirectResponse
{
    try {
        DatabaseService::retentar($sistema);
        return back()->with('success', 'Banco provisionado com sucesso.');
    } catch (\Exception $e) {
        return back()->with('error', 'Erro ao provisionar: ' . $e->getMessage());
    }
}
```

---

## Aba "Banco de Dados" no formulário do sistema (edição)

Na tela de Criar/Editar Sistema, a aba de banco de dados muda de comportamento:

| Situação | O que exibe |
|----------|-------------|
| Sistema novo (criação) | Aba oculta — banco será criado automaticamente |
| Status `pendente` | "Aguardando provisionamento..." + botão "Provisionar agora" |
| Status `criado` | Dados da conexão (host, porta, nome do banco, usuário) — **sem senha** |
| Status `erro` | Mensagem de erro + botão "Retentar" |

```vue
<!-- Na aba Banco de Dados do formulário de edição -->
<template v-if="sistema.conexao?.status === 'criado'">
    <p class="text-[#8a8f98] text-sm mb-4">
        Banco provisionado automaticamente. A senha não é exibida após a criação.
        Se precisar redefinir, use o botão abaixo.
    </p>
    <div class="space-y-2">
        <InfoField label="Host"     :value="sistema.conexao.db_host" />
        <InfoField label="Porta"    :value="sistema.conexao.db_port" />
        <InfoField label="Database" :value="sistema.conexao.db_name" />
        <InfoField label="Usuário"  :value="sistema.conexao.db_username" />
        <InfoField label="Senha"    value="••••••••••••••••" />
    </div>
</template>

<template v-else-if="sistema.conexao?.status === 'erro'">
    <div class="p-4 border border-[#e5534b] rounded-xl text-[#e5534b] text-sm">
        {{ sistema.conexao.erro_mensagem }}
    </div>
    <Button @click="retentar" class="mt-4">Retentar provisionamento</Button>
</template>
```

---

## Segurança

| Risco | Mitigação |
|-------|-----------|
| Senha exposta no banco | Armazenada com `Crypt::encrypt` (AES-256-CBC via `APP_KEY`) |
| Senha exposta em logs | `DatabaseService` nunca loga a senha, apenas o nome do banco/usuário |
| Acesso do usuário do banco | Criado com permissões mínimas — apenas no seu banco |
| Injeção de SQL no nome | `sanitizarNome()` permite apenas `[a-z0-9_]` |
| Exposição via API | Endpoint de credenciais requer permissão `1.2` (editar sistemas) |
| Banco órfão (sistema deletado) | `SistemaObserver::deleting()` limpa banco e usuário — exige confirmação dupla no frontend |

---

## Convenção de nomes gerados

| Input (slug) | DB Name | DB User |
|---|---|---|
| `financeiro` | `financeiro` | `usr_financeiro` |
| `sistema-rh` | `sistema_rh` | `usr_sistema_rh` |
| `portal-acesso` | `portal_acesso` | `usr_portal_acesso` |

---

## Diagrama do fluxo

```
Administrador cadastra novo sistema
         ↓
SistemaController::store()
         ↓
Sistema::create() → banco salvo
         ↓
SistemaObserver::created() (automático)
         ↓
DatabaseService::provisionar()
    ├─ Conecta ao servidor PostgreSQL admin
    ├─ CREATE DATABASE "{slug}"
    ├─ CREATE USER "usr_{slug}" WITH PASSWORD '{senha}'
    ├─ GRANT ALL PRIVILEGES ON DATABASE
    ├─ GRANT ALL ON SCHEMA public
    └─ ConexaoSistema::create() com senha criptografada
         ↓
SistemaController retorna página CredenciaisDatabase
         ↓
Admin vê credenciais UMA VEZ → copia para .env do sistema externo
         ↓
Credenciais nunca mais exibidas em texto plano
```
