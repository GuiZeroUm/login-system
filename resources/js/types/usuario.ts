export type AcessoForm = {
    sistema_id: number | null
    administrador_sistema: boolean
    perfis_ids: number[]
    permissoes: { permission_id: number; tipo: number }[]
}

export type SistemaOpcao = {
    id: number
    nome: string
    slug: string
    ambiente: string
}

export type CatalogoSistema = {
    roles: { id: number; name: string }[]
    permissions: { id: number; name: string; tipo_crud: 'S' | 'N' }[]
}
