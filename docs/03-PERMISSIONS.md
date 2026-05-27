# Sistema de Permissões (ACL)

## Modelo conceitual

As permissões seguem uma hierarquia em 3 camadas:

```
Usuário
  └── Lotações (vínculo com órgão + departamento)
        ├── Perfis (Roles atribuídos à lotação)
        │     └── Permissões do perfil
        └── Permissões diretas (override por usuário)
```

Um usuário pode ter múltiplas lotações (pode trabalhar em mais de um órgão/departamento). As permissões são sempre contextuais à lotação.

---

## Tipos de permissão

### Permissão CRUD (tipo_crud = 'S')

Gera 4 operações separadas para uma entidade:
- `{permission_id}.1` → Criar
- `{permission_id}.2` → Editar
- `{permission_id}.3` → Excluir
- `{permission_id}.4` → Visualizar

**Exemplo:** Permissão "Usuários" (id=2) com tipo_crud='S' gera:
- `2.1` → Pode criar usuários
- `2.2` → Pode editar usuários
- `2.3` → Pode excluir usuários
- `2.4` → Pode visualizar usuários

### Permissão simples (tipo_crud = 'N')

Gera 1 gate de acesso geral:
- `{permission_id}` → Tem acesso à funcionalidade

**Exemplo:** Permissão "Sessões" (id=3) com tipo_crud='N' gera:
- `3` → Pode gerenciar sessões

---

## Implementação com Laravel Gates

### PermissaoService

Arquivo: `app/Http/Services/PermissaoService.php`

Este service é chamado no `AppServiceProvider::boot()` e registra todos os Gates.

```php
class PermissaoService
{
    public static function setPermissoes(): void
    {
        // Gate especial: administrador global
        Gate::define('administrador', function (User $user) {
            return $user->lotacoes()
                ->where('administrador', true)
                ->where('status', 'S')
                ->exists();
        });

        // Gate especial: acesso ao sistema (tem qualquer permissão)
        Gate::define('sistema', function (User $user) {
            return Gate::allows('administrador') ||
                   $user->lotacoes()
                       ->where('status', 'S')
                       ->whereHas('perfis.role.permissions')
                       ->orWhereHas('permissoes')
                       ->exists();
        });

        // Registra gates para cada permissão cadastrada
        $permissoes = Permission::with('sistema')->get();

        foreach ($permissoes as $permissao) {
            if ($permissao->tipo_crud === 'S') {
                // 4 gates: criar, editar, excluir, visualizar
                foreach ([1, 2, 3, 4] as $tipo) {
                    Gate::define("{$permissao->id}.{$tipo}", function (User $user) use ($permissao, $tipo) {
                        return self::checkPermissao($user, $permissao, $tipo);
                    });
                }
            } else {
                // 1 gate: acesso
                Gate::define((string) $permissao->id, function (User $user) use ($permissao) {
                    return self::checkPermissao($user, $permissao, 0);
                });
            }
        }
    }

    private static function checkPermissao(User $user, Permission $permissao, int $tipo): bool
    {
        // Administrador tem tudo
        if (Gate::allows('administrador')) {
            return true;
        }

        $sistemaId = $permissao->sistema_id;

        // Verifica via permissão direta
        $temDireto = $user->lotacoes()
            ->where('status', 'S')
            ->whereHas('permissoes', function ($q) use ($permissao, $tipo) {
                $q->whereHas('permissao', function ($q2) use ($permissao) {
                    $q2->where('permissao_id_sistema', $permissao->id)
                       ->where('sistema_id', $permissao->sistema_id);
                })->where('tipo', $tipo);
            })->exists();

        if ($temDireto) return true;

        // Verifica via perfil/role
        return $user->lotacoes()
            ->where('status', 'S')
            ->whereHas('perfis.role', function ($q) use ($sistemaId) {
                $q->where('sistema_id', $sistemaId);
            })
            ->whereHas('perfis.role.permissions', function ($q) use ($permissao, $tipo) {
                $q->whereHas('permissao', function ($q2) use ($permissao) {
                    $q2->where('permissao_id_sistema', $permissao->id)
                       ->where('sistema_id', $permissao->sistema_id);
                })->where('tipo', $tipo);
            })->exists();
    }

    public static function getPermissions(): array
    {
        if (!auth()->check()) return [];

        $user  = auth()->user();
        $gates = [];

        $permissoes = Permission::all();

        foreach ($permissoes as $permissao) {
            if ($permissao->tipo_crud === 'S') {
                foreach ([1, 2, 3, 4] as $tipo) {
                    $key         = "{$permissao->id}.{$tipo}";
                    $gates[$key] = Gate::allows($key);
                }
            } else {
                $key         = (string) $permissao->id;
                $gates[$key] = Gate::allows($key);
            }
        }

        $gates['administrador'] = Gate::allows('administrador');
        $gates['sistema']       = Gate::allows('sistema');

        return $gates;
    }
}
```

### Registrar no AppServiceProvider

```php
// app/Providers/AppServiceProvider.php

public function boot(): void
{
    if (auth()->check()) {
        PermissaoService::setPermissoes();
    }
}
```

---

## Uso em Controllers

### Middleware em controllers

```php
// app/Http/Controllers/UsuarioController.php

public static function middleware(): array
{
    return [
        'auth',
        new Middleware('can:sistema'),         // Precisa ter acesso ao sistema
        new Middleware('can:2.4', only: ['index', 'show']),     // Visualizar
        new Middleware('can:2.1', only: ['create', 'store']),   // Criar
        new Middleware('can:2.2', only: ['edit', 'update']),    // Editar
        new Middleware('can:2.3', only: ['destroy']),           // Excluir
    ];
}
```

### Verificação manual

```php
if (Gate::allows('administrador')) { ... }
if (Gate::allows('2.4')) { ... }             // Pode visualizar usuários?
if (Gate::denies('2.1')) { abort(403); }     // Não pode criar?
```

---

## Models com relacionamentos

### User

```php
class User extends Authenticatable
{
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

### UserLotacao

```php
class UserLotacao extends Model
{
    use SoftDeletes;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orgao(): BelongsTo
    {
        return $this->belongsTo(Orgao::class);
    }

    public function lotacao(): BelongsTo
    {
        return $this->belongsTo(Lotacao::class);
    }

    public function perfis(): HasMany
    {
        return $this->hasMany(UserPerfil::class)->withoutTrashed();
    }

    public function permissoes(): HasMany
    {
        return $this->hasMany(UserPermission::class)->withoutTrashed();
    }
}
```

### Role

```php
class Role extends Model
{
    use SoftDeletes;

    public function sistema(): BelongsTo
    {
        return $this->belongsTo(Sistema::class);
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(RoleHasPermission::class);
    }
}
```

### RoleObserver

O ACL tem um observer que remove permissões ao deletar um role. Criar em `app/Observers/RoleObserver.php`:

```php
class RoleObserver
{
    public function deleting(Role $role): void
    {
        $role->permissions()->delete();
        UserPerfil::where('role_id', $role->id)->delete();
    }
}
```

---

## Compartilhando gates com o frontend (Inertia)

No middleware `HandleInertiaRequests`:

```php
public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'user'  => fn () => $request->user()?->load('lotacoes.lotacao'),
        'gates' => fn () => auth()->check() ? PermissaoService::getPermissions() : [],
        'flash' => [
            'success' => fn () => session('success'),
            'error'   => fn () => session('error'),
        ],
    ];
}
```

### Uso no Vue

```typescript
// composables/usePermissions.ts
import { usePage } from '@inertiajs/vue3'

export function usePermissions() {
    const page = usePage()
    
    const can = (permission: string): boolean => {
        return page.props.gates?.[permission] ?? false
    }
    
    const isAdmin = (): boolean => can('administrador')
    
    return { can, isAdmin }
}
```

```vue
<template>
    <button v-if="can('2.1')">Criar Usuário</button>
    <button v-if="can('2.3')">Excluir</button>
</template>

<script setup>
const { can } = usePermissions()
</script>
```

---

## Vinculação de permissões no CRUD de usuários

Ao criar/editar um usuário no sistema:

```php
// 1. Criar/atualizar UserLotacao
$userLotacao = UserLotacao::updateOrCreate(
    ['user_id' => $user->id, 'orgao_id' => $orgaoId],
    ['lotacao_id' => $lotacaoId, 'status' => 'S']
);

// 2. Atribuir um role ao usuário naquela lotação
UserPerfil::create([
    'role_id'         => $roleId,
    'user_lotacao_id' => $userLotacao->id,
    'created_by'      => auth()->id(),
]);

// 3. Ou atribuir permissão direta (sem role)
UserPermission::create([
    'permission_id'   => $permissionId,
    'user_lotacao_id' => $userLotacao->id,
    'tipo'            => 4, // visualizar
    'created_by'      => auth()->id(),
]);
```

---

## Permissões padrão do sistema de login (seed)

IDs fixos para referenciar no código:

| ID | Nome | tipo_crud | Gates gerados |
|----|------|-----------|---------------|
| 1  | Sistemas | S | 1.1, 1.2, 1.3, 1.4 |
| 2  | Usuários | S | 2.1, 2.2, 2.3, 2.4 |
| 3  | Órgãos | S | 3.1, 3.2, 3.3, 3.4 |
| 4  | Lotações | S | 4.1, 4.2, 4.3, 4.4 |
| 5  | Unidades | S | 5.1, 5.2, 5.3, 5.4 |
| 6  | Perfis | S | 6.1, 6.2, 6.3, 6.4 |
| 7  | Permissões | S | 7.1, 7.2, 7.3, 7.4 |
| 8  | Sessões | N | 8 |

Os IDs dos gates de permissão devem ser **estáveis** — não mudar após produção, pois os controllers referenciam por ID numérico.
