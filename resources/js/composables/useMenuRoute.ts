const rotas: Record<string, string> = {
    dashboard: '/dashboard',
    'sistema.index': '/sistema',
    'sistema.create': '/sistema/create',
    'usuario.index': '/usuario',
    'sessoes.index': '/sessoes',
    'orgao.index': '/orgao',
    'lotacao.index': '/lotacao',
}

export function menuRouteUrl(nomeRota?: string): string {
    if (!nomeRota) {
        return '#'
    }

    return rotas[nomeRota] ?? '#'
}
