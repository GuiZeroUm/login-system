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
