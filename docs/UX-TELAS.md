# UX — Especificação Completa de Telas

> Documento de referência para design e desenvolvimento. Para cada tela: objetivo, layout, componentes obrigatórios, estados e comportamentos.

---

## Índice de telas

| # | Tela | Rota | Acesso |
|---|------|------|--------|
| 01 | Login | `/login/{slug?}` | Público |
| 02 | Dashboard | `/dashboard` | Autenticado |
| 03 | Lista de Sistemas | `/sistema` | Permissão 1.4 |
| 04 | Criar / Editar Sistema | `/sistema/create` e `/sistema/{id}/edit` | Permissão 1.1 / 1.2 |
| 05 | Lista de Usuários | `/usuario` | Permissão 2.4 |
| 06 | Criar / Editar Usuário | `/usuario/create` e `/usuario/{id}/edit` | Permissão 2.1 / 2.2 |
| 07 | Lista de Órgãos | `/orgao` | Permissão 3.4 |
| 08 | Criar / Editar Órgão | `/orgao/create` e `/orgao/{id}/edit` | Permissão 3.1 / 3.2 |
| 09 | Lista de Lotações | `/lotacao` | Permissão 4.4 |
| 10 | Criar / Editar Lotação | `/lotacao/create` e `/lotacao/{id}/edit` | Permissão 4.1 / 4.2 |
| 11 | Sessões Ativas | `/sessoes` | Permissão 7 |
| 12 | Erro (403 / 404 / 500) | — | Todos |

---

## Design system de referência

Todas as telas seguem o `docs/SYSTEM-DESIGN.md`. Tokens essenciais para implementação:

| Token | Valor | Uso nas telas |
|-------|-------|---------------|
| `{colors.canvas}` | #010102 | Background da página inteira |
| `{colors.surface-1}` | #0f1011 | Sidebar, cards, painéis |
| `{colors.surface-2}` | #141516 | Hover de cards, item ativo na sidebar, abas selecionadas |
| `{colors.surface-3}` | #18191a | Dropdowns, menus de contexto |
| `{colors.primary}` | #5e6ad2 | Botão primário, link ativo, focus ring, indicador de item ativo |
| `{colors.primary-hover}` | #828fff | Hover do botão primário |
| `{colors.ink}` | #f7f8f8 | Títulos, labels, texto de botões |
| `{colors.ink-muted}` | #d0d6e0 | Subtítulos, metadados, texto secundário |
| `{colors.ink-subtle}` | #8a8f98 | Placeholders, texto desabilitado, breadcrumb inativo |
| `{colors.hairline}` | #23252a | Bordas de cards, inputs, divisores |
| `{colors.hairline-strong}` | #34343a | Borda de input em focus |
| `{colors.semantic-success}` | #27a644 | Badge "Ativo", confirmações, toasts de sucesso |
| Erro (não no SYSTEM-DESIGN) | #e5534b | Badge "Inativo", campos inválidos, toasts de erro |

---

## Layout base — Painel administrativo (telas 02 a 11)

**Estrutura:**
```
┌─────────────────────────────────────────────┐
│  SIDEBAR (fixo, 240px)  │  CONTEÚDO         │
│  {colors.surface-1}     │  {colors.canvas}  │
│                         │                   │
│  [Logo]                 │  Breadcrumb       │
│  [Nome do usuário]      │  ─────────────    │
│                         │  Card principal   │
│  ● Dashboard            │  {colors.surface-1}│
│  ● Sistemas             │    Header         │
│  ● Usuários             │    Corpo          │
│  ● Órgãos               │    Footer         │
│  ● Lotações             │                   │
│  ● Sessões              │                   │
│                         │                   │
│  [Sair]                 │                   │
└─────────────────────────────────────────────┘
```

**Sidebar:** `{colors.surface-1}` com borda direita `{colors.hairline}`
- Logo do Login Universal no topo
- Nome e avatar do usuário logado (`{colors.ink}`)
- Links de navegação com ícone + label (`{colors.ink-muted}` no estado padrão)
- Item ativo: texto `{colors.ink}` + fundo `{colors.surface-2}` + barra esquerda `{colors.primary}` (2px)
- Itens sem permissão: ocultos (não desabilitados)
- Botão de logout no rodapé
- Em mobile: colapsável via hamburger

**Card principal:** `{colors.surface-1}`, borda 1px `{colors.hairline}`, border-radius `{rounded.lg}` (12px)
- Header do card: título `{colors.ink}` + subtítulo `{colors.ink-subtle}` à esquerda, botão "Novo" à direita
- Body do card: conteúdo da tela (tabela, formulário)
- Footer do card (formulários): botões Cancelar (`button-secondary`) + Salvar (`button-primary`) à direita
- **Sem sombra** — o contraste entre `{colors.surface-1}` e `{colors.canvas}` cria a elevação

**Breadcrumb:** `{colors.ink-subtle}` para itens anteriores, `{colors.ink-muted}` para o atual
- Formato: `Início / Seção / Página atual`
- Último item não é clicável

**Notificações flash:** toast no canto superior direito
- Sucesso: fundo `{colors.surface-2}`, borda esquerda `{colors.semantic-success}`, texto `{colors.ink}`
- Erro: fundo `{colors.surface-2}`, borda esquerda #e5534b, texto `{colors.ink}`
- Auto-dismiss em 4 segundos
- Ícone + mensagem curta

---

## Tela 01 — Login

**Rota:** `/login/{slug?}`
**Acesso:** Público — redireciona para dashboard se já autenticado

### Objetivo
Autenticar o usuário. Quando acessada com um `{slug}`, exibe a identidade visual do sistema que originou o redirecionamento. Após login bem-sucedido, redireciona de volta ao sistema de origem (ou para o dashboard se não há origem).

### Layout

```
┌──────────────────────┬──────────────────────┐
│                      │                      │
│   PAINEL ESQUERDO    │   PAINEL DIREITO     │
│   (ilustração)       │   (formulário)       │
│                      │                      │
│   [Imagem/           │   [Logo do sistema]  │
│    Ilustração        │                      │
│    do sistema]       │   Nome do sistema    │
│                      │   Subtítulo          │
│   ou                 │                      │
│                      │   ┌──────────────┐  │
│   Nome do sistema    │   │  E-mail      │  │
│   em texto           │   └──────────────┘  │
│   centralizado       │   ┌──────────────┐  │
│   sobre fundo        │   │  Senha       │  │
│   colorido           │   └──────────────┘  │
│                      │                      │
│                      │   □ Lembrar de mim  │
│                      │                      │
│                      │   [  Entrar  ]       │
│                      │                      │
└──────────────────────┴──────────────────────┘
```

Em mobile: apenas o painel direito (formulário), sem ilustração.

### Componentes obrigatórios

**Painel esquerdo (lg+):** fundo `{colors.surface-1}`
- Se `sistema.caminho_ilustracao` preenchido: imagem ocupando todo o painel
- Se não: fundo `{colors.primary}` com nome (`{colors.on-primary}`) e descrição do sistema
- Se nenhum sistema: fundo `{colors.primary}` com nome "Login Universal"

**Painel direito:** fundo `{colors.canvas}`
- Logo do sistema (`sistema.caminho_logo`) — se não houver, iniciais ou ícone padrão
- Nome do sistema como `<h1>` em `{colors.ink}`
- Subtítulo `{colors.ink-subtle}`: "Entre com suas credenciais para continuar"
- Campo **E-mail**: `text-input` do SYSTEM-DESIGN — fundo `{colors.surface-1}`, borda `{colors.hairline}`, texto `{colors.ink}`, radius `{rounded.md}`
- Campo **Senha**: mesmo estilo do e-mail
- Focus ring: 2px `{colors.primary-focus}` — conforme `text-input-focused`
- Checkbox **Lembrar de mim**: `{colors.ink-subtle}`
- Botão **Entrar**: `button-primary` — fundo `{colors.primary}`, texto `{colors.on-primary}`, radius `{rounded.md}`, full width

### Estados

| Estado | Comportamento visual |
|--------|---------------------|
| Carregando | Botão com fundo `{colors.primary}` + opacity 50% + spinner branco |
| Erro de credenciais | Borda dos campos: #e5534b — mensagem abaixo: "E-mail ou senha incorretos" em #e5534b |
| Erro de campo vazio | Borda do campo #e5534b + mensagem `{typography.caption}` abaixo |
| Sucesso | Redireciona automaticamente — sem mensagem |
| Sessão já ativa | Redireciona direto para o destino sem mostrar formulário |

### Comportamentos especiais
- Se o usuário já está autenticado e acessa `/login/{slug}`, não exibe o formulário — processa o login direto e redireciona
- O formulário não deve ter botão "Esqueci minha senha" na v1 (implementar futuramente)
- Foco automático no campo de e-mail ao carregar

---

## Tela 02 — Dashboard

**Rota:** `/dashboard`
**Acesso:** Usuário autenticado

### Objetivo
Tela inicial após login. Apresenta um resumo do sistema e acesso rápido às seções disponíveis para o usuário logado. Itens sem permissão não aparecem.

### Layout

```
┌──────────────────────────────────────────┐
│  Bem-vindo, [Nome do usuário]            │
│  [Data e hora atual]                     │
├──────────────┬───────────┬───────────────┤
│  Sistemas    │ Usuários  │   Órgãos      │
│  [ícone]     │ [ícone]   │   [ícone]     │
│  [contagem]  │[contagem] │  [contagem]   │
├──────────────┴───────────┴───────────────┤
│  Lotações   │  Sessões                   │
│  [ícone]    │  [ícone]                   │
│  [contagem] │  [contagem ativas]         │
└─────────────┴──────────────────────────-─┘
```

### Componentes obrigatórios
- Saudação com nome do usuário logado
- Cards de acesso rápido para cada seção — exibidos apenas se o usuário tem permissão de visualização
- Cada card: ícone representativo + nome da seção + contagem de registros + link para a lista
- Estado vazio: se o usuário não tem nenhuma permissão de visualização, exibir mensagem "Você não tem acesso a nenhum módulo. Contate o administrador."

---

## Tela 03 — Lista de Sistemas

**Rota:** `/sistema`
**Permissão:** `1.4` (visualizar sistemas)

### Objetivo
Listar todos os sistemas cadastrados, com busca e acesso às ações de criação e edição.

### Layout
```
Card
  Header: "Sistemas" + subtítulo + [Botão "Novo Sistema" — visível se can('1.1')]
  Body:
    - Campo de busca (filtro por nome ou slug)
    - Tabela de resultados
    - Paginação
```

### Tabela — colunas

| Coluna | Conteúdo |
|--------|----------|
| Nome | Nome do sistema + slug como subtexto |
| URL | Link clicável para a URL do sistema |
| Ambiente | Badge `{rounded.pill}`: Produção (`{colors.semantic-success}`) / Homologação (#d97706) / Desenvolvimento (`{colors.ink-subtle}`) |
| Status | Badge `{rounded.pill}`: Ativo (`{colors.semantic-success}`) / Inativo (`{colors.ink-subtle}`) |
| Ações | Ícones: Editar (can 1.2) / Excluir (can 1.3) |

### Estados
- **Sem resultados:** ilustração + "Nenhum sistema cadastrado" + botão "Cadastrar primeiro sistema"
- **Sem resultados na busca:** "Nenhum sistema encontrado para '{busca}'"
- **Excluir:** modal de confirmação antes de deletar ("Tem certeza? Esta ação não pode ser desfeita.")

---

## Tela 04 — Criar / Editar Sistema

**Rota:** `/sistema/create` e `/sistema/{id}/edit`
**Permissão:** `1.1` (criar) / `1.2` (editar)

### Objetivo
Formulário completo de cadastro/edição de um sistema cliente. Organizado em abas para não sobrecarregar o usuário.

### Abas

#### Aba 1 — Dados do Sistema
- **Nome** (obrigatório) — texto livre
- **Slug** (obrigatório) — identificador único usado na URL. Auto-gerado a partir do nome, editável. Validado: apenas letras minúsculas, números e hífens. Exemplo: `sistema-financeiro`
- **URL** (obrigatório) — URL base do sistema externo. Exemplo: `https://financeiro.empresa.com`
- **URL de Logout** (opcional) — para onde redirecionar quando o usuário faz logout a partir deste sistema
- **Ambiente** — select: Produção / Homologação / Desenvolvimento
- **Descrição** (opcional) — textarea
- **Status** — toggle Ativo/Inativo

#### Aba 2 — Tela de Login
Configura a identidade visual exibida quando um usuário chega a partir deste sistema.

- **Logo** — upload de imagem (PNG/JPG/SVG, max 2MB). Preview ao lado do input. Formatos aceitos visíveis. Botão para remover logo existente
- **Ilustração** — upload de imagem para o painel esquerdo da tela de login (PNG/JPG, recomendado 800×600px). Preview ao lado. Botão para remover
- Preview em tempo real: miniatura de como ficará a tela de login com os assets enviados

#### Aba 3 — Perfis e Permissões
Disponível apenas em modo de edição (sistema já existe).

- Lista os perfis (roles) do sistema
- Cada perfil: nome + lista de permissões atribuídas
- Botão "Adicionar Perfil" — inline ou modal
- Para cada perfil: expandir para ver/editar permissões
- Permissões disponíveis listadas com checkboxes
- Para permissões CRUD: checkboxes individuais por operação (Criar / Editar / Excluir / Visualizar)
- Botão "Remover perfil" por perfil

#### Aba 4 — Relacionamento com Órgãos
- Lista de órgãos cadastrados com checkbox
- Selecionar quais órgãos têm acesso a este sistema
- Campo opcional: ID do órgão no sistema externo (para sincronização)

### Rodapé do card (fixo em todas as abas)
- Botão "Cancelar": `button-secondary` — fundo `{colors.surface-1}`, texto `{colors.ink}`, borda `{colors.hairline}`
- Botão "Salvar": `button-primary` — fundo `{colors.primary}`, texto `{colors.on-primary}`

### Comportamentos
- Se houver erros de validação: aba com erro recebe indicador visual (ícone de aviso no título da aba)
- Slug: auto-preenchido ao digitar o nome (convertendo para kebab-case), mas editável manualmente. Após salvar uma vez, exibir aviso "Alterar o slug pode quebrar sistemas que já usam este login"
- Upload de imagens: preview imediato no lado direito do campo

---

## Tela 05 — Lista de Usuários

**Rota:** `/usuario`
**Permissão:** `2.4` (visualizar usuários)

### Objetivo
Listar usuários com busca. Por ser uma lista potencialmente grande, a busca é obrigatória para exibir resultados (exceto para administradores).

### Layout
```
Card
  Header: "Usuários" + subtítulo + [Botão "Novo Usuário" — visível se can('2.1')]
  Body:
    - Campo de busca (por nome ou e-mail)
    - Tabela de resultados (apenas após busca, para não-admins)
    - Paginação
```

### Tabela — colunas

| Coluna | Conteúdo |
|--------|----------|
| Usuário | Avatar (iniciais) + Nome + e-mail como subtexto |
| Lotação principal | Órgão + Lotação do vínculo marcado como exercício |
| Status | Badge: Ativo / Inativo |
| Último acesso | Data relativa ("há 2 dias") com tooltip da data exata |
| Ações | Editar (can 2.2) / Excluir (can 2.3) |

### Estado especial
- Se usuário não é admin E busca tem menos de 3 caracteres: exibe "Digite nome ou e-mail para buscar usuários" com ícone de lupa
- Isso evita carregar toda a base de usuários sem necessidade

---

## Tela 06 — Criar / Editar Usuário

**Rota:** `/usuario/create` e `/usuario/{id}/edit`
**Permissão:** `2.1` (criar) / `2.2` (editar)

### Objetivo
Formulário completo do usuário em 3 abas: dados básicos, vínculos organizacionais e controle de acesso.

### Abas

#### Aba 1 — Dados Básicos
- **Nome completo** (obrigatório)
- **E-mail** (obrigatório, único) — usado para login
- **Senha** (obrigatório na criação, opcional na edição) — campo com toggle mostrar/ocultar. Na edição: deixar vazio para não alterar
- **Confirmar senha** — aparece quando senha é preenchida
- **Status** — toggle Ativo/Inativo

#### Aba 2 — Órgãos e Lotações
Define onde o usuário está posicionado na estrutura organizacional.

- Lista os vínculos existentes (cards ou linhas)
- Cada vínculo mostra: Órgão + Lotação + flag "Lotação principal" + Status + botão Remover
- Botão **"Adicionar Vínculo"** — abre um painel inline ou modal com:
  - Select de Órgão (busca por nome)
  - Select de Lotação (filtrado pelo órgão selecionado, busca por nome)
  - Toggle "Definir como lotação principal" (só um pode ser principal)
  - Toggle Status (Ativo/Inativo)
- Regra visual: vínculo principal tem badge `{colors.primary}` + texto `{colors.on-primary}` ("Principal")
- Se não há vínculos: mensagem "Nenhum vínculo organizacional. Adicione ao menos um para definir permissões."

#### Aba 3 — Perfis e Permissões
Define o que o usuário pode fazer em cada sistema, dentro do contexto de cada vínculo.

- Layout: lista de vínculos (do usuário) como accordion
- Cada vínculo expandido mostra:
  - **Perfis atribuídos** — multi-select de roles disponíveis para o(s) sistema(s)
  - **Permissões diretas** — lista de permissões com checkboxes individuais
  - Toggle **Administrador** neste vínculo — dá acesso total naquele órgão/sistema
- Texto auxiliar: "Permissões diretas têm prioridade sobre permissões do perfil"

### Comportamentos
- Abas com erro: ícone de aviso no título da aba
- Na edição: se o usuário tem sessão ativa e status é alterado para Inativo, exibir aviso "Este usuário será desconectado nas próximas requisições"
- Senha: campo do tipo password, sem autopreenchimento forçado

---

## Tela 07 — Lista de Órgãos

**Rota:** `/orgao`
**Permissão:** `3.4`

### Objetivo
Listar organizações cadastradas com acesso às ações de CRUD.

### Tabela — colunas

| Coluna | Conteúdo |
|--------|----------|
| Órgão | Nome completo + sigla como subtexto |
| CNPJ | Formatado (XX.XXX.XXX/XXXX-XX) |
| Status | Badge `{rounded.pill}`: Ativo (`{colors.semantic-success}`) / Inativo (`{colors.ink-subtle}`) |
| Lotações | Contagem de lotações vinculadas (link para a lista filtrada) |
| Ações | Editar / Excluir |

### Filtros disponíveis
- Busca por nome ou sigla
- Filtro por status (Ativo / Inativo / Todos)

---

## Tela 08 — Criar / Editar Órgão

**Rota:** `/orgao/create` e `/orgao/{id}/edit`
**Permissão:** `3.1` / `3.2`

### Objetivo
Formulário simples de cadastro de organização. Não usa abas — é suficientemente simples.

### Campos
- **Nome completo** (obrigatório)
- **Sigla** (opcional, máx 20 caracteres) — ex: "SEDUC"
- **CNPJ** (opcional) — com máscara de formatação automática, validação
- **Status** — toggle Ativo/Inativo (padrão: Ativo)

### Validações
- CNPJ: máscara XX.XXX.XXX/XXXX-XX, validação de dígitos verificadores
- Sigla: apenas letras maiúsculas e números
- CNPJ único no banco — se já existir, mensagem específica: "Já existe um órgão com este CNPJ"

---

## Tela 09 — Lista de Lotações

**Rota:** `/lotacao`
**Permissão:** `4.4`

### Objetivo
Listar departamentos/setores. Pode ser acessada em contexto global ou filtrada por órgão.

### Tabela — colunas

| Coluna | Conteúdo |
|--------|----------|
| Lotação | Nome + sigla como subtexto |
| Órgão | Nome do órgão vinculado |
| Nível hierárquico | Número (1 = raiz, 2 = subdivisão, etc.) |
| Subordinada a | Nome da lotação-pai (se houver) |
| Ações | Editar / Excluir |

### Filtros
- Busca por nome
- Filtro por órgão (select)

---

## Tela 10 — Criar / Editar Lotação

**Rota:** `/lotacao/create` e `/lotacao/{id}/edit`
**Permissão:** `4.1` / `4.2`

### Objetivo
Formulário de criação de departamento dentro de um órgão.

### Campos
- **Órgão** (obrigatório) — select com busca, lista todos os órgãos ativos
- **Nome da lotação** (obrigatório) — ex: "Diretoria de Planejamento"
- **Sigla** (opcional) — ex: "DIPLAN"
- **Nível hierárquico** — número inteiro, padrão 1
- **Subordinada a** (opcional) — select com busca, lista outras lotações do mesmo órgão. Permite criar hierarquia

---

## Tela 11 — Sessões Ativas

**Rota:** `/sessoes`
**Permissão:** `7`

### Objetivo
Visibilidade de todas as sessões de usuários ativos no sistema. Permite que administradores revoguem sessões (desconectem usuários forçadamente).

### Layout
```
Card
  Header: "Sessões ativas" + subtítulo + botão "Revogar todas" (com confirmação)
  Body:
    - Filtros
    - Tabela de sessões
    - Paginação
```

### Tabela — colunas

| Coluna | Conteúdo |
|--------|----------|
| Usuário | Nome + e-mail |
| IP | Endereço IP da sessão |
| Dispositivo | User-agent simplificado (ex: "Chrome / Windows", "Safari / iOS") |
| Último acesso | Tempo relativo + tooltip com data exata |
| Sessão atual | Badge "Você" se for a sessão do usuário logado |
| Ações | Botão "Revogar" — desconecta o usuário (exceto a própria sessão) |

### Filtros
- Busca por nome ou e-mail de usuário
- Filtro por período de último acesso

### Comportamentos
- Revogar: modal de confirmação "Tem certeza? O usuário será desconectado imediatamente."
- A própria sessão do admin logado não pode ser revogada (botão desabilitado com tooltip explicativo)
- Após revogar: linha some da tabela com animação suave

---

## Tela 12 — Erro

**Rotas:** geradas automaticamente pelo Laravel para 403, 404, 500

### Objetivo
Comunicar erros de forma clara sem deixar o usuário perdido.

### Layout
Centralizado, sem sidebar (usa o layout de autenticação ou um layout mínimo):

```
┌───────────────────────────────┐
│  [Código do erro — grande]    │
│  [Título do erro]             │
│  [Descrição amigável]         │
│                               │
│  [Botão: Voltar ao início]    │
└───────────────────────────────┘
```

### Mensagens por código

| Código | Título | Descrição |
|--------|--------|-----------|
| 403 | Acesso negado | "Você não tem permissão para acessar esta página. Contate o administrador se acredita que isto é um erro." |
| 404 | Página não encontrada | "A página que você procura não existe ou foi movida." |
| 500 | Erro interno | "Algo deu errado no servidor. Nossa equipe já foi notificada." |

---

## Componentes globais reutilizáveis

Estes componentes aparecem em múltiplas telas e devem ser criados como componentes Vue independentes. Todos seguem os tokens do `docs/SYSTEM-DESIGN.md`.

| Componente | Uso | Tokens-chave |
|------------|-----|-------------|
| `<DataTable>` | Tabela com ordenação, paginação integrada | `surface-1`, `hairline`, `ink`, `ink-muted` |
| `<SearchInput>` | Campo de busca com debounce | `text-input` do SYSTEM-DESIGN |
| `<StatusBadge>` | Badge de status (Ativo/Inativo/Ambiente) | `semantic-success`, `ink-subtle`, `rounded.pill` |
| `<ConfirmModal>` | Modal de confirmação genérico | `surface-2`, `hairline`, overlay `semantic-overlay` |
| `<EmptyState>` | Ilustração + texto para listas vazias | `ink-subtle`, `ink-tertiary` |
| `<TabsNav>` | Navegação por abas com indicador de erro | `surface-2` ativo, `canvas` inativo, `primary` underline |
| `<ImageUpload>` | Upload com preview imediato | `surface-1`, `hairline`, borda dashed |
| `<FormFooter>` | Rodapé fixo com Cancelar + Salvar | `button-secondary` + `button-primary` |
| `<Breadcrumb>` | Migalha de pão da navegação | `ink-subtle` → `ink-muted` |
| `<FlashMessage>` | Toast de sucesso/erro | `surface-2` + borda lateral colorida |
| `<ToggleSwitch>` | Toggle booleano | Track ativo: `primary`, inativo: `hairline-strong` |

---

## Considerações gerais de UX

### Responsividade
- Mobile first: todas as telas funcionam em 360px+
- Sidebar colapsa para drawer em mobile (toggle no header)
- Tabelas: em mobile, linhas viram cards empilhados

### Feedback de ações
- Toda ação destrutiva (excluir, revogar) exige confirmação em modal
- Após salvar formulário: toast de sucesso + redirecionamento para lista
- Erros de validação: inline abaixo do campo + indicador na aba (se houver abas)
- Loading states: botão de submit desabilitado + spinner enquanto processa

### Acessibilidade
- Labels em todos os inputs
- Focus ring visível
- Mensagens de erro associadas ao campo via `aria-describedby`
- Cores não são o único indicador de estado (usar ícone ou texto também)

### Vazio > Erro
- Preferir estados vazios amigáveis a mensagens de erro técnicas
- Estado vazio: ilustração simples + texto explicativo + ação sugerida
