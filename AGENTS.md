# AGENTS.md — Guia para Agentes de IA

## Contexto do projeto

Este é um **sistema de login universal próprio** — autenticação centralizada com controle de acesso (ACL) para múltiplos sistemas. Sistemas externos se cadastram aqui, redirecionam usuários para login, e recebem de volta os dados e permissões via API.

**Autenticação é 100% própria: email + senha.** Não há Gov.br, LDAP, OAuth externo ou qualquer integração de terceiros.

O modelo de referência é o sistema ACL em `/home/guilherme-santos/projetos/login-system/acl/`. Consulte aquele diretório para ver padrões de implementação — mas ignore tudo que for `GovBrService`, `LdapService`, `TurmalinaService`, `SafiraService` e qualquer integração com serviços externos.

---

## Stack

- **Laravel 13** (PHP 8.3+)
- **Vue 3** + TypeScript + **Inertia.js**
- **PostgreSQL**
- **Vite**

---

## Documentação completa

Leia nesta ordem antes de implementar qualquer coisa:

| Arquivo | Conteúdo |
|---------|----------|
| [docs/00-OVERVIEW.md](docs/00-OVERVIEW.md) | Visão geral, escopo, ordem de implementação |
| [docs/01-DATABASE.md](docs/01-DATABASE.md) | Schema completo do banco, todas as migrations |
| [docs/02-AUTHENTICATION.md](docs/02-AUTHENTICATION.md) | Fluxo de login local (email/senha) |
| [docs/03-PERMISSIONS.md](docs/03-PERMISSIONS.md) | Sistema de permissões com Laravel Gates |
| [docs/04-API.md](docs/04-API.md) | Endpoints para sistemas externos |
| [docs/05-FRONTEND.md](docs/05-FRONTEND.md) | Telas Vue, Inertia, design system |
| [docs/06-INTEGRATION.md](docs/06-INTEGRATION.md) | Integração genérica de sistemas externos |
| [docs/07-SETUP.md](docs/07-SETUP.md) | Instalação, .env, deploy |
| [docs/08-SISTEMA-EXTERNO-INTEGRACAO.md](docs/08-SISTEMA-EXTERNO-INTEGRACAO.md) | **Padrão real de integração (baseado no CAISAN)** — AutenticacaoService, sessão, Gates, catálogo de permissões, frontend |
| [docs/09-AUTO-DATABASE.md](docs/09-AUTO-DATABASE.md) | **Criação automática de banco PostgreSQL** por sistema — DatabaseService, SistemaObserver, tela de credenciais |
| [docs/ACL-DESCRICAO.md](docs/ACL-DESCRICAO.md) | Descrição do produto (para IAs de design) |
| [docs/UX-TELAS.md](docs/UX-TELAS.md) | Especificação UX completa de todas as telas |
| [docs/SYSTEM-DESIGN.md](docs/SYSTEM-DESIGN.md) | Design system Linear-inspired (cores, tipografia, tokens) |

---

## Estado real do projeto (ler antes de qualquer coisa)

O projeto é um **Laravel 13 starter kit com Vue/Inertia**. Antes de implementar, entenda o que já está instalado e como difere do padrão antigo:

### Tecnologias específicas deste starter kit

| Tecnologia | Versão/Detalhe | Impacto no código |
|---|---|---|
| Inertia.js | **v3** com `@inertiajs/vite` | `app.ts` é mínimo — sem `resolvePageComponent` ou `createApp` manual |
| Tailwind CSS | **v4** (plugin Vite) | Sem `tailwind.config.js` — customização via `app.css` com `@theme` |
| Rotas no frontend | **Wayfinder** | Sem Ziggy. Importar ações de `@/actions/`. Gerar com `php artisan wayfinder:generate` |
| Props do user | `auth.user` | O HandleInertiaRequests usa `auth: { user: ... }`, não `user` direto |

### O que já existe no projeto

```
✅ Laravel 13
✅ Inertia.js v3 + Vue 3 + TypeScript
✅ Tailwind CSS v4
✅ Wayfinder (gerador de rotas tipadas)
✅ VueUse (@vueuse/core)
✅ HandleInertiaRequests.php (precisa atualizar share())
✅ Migration padrão de users (substituir pela de 01-DATABASE.md)
✅ Migration de cache e jobs
✅ resources/js/lib/utils.ts com cn() (clsx + tailwind-merge)
✅ resources/js/types/auth.ts e index.ts (atualizar)
❌ Migrations do sistema (criar conforme 01-DATABASE.md)
❌ Models com relacionamentos
❌ Controllers de autenticação
❌ AutenticacaoService, ValidarService, PermissaoService
❌ API de validação de token (routes/api.php)
❌ Telas Vue (Login, Dashboard, CRUDs)
❌ Seeders
```

---

## Projeto de referência real: CAISAN

O CAISAN (`/home/guilherme-santos/projetos/login-system/caisan/`) é um sistema Laravel 12 + Vue 3 real que **já integra com o ACL**. É a referência mais concreta de como um sistema externo funciona na prática.

**O que o CAISAN revela sobre o padrão de integração:**

| Aspecto | Como o CAISAN faz |
|---------|-----------------|
| Rota de callback | `GET /login?callback={token}` |
| Troca de token | `HTTP POST {ACL_URL}/api/login` com `{ token, sistema: ACL_SLUG }` |
| Sessão | `session('user')['permissoes'][orgao_id][gate_id]` |
| Gates | Registrados dinamicamente no `PermissionServiceProvider` por orgao_atual |
| Catálogo de permissões | `PermissaoAcoesCatalogo::gates()` — mapeia nomes legíveis para IDs de gate |
| Frontend | Mixin global com `can(id)` e `podeAcao('feature.acao')` |
| Usuário local | Tabela `usuarios` mínima — `updateOrCreate` por email a cada login |
| Cookie SSO | `SSO-USER-ACL={user_id}` válido 30 dias |

**Leia `docs/08-SISTEMA-EXTERNO-INTEGRACAO.md`** para o detalhamento completo com código.

---

## O que copiar do ACL (adaptando)

| ACL (origem) | Login (destino) | Adaptações |
|---|---|---|
| `acl/app/Http/Services/Autenticacao/AutenticacaoService.php` | `app/Http/Services/Autenticacao/AutenticacaoService.php` | Remover GovBr e LDAP; manter apenas o `login()` que gera o token callback |
| `acl/app/Http/Services/Autenticacao/ValidarService.php` | `app/Http/Services/Autenticacao/ValidarService.php` | Nenhuma |
| `acl/app/Http/Services/PermissaoService.php` | `app/Http/Services/PermissaoService.php` | Nenhuma |
| `acl/app/Http/Middleware/ApiAuth.php` | `app/Http/Middleware/ApiAuth.php` | Nenhuma |
| `acl/app/Http/Controllers/AutenticacaoController.php` | `app/Http/Controllers/AutenticacaoController.php` | Remover métodos govbr/ldap; manter index, store e logout |
| `acl/app/Http/Resources/UserResource.php` | `app/Http/Resources/UserResource.php` | Remover campos específicos de governo (cpf, origem, nivel, selos) |
| `acl/app/Http/Middleware/HandleInertiaRequests.php` | `app/Http/Middleware/HandleInertiaRequests.php` | Adaptar ao share() simplificado |

## O que NÃO copiar

- `GovBrService.php` — não usa Gov.br
- `LdapService.php` — não usa LDAP
- `AutenticacaoLdapController.php` — não usa LDAP
- `TurmalinaService.php`, `SafiraService.php` — não usa Oracle
- `Console/Commands/Turmalina*`, `Console/Commands/Safira*`
- Qualquer migration de `turmalina_*` ou `unidades_safira`

---

## Convenções de código

- Controllers sem comentários desnecessários
- Services para toda lógica de negócio
- API Resources para respostas JSON
- Form Requests para validação de entrada
- `softDeletes()` + `created_by/updated_by/deleted_by` nas tabelas definidas em `01-DATABASE.md`

---

## Fluxo crítico para validar após implementar

```
1. GET /login/sistema-teste               → renderiza Login.vue com dados do sistema
2. POST /login { email, password }        → autentica, gera token, redireciona
3. Sistema externo recebe ?callback=xxx
4. GET /api/v1/login/sistema-teste?token=xxx → retorna JSON do usuário
5. Token expirado/inválido                → retorna 401
6. GET /logout                            → invalida sessão, redireciona para /login
```

---

## Comandos úteis

```bash
composer run dev          # Tudo junto (servidor + vite + queue + logs)
php artisan migrate:fresh --seed   # Recriar banco do zero
php artisan route:list    # Ver rotas registradas
php artisan optimize:clear # Limpar caches
```
