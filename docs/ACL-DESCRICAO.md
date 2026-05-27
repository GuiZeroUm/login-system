# Descrição do Produto — Sistema de Login Universal (ACL)

> Este documento descreve o produto de forma conceitual e funcional. Destina-se a IAs de design, prototipação e desenvolvimento que precisam entender o que é o sistema, como ele funciona e quem o usa — antes de criar qualquer tela ou componente.

---

## O que é o sistema

O **Login Universal** é um sistema centralizado de autenticação e controle de acesso (ACL — Access Control List). Ele funciona como um **hub de identidade** para múltiplos sistemas internos.

Em vez de cada sistema ter sua própria tela de login, banco de usuários e lógica de permissões, todos eles apontam para o Login Universal. Ele é o único lugar onde um usuário existe, faz login e tem suas permissões definidas.

A analogia mais próxima é o **"Entrar com Google"** — mas completamente próprio, sem dependência de terceiros, e com um sistema de permissões granular embutido.

---

## Quem usa

### Perfil 1: Administrador do sistema de login
- Gerencia tudo: sistemas cadastrados, usuários, órgãos, permissões
- Acessa o painel administrativo do Login Universal diretamente
- Configura quais permissões cada usuário tem em cada sistema

### Perfil 2: Usuário comum
- Nunca acessa o Login Universal diretamente
- É redirecionado para ele quando tenta acessar algum sistema externo que usa o login centralizado
- Vê apenas a tela de login, autentica, e é redirecionado de volta ao sistema que tentou acessar

### Perfil 3: Desenvolvedor / sistema externo
- Cadastra seu sistema no Login Universal
- Consome a API para receber os dados do usuário após o login
- Não implementa lógica de autenticação — delega tudo para cá

---

## Conceitos fundamentais

### Sistema
Um "sistema" é qualquer aplicação externa que usa o Login Universal como provedor de identidade. Cada sistema tem:
- Um **slug** único (ex: `financeiro`, `rh`, `protocolo`) — usado na URL de login
- Uma **URL base** — para onde o usuário é redirecionado após autenticar
- Uma **identidade visual própria** (logo, imagem de fundo) — exibida na tela de login quando o usuário chega a partir daquele sistema
- Seus próprios **perfis** e **permissões**

### Usuário
Um usuário existe no Login Universal com email e senha. Ele pode ter vínculos com múltiplos órgãos e lotações, e em cada vínculo pode ter papéis (perfis) e permissões diferentes.

### Órgão
Representa uma organização, empresa ou secretaria. Exemplo: "Secretaria de Educação", "Departamento Financeiro", "Empresa X". Os usuários são vinculados a órgãos.

### Lotação
Um departamento ou setor dentro de um órgão. Exemplo: "Diretoria de Planejamento" dentro da "Secretaria de Educação". A lotação é o nível mais granular de vínculo de um usuário com uma organização.

### Perfil (Role)
Um papel que um usuário pode ter dentro de um sistema específico. Exemplo: "Administrador", "Editor", "Consultor". Cada perfil agrupa um conjunto de permissões. Um perfil pertence a um sistema específico.

### Permissão
Uma capacidade específica dentro de um sistema. Pode ser do tipo:
- **CRUD**: gera 4 operações separadas — Criar, Editar, Excluir, Visualizar
- **Simples**: acesso geral a uma funcionalidade

Exemplo: a permissão "Relatórios" (simples) ou "Contratos" (CRUD, com 4 sub-permissões).

---

## O fluxo completo de autenticação

```
1. Usuário acessa sistema-externo.com
   └─ Sistema externo redireciona para: login.app/login/sistema-externo

2. Login Universal exibe a tela de login
   └─ Com a identidade visual do sistema que fez o redirecionamento
   └─ Logo e imagem de fundo customizados por sistema

3. Usuário digita email + senha e submete

4. Login Universal autentica o usuário

5. Gera um token criptografado de uso único (válido por 1 minuto)
   └─ Contém: token, session_id, slug do sistema, timestamp de validade

6. Redireciona de volta: sistema-externo.com?callback={token_criptografado}

7. Sistema externo troca o token pela API:
   GET login.app/api/v1/login/sistema-externo?token={token}

8. Login Universal valida o token, revoga (uso único) e retorna:
   └─ Dados do usuário (nome, email, etc.)
   └─ Lotações e órgãos do usuário
   └─ Perfis e permissões para aquele sistema
   └─ Session ID ativo (para verificar sessão posteriormente)

9. Sistema externo cria sua sessão local com esses dados
```

---

## Segurança do fluxo

- O token de callback é **criptografado** com a chave da aplicação (AES via Laravel Crypt)
- O token expira em **1 minuto** — janela mínima para evitar replay attacks
- O token é de **uso único** — após validado, é imediatamente zerado no banco
- O slug do sistema destino está **dentro do payload criptografado** — não é possível usar um token gerado para o sistema A no sistema B

---

## O painel administrativo

O Login Universal tem um painel web próprio, acessado por administradores, com:

| Seção | O que faz |
|-------|-----------|
| Dashboard | Visão geral do sistema |
| Sistemas | Cadastro e configuração de sistemas clientes |
| Usuários | CRUD de usuários, vínculos, perfis e permissões |
| Órgãos | Cadastro de organizações |
| Lotações | Departamentos dentro dos órgãos |
| Sessões | Visualizar e revogar sessões ativas |

O acesso a cada seção é controlado pelas próprias permissões do sistema — um administrador de usuários pode não ter acesso à gestão de sistemas, por exemplo.

---

## Customização visual por sistema

Quando um usuário chega à tela de login vindo de um sistema externo, a tela se adapta visualmente:

- **Logo** do sistema no topo do formulário
- **Imagem/ilustração** no painel esquerdo da tela
- **Nome** do sistema como título
- **Descrição** como subtítulo

Se o usuário acessar o login diretamente (sem vir de um sistema), vê a identidade padrão do Login Universal.

---

## Identidade visual do produto

O sistema é uma ferramenta administrativa e técnica. A linguagem visual deve transmitir:

- **Confiança e segurança** — é onde ficam as credenciais de acesso de todos
- **Clareza e hierarquia** — muita informação estruturada (tabelas, formulários com abas)
- **Neutralidade** — a identidade do sistema fica em segundo plano quando exibindo a tela de login de outro sistema

### Design system

O sistema segue o design system definido em `docs/SYSTEM-DESIGN.md` — uma linguagem visual escura inspirada no Linear, com canvas quase-preto e acento único em lavender-blue.

| Papel | Token | Valor | Uso |
|-------|-------|-------|-----|
| Fundo da página | `{colors.canvas}` | #010102 | Background de todas as telas |
| Superfície de cards | `{colors.surface-1}` | #0f1011 | Cards, painéis, sidebar |
| Superfície elevada | `{colors.surface-2}` | #141516 | Cards em hover, itens selecionados |
| Cor primária / acento | `{colors.primary}` | #5e6ad2 | Botões CTA, links ativos, focus ring |
| Primária em hover | `{colors.primary-hover}` | #828fff | Hover do botão primário |
| Texto principal | `{colors.ink}` | #f7f8f8 | Títulos e corpo |
| Texto secundário | `{colors.ink-muted}` | #d0d6e0 | Labels, metadados |
| Texto sutil | `{colors.ink-subtle}` | #8a8f98 | Placeholders, itens desabilitados |
| Borda | `{colors.hairline}` | #23252a | Bordas de cards e inputs |
| Sucesso | `{colors.semantic-success}` | #27a644 | Badges "Ativo", confirmações |
| Erro | — | #e5534b | Mensagens de erro, campos inválidos (não definido no SYSTEM-DESIGN — adicionar se necessário) |

### Filosofia visual (extraída do SYSTEM-DESIGN.md)
- O canvas escuro (`{colors.canvas}`) **é** o espaço em branco — não há gaps brancos entre seções
- Hierarquia criada pela escada de surfaces (canvas → surface-1 → surface-2), sem sombras
- O acento `{colors.primary}` lavender é usado com **parcimônia**: botão CTA, focus ring, link ativo — nunca como fundo decorativo
- Bordas de 1px (`{colors.hairline}`) em cards e inputs — nunca sombra em dark mode

### Tom de voz
- Direto e funcional
- Mensagens de erro específicas ("E-mail ou senha incorretos" em vez de "Erro de autenticação")
- Labels em português, sem abreviações desnecessárias

---

## Estrutura de navegação (painel admin)

```
Login Universal
├── Dashboard
├── Sistemas
│   ├── Listar sistemas
│   └── Criar / Editar sistema
│       ├── Aba: Dados do sistema
│       ├── Aba: Tela de login (logo, ilustração)
│       ├── Aba: Perfis e permissões
│       └── Aba: Relacionamento com órgãos
├── Usuários
│   ├── Listar usuários
│   └── Criar / Editar usuário
│       ├── Aba: Dados básicos
│       ├── Aba: Órgãos e Lotações
│       └── Aba: Perfis e Permissões
├── Órgãos
│   ├── Listar órgãos
│   └── Criar / Editar órgão
├── Lotações
│   ├── Listar lotações
│   └── Criar / Editar lotação
└── Sessões
    └── Listar e revogar sessões ativas
```

---

## Referência de implementação

O sistema foi inspirado no ACL da SEPLAG/AC, localizado em `/home/guilherme-santos/projetos/login-system/acl/`. A documentação técnica completa do novo sistema está em `docs/` (arquivos 00 a 07) e `AGENTS.md`.
