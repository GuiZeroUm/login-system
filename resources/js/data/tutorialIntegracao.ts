export type TutorialCodigo = {
    rotulo: string
    conteudo: string
}

export type TutorialPasso = {
    titulo: string
    resumo: string
    itens?: string[]
    codigo?: TutorialCodigo
    aviso?: string
    linkInterno?: { href: string; rotulo: string }
}

export type TutorialSecao = {
    id: string
    titulo: string
    descricao: string
    passos: TutorialPasso[]
}

export const secoesTutorialIntegracao: TutorialSecao[] = [
    {
        id: 'login-universal',
        titulo: 'No Login Universal',
        descricao: 'Cadastro e configuração do sistema cliente neste painel.',
        passos: [
            {
                titulo: 'Cadastrar o sistema',
                resumo: 'Em Sistemas → Novo sistema, preencha os dados básicos. O slug identifica o sistema em todas as URLs de integração.',
                itens: [
                    'Nome e slug únicos (ex.: financeiro, sistema-teste).',
                    'O slug deve ser idêntico ao ACL_SLUG no .env do app cliente e à URL /login/{slug} do hub.',
                    'URL de callback: rota que recebe ?callback= (ex.: http://teste.test/login), não apenas o domínio raiz.',
                    'URL de logout (opcional): para onde enviar após logout federado.',
                    'Use o preview na tela: {hub}/login/{slug} deve bater com o redirect do cliente.',
                    'Mantenha o sistema Ativo para aceitar logins.',
                ],
                linkInterno: { href: '/sistema/create', rotulo: 'Abrir cadastro de sistema' },
            },
            {
                titulo: 'Personalizar a tela de login',
                resumo: 'Na aba Tela de login, defina identidade visual e textos exibidos ao usuário antes de autenticar.',
                itens: [
                    'Envie logo e ilustração do painel esquerdo.',
                    'Ajuste nome exibido, subtítulo do formulário e bloco inferior.',
                    'Escolha tema escuro ou claro e oculte elementos sobre a ilustração, se desejar.',
                    'O preview reflete a tela real em /login/{slug}.',
                ],
                linkInterno: { href: '/sistema', rotulo: 'Ir para lista de sistemas' },
            },
            {
                titulo: 'Definir permissões e perfis',
                resumo: 'Cada sistema possui permissões próprias. Perfis (roles) agrupam essas permissões para atribuir aos usuários.',
                itens: [
                    'Na aba Perfis, crie perfis (ex.: Gestor, Operador).',
                    'Marque permissões CRUD (criar/editar/excluir/visualizar) ou simples conforme a necessidade.',
                    'Permissões com tipo CRUD geram gates 1.1, 1.2, etc.; simples usam o id da permissão.',
                ],
            },
            {
                titulo: 'Vincular órgãos e banco (opcional)',
                resumo: 'Órgãos definem quais lotações podem acessar o sistema. O banco tenant serve para sincronizar permissões de um sistema legado.',
                itens: [
                    'Aba Relacionamento: selecione órgãos autorizados para este sistema.',
                    'Aba Banco: credenciais PostgreSQL do tenant — usadas pelo botão Sincronizar na listagem.',
                    'Sem banco configurado, permissões são gerenciadas manualmente neste painel.',
                ],
            },
            {
                titulo: 'Garantir usuários com acesso',
                resumo: 'O login só devolve permissões se o usuário tiver vínculo ativo em user_sistemas para este sistema.',
                itens: [
                    'Usuário precisa existir no Login Universal (e-mail e senha).',
                    'Em Usuários → editar → aba Sistemas: vincule o sistema e atribua perfis ou permissões diretas.',
                    'Sem vínculo, a API retorna 403 mesmo com credenciais corretas.',
                    'Teste com um usuário de homologação antes de integrar produção.',
                ],
            },
        ],
    },
    {
        id: 'sistema-externo',
        titulo: 'No seu sistema',
        descricao: 'O que implementar na aplicação que consumirá o Login Universal.',
        passos: [
            {
                titulo: 'Variáveis de ambiente',
                resumo: 'Configure a URL do Login Universal e o slug cadastrado no passo anterior.',
                codigo: {
                    rotulo: '.env do sistema externo',
                    conteudo: `ACL_URL=https://login.seudominio.gov.br
ACL_SLUG=meu-sistema

# Back-channel (Docker/proxy): omita se front e API usam o mesmo host
ACL_API_URL=http://nginx-interno
ACL_API_HOST=login.seudominio.gov.br`,
                },
            },
            {
                titulo: 'Redirecionar usuários não autenticados',
                resumo: 'Rotas protegidas devem enviar o usuário para o Login Universal. O slug na URL carrega a identidade visual do sistema.',
                codigo: {
                    rotulo: 'Redirect (ex.: middleware ou controller)',
                    conteudo: `// Usuário sem sessão local
return redirect("{$aclUrl}/login/{$aclSlug}");`,
                },
                itens: [
                    'Salve a URL de destino na sessão (url_intended) para retornar após o login.',
                ],
            },
            {
                titulo: 'Rota de callback',
                resumo: 'Após login, o Login Universal redireciona para {url}?callback={token}. Sua rota deve ler esse parâmetro imediatamente.',
                codigo: {
                    rotulo: 'URL de retorno (gerada pelo Login Universal)',
                    conteudo: `{SISTEMA_URL}?callback={token_criptografado}

// Ex.: https://app.gov.br/login?callback=eyJpdiI6...`,
                },
                itens: [
                    'O campo url do cadastro deve apontar para esta rota (ex.: /login ou /auth/callback).',
                    'Token de uso único e validade de 1 minuto — troque na API sem atraso.',
                ],
            },
            {
                titulo: 'Trocar token pela API',
                resumo: 'Chame o endpoint de validação com o valor recebido em callback. A resposta traz usuário, lotações, perfis e permissões.',
                codigo: {
                    rotulo: 'Requisição (implementação atual)',
                    conteudo: `GET {ACL_API_URL}/api/v1/login/{ACL_SLUG}?token={callback}

// Resposta 200: JSON com id, nome, email, orgaos.lotacoes, perfis, permissoes...`,
                },
                aviso: 'Use GET com query token (não POST com Bearer). O token é revogado após a primeira troca bem-sucedida. ACL_SLUG no cliente deve ser igual ao slug cadastrado aqui.',
            },
            {
                titulo: 'Criar sessão local',
                resumo: 'Persista o usuário no banco local, grave permissões na sessão e autentique no framework do app.',
                itens: [
                    'updateOrCreate do usuário local a partir do e-mail retornado.',
                    'Filtre perfis/permissões pelo slug do sistema (role.sistema.slug === ACL_SLUG).',
                    'Opcional: cookie SSO-USER-ACL para logout federado.',
                    'Redirecione para url_intended ou dashboard.',
                ],
                codigo: {
                    rotulo: 'Exemplo Laravel (Http client)',
                    conteudo: `$request = Http::timeout(15)->acceptJson();
if ($host = config('acl.api_host')) {
    $request = $request->withHeaders(['Host' => $host]);
}
$response = $request->get(
    config('acl.api_url').'/api/v1/login/'.config('acl.slug'),
    ['token' => $request->query('callback')],
);

session(['usuario_acl' => $response->json()]);`,
                },
            },
            {
                titulo: 'Logout e verificação de sessão',
                resumo: 'Encerre a sessão local e redirecione para o logout centralizado. Opcionalmente verifique se a sessão ACL ainda está ativa.',
                codigo: {
                    rotulo: 'Logout federado',
                    conteudo: `session()->invalidate();
return redirect("{ACL_URL}/logout/{ACL_SLUG}");

// Verificação periódica (opcional):
GET {ACL_URL}/api/v1/check/{acl_token}`,
                },
            },
            {
                titulo: 'Fluxo completo (referência)',
                resumo: 'Visão do caminho ponta a ponta entre o sistema externo e este Login Universal.',
                codigo: {
                    rotulo: 'Sequência',
                    conteudo: `1. App externo → GET /login/{slug} (Login Universal)
2. Usuário autentica (e-mail + senha)
3. Login Universal → {url}?callback=token
4. App externo → GET /api/v1/login/{slug}?token=...
5. App externo cria sessão local → dashboard`,
                },
                linkInterno: { href: '/documentacao', rotulo: 'Ver documentação completa' },
            },
        ],
    },
]

export const totalPassosTutorial = secoesTutorialIntegracao.reduce((acc, secao) => acc + secao.passos.length, 0)
