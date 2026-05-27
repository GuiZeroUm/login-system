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
    login_nome?: string | null
    tema_login?: 'escuro' | 'claro'
    login_subtitulo?: string | null
    login_painel_eyebrow?: string | null
    login_painel_titulo?: string | null
    login_painel_descricao?: string | null
    exibir_logo_topo?: boolean
    exibir_bloco_inferior?: boolean
    exibir_degrade_ilustracao?: boolean
    caminho_logo: string | null
    caminho_logo_url?: string | null
    caminho_ilustracao: string | null
    caminho_ilustracao_url?: string | null
    ativo: boolean
    permissions_synced_at?: string | null
    permissions_count?: number
    roles_count?: number
    banco?: {
        tipo: string | null
        host: string | null
        porta: number | null
        nome_banco: string | null
        usuario: string | null
        senha: string
    } | null
    perfis?: Array<{
        id: number
        name: string
        permissoes: Array<{ permission_id: number; tipo: number }>
    }>
    orgaos_ids?: number[]
}
