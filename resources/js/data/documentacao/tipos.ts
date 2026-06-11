export type DocCodigo = {
    rotulo: string
    conteudo: string
}

export type DocPasso = {
    id: string
    titulo: string
    resumo: string
    itens?: string[]
    codigo?: DocCodigo
    aviso?: string
    linkInterno?: { href: string; rotulo: string }
}

export type DocGuia = {
    id: string
    titulo: string
    descricao: string
    tempoEstimado: string
    rota: string
    passos: DocPasso[]
}

export type DocCard = {
    id: string
    titulo: string
    resumo: string
    tempoEstimado: string
    rota: string
    icon: string
}
