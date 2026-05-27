# Frontend — Telas, Componentes e Customização

## Stack (o que está instalado no projeto)

- **Vue 3** + Composition API + `<script setup>`
- **TypeScript**
- **Inertia.js v3** com plugin `@inertiajs/vite`
- **Tailwind CSS v4** (via plugin Vite — sem `tailwind.config.js`)
- **Wayfinder** — gerador de tipos de rotas do Laravel (substitui Ziggy)
- **VueUse** (`@vueuse/core`)
- **Vite**

---

## Como o Wayfinder funciona (importante)

O Wayfinder gera arquivos TypeScript a partir das rotas PHP. Em vez de `route('login.store')` (Ziggy), você importa a ação diretamente:

```typescript
// Gerado automaticamente em resources/js/actions/
import LoginStore from '@/actions/Auth/LoginStore'

// Uso em form:
useForm({ ... }).post(LoginStore.url())

// Ou como action de formulário HTML:
// { action: LoginStore.url(), method: 'post' }
```

**Para gerar os arquivos de rotas após criar novas rotas PHP:**
```bash
php artisan wayfinder:generate
```

---

## Estrutura de pastas (respeitando o que já existe)

```
resources/
├── css/
│   └── app.css                 # Tailwind v4: @import 'tailwindcss'
├── js/
│   ├── app.ts                  # Entry point Inertia v3 (mínimo)
│   ├── actions/                # Gerado pelo Wayfinder (não editar manualmente)
│   ├── wayfinder/              # Utilitários do Wayfinder (não editar)
│   ├── lib/
│   │   └── utils.ts            # cn() helper (clsx + tailwind-merge)
│   ├── pages/                  # Páginas renderizadas pelo Inertia
│   │   ├── Welcome.vue         # Já existe (substituir ou ignorar)
│   │   ├── Auth/
│   │   │   └── Login.vue       # Tela de login
│   │   ├── Dashboard.vue
│   │   ├── Usuario/
│   │   ├── Sistema/
│   │   ├── Orgao/
│   │   ├── Lotacao/
│   │   └── Sessoes/
│   ├── components/
│   │   ├── Layout/
│   │   │   ├── AppLayout.vue
│   │   │   └── AuthLayout.vue
│   │   └── Ui/
│   ├── composables/
│   │   └── usePermissions.ts
│   └── types/
│       ├── index.ts            # Re-exporta tudo
│       ├── auth.ts             # Tipos de user/auth (já existe)
│       └── acl.ts              # Novos tipos do sistema ACL
└── views/
    └── app.blade.php
```

---

## Template Blade (`resources/views/app.blade.php`)

Já existe no projeto. Verificar se está assim:

```blade
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>{{ config('app.name', 'Login Universal') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
    @inertiaHead
</head>
<body class="antialiased">
    @inertia
</body>
</html>
```

---

## Entry point (`resources/js/app.ts`)

O projeto usa Inertia v3 com `@inertiajs/vite`. O `app.ts` é mínimo — o plugin Vite resolve as páginas automaticamente:

```typescript
import { createInertiaApp } from '@inertiajs/vue3';

const appName = import.meta.env.VITE_APP_NAME || 'Login Universal';

createInertiaApp({
    title: (title) => (title ? `${title} — ${appName}` : appName),
    progress: {
        color: '#5e6ad2', // {colors.primary} — SYSTEM-DESIGN.md
    },
});
```

**Não adicionar** `resolvePageComponent`, `ZiggyVue` ou `createApp` manual — o plugin cuida disso.

---

## HandleInertiaRequests Middleware

O arquivo já existe em `app/Http/Middleware/HandleInertiaRequests.php`. Substituir o conteúdo do `share()`:

```php
public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'auth' => [
            'user' => fn () => $request->user()?->load([
                'lotacoes.orgao',
                'lotacoes.lotacao',
            ]),
        ],
        'gates' => fn () => auth()->check()
            ? PermissaoService::getPermissions()
            : [],
        'flash' => [
            'success' => fn () => session('success'),
            'error'   => fn () => session('error'),
            'info'    => fn () => session('info'),
        ],
    ];
}
```

**Nota:** o user fica em `auth.user` (não `user` direto), seguindo o padrão do starter kit.

---

## Tipos TypeScript

### `resources/js/types/acl.ts` (criar)

```typescript
export interface UserLotacao {
    id: number
    administrador: boolean
    lotacao_exercicio: boolean
    status: 'S' | 'N'
    orgao: Orgao
    lotacao: Lotacao | null
}

export interface Sistema {
    id: number
    nome: string
    slug: string
    url: string
    url_logout: string | null
    ambiente: 'production' | 'homologacao' | 'desenvolvimento'
    descricao: string | null
    caminho_logo: string | null
    caminho_ilustracao: string | null
    ativo: boolean
}

export interface Orgao {
    id: number
    descricao_orgao: string
    sigla_orgao: string | null
    status: 'ativo' | 'inativo'
}

export interface Lotacao {
    id: number
    nome_lotacao: string
    sigla_lotacao: string | null
    nivel_hierarquico: number
}
```

### `resources/js/types/auth.ts` (atualizar o existente)

```typescript
import type { UserLotacao } from './acl'

export type User = {
    id: number
    name: string
    email: string
    status_usuario: 'S' | 'N'
    ultimo_login: string | null
    lotacoes: UserLotacao[]
    [key: string]: unknown
}

export type Auth = {
    user: User | null
}
```

### `resources/js/types/index.ts` (já existe — adicionar export)

```typescript
export * from './auth'
export * from './acl'
```

---

## Props compartilhados (usePage)

```typescript
import { usePage } from '@inertiajs/vue3'
import type { Auth } from '@/types'

// O user fica em auth.user (não user direto)
const page = usePage<{
    auth: Auth
    gates: Record<string, boolean>
    flash: { success: string | null; error: string | null; info: string | null }
}>()

const user = page.props.auth.user
```

---

## Composable de permissões (`composables/usePermissions.ts`)

```typescript
import { usePage } from '@inertiajs/vue3'

export function usePermissions() {
    const page = usePage<{ gates: Record<string, boolean> }>()

    const can = (permission: string): boolean =>
        page.props.gates?.[permission] ?? false

    const canAny = (...permissions: string[]): boolean =>
        permissions.some(p => can(p))

    const isAdmin = (): boolean => can('administrador')

    return { can, canAny, isAdmin }
}
```

---

## Tela de Login (`pages/Auth/Login.vue`)

O controller passa os seguintes props:

```php
return Inertia::render('Auth/Login', [
    'sistema' => $sistema ? new SistemaResource($sistema) : null,
]);
```

### Componente

```vue
<template>
    <!-- canvas: {colors.canvas} #010102 -->
    <div class="min-h-screen flex bg-[#010102]">
        <!-- Painel esquerdo: identidade visual do sistema — {colors.primary} ou ilustração -->
        <div class="hidden lg:flex lg:w-1/2 bg-[#5e6ad2] items-center justify-center">
            <img
                v-if="sistema?.caminho_ilustracao"
                :src="sistema.caminho_ilustracao"
                alt=""
                class="max-w-md"
            />
            <div v-else class="text-white text-center px-8">
                <h1 class="text-3xl font-semibold tracking-tight">{{ sistema?.nome ?? 'Login Universal' }}</h1>
                <p v-if="sistema?.descricao" class="mt-2 text-white/70">
                    {{ sistema.descricao }}
                </p>
            </div>
        </div>

        <!-- Painel direito: formulário — {colors.canvas} -->
        <div class="flex-1 flex items-center justify-center p-8">
            <div class="w-full max-w-sm">
                <div class="text-center mb-8">
                    <img
                        v-if="sistema?.caminho_logo"
                        :src="sistema.caminho_logo"
                        :alt="sistema.nome"
                        class="h-12 mx-auto"
                    />
                    <!-- {colors.ink} #f7f8f8 -->
                    <h2 class="text-2xl font-semibold text-[#f7f8f8] mt-4 tracking-tight">
                        {{ sistema?.nome ?? 'Login Universal' }}
                    </h2>
                    <!-- {colors.ink-subtle} #8a8f98 -->
                    <p class="text-sm text-[#8a8f98] mt-1">Entre com suas credenciais</p>
                </div>

                <!-- Erro flash — borda {colors.error} #e5534b -->
                <div
                    v-if="$page.props.flash.error"
                    class="mb-4 p-3 bg-[#0f1011] border border-[#e5534b] text-[#f7f8f8] rounded-lg text-sm"
                >
                    {{ $page.props.flash.error }}
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <!-- {colors.ink-muted} #d0d6e0 -->
                        <label class="block text-sm font-medium text-[#d0d6e0] mb-1">
                            E-mail
                        </label>
                        <!-- text-input: surface-1 + hairline border + focus ring primary -->
                        <input
                            v-model="form.email"
                            type="email"
                            autocomplete="email"
                            class="w-full bg-[#0f1011] border border-[#23252a] text-[#f7f8f8] rounded-lg px-3 py-2 text-sm placeholder-[#8a8f98] focus:outline-none focus:ring-2 focus:ring-[#5e6ad2]/50 focus:border-[#34343a]"
                            :class="{ 'border-[#e5534b]': form.errors.email }"
                        />
                        <p v-if="form.errors.email" class="mt-1 text-xs text-[#e5534b]">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-[#d0d6e0] mb-1">
                            Senha
                        </label>
                        <input
                            v-model="form.password"
                            type="password"
                            autocomplete="current-password"
                            class="w-full bg-[#0f1011] border border-[#23252a] text-[#f7f8f8] rounded-lg px-3 py-2 text-sm placeholder-[#8a8f98] focus:outline-none focus:ring-2 focus:ring-[#5e6ad2]/50 focus:border-[#34343a]"
                            :class="{ 'border-[#e5534b]': form.errors.password }"
                        />
                        <p v-if="form.errors.password" class="mt-1 text-xs text-[#e5534b]">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <input
                            id="remember"
                            v-model="form.remember"
                            type="checkbox"
                            class="rounded border-[#34343a] bg-[#0f1011] accent-[#5e6ad2]"
                        />
                        <!-- {colors.ink-subtle} -->
                        <label for="remember" class="text-sm text-[#8a8f98]">
                            Lembrar de mim
                        </label>
                    </div>

                    <!-- button-primary: {colors.primary} + hover {colors.primary-hover} -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full bg-[#5e6ad2] text-white py-2 rounded-lg text-sm font-medium hover:bg-[#828fff] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ form.processing ? 'Entrando...' : 'Entrar' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import type { Sistema } from '@/types'

// As rotas são geradas pelo Wayfinder. Após criar a rota login.store no PHP,
// rodar: php artisan wayfinder:generate
// Aí importar: import LoginStore from '@/actions/Auth/LoginStore'
// e usar: form.post(LoginStore.url())

defineProps<{
    sistema: Pick<Sistema, 'nome' | 'slug' | 'caminho_logo' | 'caminho_ilustracao' | 'descricao'> | null
}>()

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

function submit() {
    // Após gerar com Wayfinder, trocar '/login' pela importação tipada
    form.post('/login', {
        onFinish: () => form.reset('password'),
    })
}
</script>
```

---

## Dashboard (`pages/Dashboard.vue`)

```vue
<template>
    <AppLayout title="Dashboard">
        <!-- canvas: {colors.canvas} #010102 -->
        <div class="p-6">
            <!-- {colors.ink} #f7f8f8 -->
            <h1 class="text-2xl font-semibold text-[#f7f8f8] tracking-tight">
                Bem-vindo, {{ auth.user?.name }}
            </h1>

            <div class="mt-6 grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- card: surface-1 + hairline + hover surface-2 com borda primary -->
                <a
                    v-if="can('1.4')"
                    href="/sistema"
                    class="p-4 bg-[#0f1011] border border-[#23252a] rounded-xl hover:border-[#5e6ad2] hover:bg-[#141516] transition-colors"
                >
                    <p class="font-medium text-[#f7f8f8]">Sistemas</p>
                </a>
                <a
                    v-if="can('2.4')"
                    href="/usuario"
                    class="p-4 bg-[#0f1011] border border-[#23252a] rounded-xl hover:border-[#5e6ad2] hover:bg-[#141516] transition-colors"
                >
                    <p class="font-medium text-[#f7f8f8]">Usuários</p>
                </a>
                <a
                    v-if="can('3.4')"
                    href="/orgao"
                    class="p-4 bg-[#0f1011] border border-[#23252a] rounded-xl hover:border-[#5e6ad2] hover:bg-[#141516] transition-colors"
                >
                    <p class="font-medium text-[#f7f8f8]">Órgãos</p>
                </a>
                <a
                    v-if="can('7')"
                    href="/sessoes"
                    class="p-4 bg-[#0f1011] border border-[#23252a] rounded-xl hover:border-[#5e6ad2] hover:bg-[#141516] transition-colors"
                >
                    <p class="font-medium text-[#f7f8f8]">Sessões</p>
                </a>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import AppLayout from '@/components/Layout/AppLayout.vue'
import { usePermissions } from '@/composables/usePermissions'
import type { Auth } from '@/types'

const { can } = usePermissions()
const { auth } = usePage<{ auth: Auth }>().props
</script>
```

---

## Tailwind v4 — configuração do design system

O Tailwind v4 não usa `tailwind.config.js`. Todos os tokens do `docs/SYSTEM-DESIGN.md` são declarados no `resources/css/app.css` via `@theme`:

```css
@import 'tailwindcss';

@theme inline {
    /* Tokens do SYSTEM-DESIGN.md */

    /* Canvas e surfaces */
    --color-canvas:    #010102;
    --color-surface-1: #0f1011;
    --color-surface-2: #141516;
    --color-surface-3: #18191a;

    /* Bordas */
    --color-hairline:          #23252a;
    --color-hairline-strong:   #34343a;
    --color-hairline-tertiary: #3e3e44;

    /* Primária (lavender-blue) */
    --color-primary:       #5e6ad2;
    --color-on-primary:    #ffffff;
    --color-primary-hover: #828fff;
    --color-primary-focus: #5e69d1;

    /* Texto */
    --color-ink:          #f7f8f8;
    --color-ink-muted:    #d0d6e0;
    --color-ink-subtle:   #8a8f98;
    --color-ink-tertiary: #62666d;

    /* Semânticas */
    --color-success: #27a644;
    --color-error:   #e5534b;

    /* Border radius (rounded) */
    --radius-xs:   4px;
    --radius-sm:   6px;
    --radius-md:   8px;
    --radius-lg:   12px;
    --radius-xl:   16px;
    --radius-xxl:  24px;
    --radius-pill: 9999px;
}

@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../../storage/framework/views/*.php';
```

Com isso, as classes Tailwind ficam semanticamente alinhadas com o design system:

```html
<!-- Exemplos de uso com as variáveis definidas -->
<div class="bg-canvas text-ink">                    <!-- página -->
<div class="bg-surface-1 border border-hairline">   <!-- card -->
<button class="bg-primary text-on-primary hover:bg-primary-hover"> <!-- CTA -->
<span class="text-success">Ativo</span>             <!-- badge sucesso -->
<span class="text-error">Erro</span>                <!-- mensagem erro -->
```

Não adicionar `@apply` desnecessário — usar classes diretamente no template.
