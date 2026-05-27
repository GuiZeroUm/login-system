# Sistema de Login Universal — Visão Geral

## O que é este projeto

Um **sistema centralizado de autenticação e controle de acesso (ACL)** próprio. Ele funciona como um middleware de identidade: outros sistemas se registram nele, e ao invés de cada sistema ter sua própria tela de login, eles redirecionam para cá, recebem de volta um token com os dados e permissões do usuário.

O fluxo básico é:
1. Usuário acessa `sistema-externo.com`, que não tem login próprio
2. `sistema-externo.com` redireciona para `login.app/login/sistema-externo`
3. Usuário faz login com email e senha
4. Este sistema gera um token criptografado e redireciona de volta: `sistema-externo.com?callback={token}`
5. `sistema-externo.com` troca o token em `/api/v1/login/sistema-externo?token={token}` e recebe os dados completos do usuário com suas permissões

---

## Stack tecnológica

- **Backend:** Laravel 13 (PHP 8.3+)
- **Frontend:** Vue 3 + Inertia.js + TypeScript
- **Bundler:** Vite
- **Banco principal:** PostgreSQL (configurável)
- **Sessões:** Banco de dados (tabela `sessions`)
- **Autenticação:** Email + senha (credenciais próprias)
- **API de integração:** REST JSON

---

## Projetos de referência

O sistema ACL de referência está em `/home/guilherme-santos/projetos/login-system/acl/`. A arquitetura geral (permissões, token callback, estrutura de órgãos/lotações) foi extraída dele. Consulte o código-fonte do ACL quando houver dúvida sobre padrões de implementação, ignorando tudo que for Gov.br, LDAP e Turmalina.

---

## Escopo do que precisa ser construído

### Módulos principais

| Módulo | Descrição |
|--------|-----------|
| Autenticação | Login/logout com email e senha |
| Usuários | CRUD de usuários |
| Sistemas | Cadastro dos sistemas que vão usar este login |
| Órgãos | Cadastro de órgãos/organizações |
| Lotações | Departamentos dentro dos órgãos |
| Unidades | Subdivisões de órgãos (opcional) |
| Perfis/Roles | Papéis de usuário por sistema |
| Permissões | Controle granular de acesso (CRUD por entidade) |
| API de integração | Endpoints para sistemas externos validarem tokens |
| Sessões | Gerenciamento e revogação de sessões ativas |

### O que NÃO está no escopo

- Gov.br / OAuth2 externo
- LDAP
- Integração Turmalina/Oracle
- Selos de confiabilidade
- Níveis Bronze/Prata/Ouro

---

## Arquivos desta documentação

```
docs/
├── 00-OVERVIEW.md          ← Este arquivo
├── 01-DATABASE.md          ← Esquema completo do banco de dados
├── 02-AUTHENTICATION.md    ← Fluxo de autenticação local
├── 03-PERMISSIONS.md       ← Sistema de permissões ACL
├── 04-API.md               ← Endpoints de integração
├── 05-FRONTEND.md          ← Telas, componentes Vue, Inertia
├── 06-INTEGRATION.md       ← Como sistemas externos se integram
└── 07-SETUP.md             ← Configuração, .env, deploy
```

---

## Ordem recomendada de implementação

1. **Banco de dados** — migrations (ver `01-DATABASE.md`)
2. **Models** — com relacionamentos Eloquent
3. **Autenticação local** — email/senha (ver `02-AUTHENTICATION.md`)
4. **API de validação de token** — para sistemas externos (ver `04-API.md`)
5. **Tela de login** — Vue + Inertia (ver `05-FRONTEND.md`)
6. **CRUD de sistemas** — cadastro dos sistemas clientes
7. **CRUD de usuários, órgãos, lotações**
8. **Sistema de permissões** — gates e policies (ver `03-PERMISSIONS.md`)
9. **Gerenciamento de sessões**
10. **Testes e documentação da API**
