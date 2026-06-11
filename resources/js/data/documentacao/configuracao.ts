import type { DocGuia } from './tipos'

export const guiaConfiguracao: DocGuia = {
    id: 'configuracao',
    titulo: 'Configuração no Login Universal',
    descricao: 'Cadastre o sistema, defina permissões e vincule usuários antes de integrar qualquer aplicação externa.',
    tempoEstimado: '~15 min',
    rota: '/documentacao/configuracao',
    passos: [
        {
            id: 'cadastrar-sistema',
            titulo: 'Cadastrar o sistema',
            resumo: 'Cada aplicação externa precisa de um registro com slug único e URL de callback. O slug identifica o sistema em todas as URLs de integração.',
            itens: [
                'Acesse Sistemas → Novo sistema.',
                'Nome e slug únicos (ex.: financeiro, sistema-teste).',
                'O slug deve ser idêntico ao ACL_SLUG no .env do app cliente.',
                'URL de callback: rota que recebe ?callback= (ex.: http://app.test/login), não apenas o domínio raiz.',
                'URL de logout (opcional): destino após logout federado no hub.',
                'Mantenha o sistema Ativo para aceitar logins.',
            ],
            codigo: {
                rotulo: 'Preview de URLs geradas',
                conteudo: `Tela de login:  {hub}/login/{slug}
Callback:        {url_cadastrada}?callback={token}
API validação:   {hub}/api/v1/login/{slug}?token={callback}`,
            },
            linkInterno: { href: '/sistema/create', rotulo: 'Abrir cadastro de sistema' },
        },
        {
            id: 'personalizar-login',
            titulo: 'Personalizar a tela de login',
            resumo: 'Na aba Tela de login, defina identidade visual e textos exibidos ao usuário antes de autenticar.',
            itens: [
                'Envie logo e ilustração do painel esquerdo.',
                'Ajuste nome exibido, subtítulo do formulário e bloco inferior.',
                'Escolha tema escuro ou claro.',
                'O preview reflete a tela real em /login/{slug}.',
            ],
            linkInterno: { href: '/sistema', rotulo: 'Ir para lista de sistemas' },
        },
        {
            id: 'permissoes-perfis',
            titulo: 'Criar permissões e perfis',
            resumo: 'Cada sistema possui permissões próprias. Perfis (roles) agrupam permissões para atribuir aos usuários.',
            itens: [
                'Na aba Perfis do sistema, crie perfis (ex.: Gestor, Operador).',
                'Marque permissões CRUD (criar/editar/excluir/visualizar) ou simples conforme a necessidade.',
                'Permissões com tipo CRUD geram subtipos 1–4; simples usam o id da permissão.',
                'As permissões retornadas na API são filtradas pelo slug do sistema no cliente.',
            ],
        },
        {
            id: 'vincular-usuario',
            titulo: 'Vincular usuário ao sistema',
            resumo: 'O login só devolve permissões se o usuário tiver vínculo ativo em user_sistemas para este sistema.',
            itens: [
                'O usuário precisa existir no Login Universal (e-mail e senha).',
                'Em Usuários → editar → aba Sistemas: vincule o sistema desejado.',
                'Atribua perfis e/ou permissões diretas ao vínculo.',
                'Status do vínculo deve estar ativo (S).',
                'Administradores globais têm acesso a todos os sistemas automaticamente.',
            ],
            aviso: 'Sem vínculo em user_sistemas, a API retorna 403 e o usuário não entra no sistema externo — mesmo com credenciais corretas no hub.',
            linkInterno: { href: '/usuario', rotulo: 'Ir para usuários' },
        },
        {
            id: 'testar-fluxo',
            titulo: 'Testar antes de integrar',
            resumo: 'Valide o cadastro com um usuário de homologação antes de conectar produção.',
            itens: [
                'Confirme que /login/{slug} exibe a tela personalizada.',
                'Faça login com usuário vinculado ao sistema.',
                'Verifique se o redirect aponta para a URL de callback cadastrada.',
                'No app cliente, confirme que a troca de token na API retorna 200.',
            ],
            codigo: {
                rotulo: 'Teste manual da API (token inválido espera 401)',
                conteudo: `curl "{hub}/api/v1/login/{slug}?token=teste"
# Resposta esperada: {"message":"Token inválido"} com HTTP 401`,
            },
        },
    ],
}
