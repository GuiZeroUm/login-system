import { guiaConfiguracao } from './configuracao'
import { guiaLaravel } from './laravel'
import { guiaReactNext } from './react-next'
import type { DocCard, DocGuia } from './tipos'

export const cardsDocumentacao: DocCard[] = [
    {
        id: 'configuracao',
        titulo: 'Configuração',
        resumo: 'Cadastre sistema, permissões e vincule usuários no painel.',
        tempoEstimado: '~15 min',
        rota: '/documentacao/configuracao',
        icon: 'shield',
    },
    {
        id: 'laravel',
        titulo: 'Laravel',
        resumo: 'Integração completa com redirect, callback e sessão local.',
        tempoEstimado: '~30 min',
        rota: '/documentacao/laravel',
        icon: 'folder',
    },
    {
        id: 'react-next',
        titulo: 'React / Next.js',
        resumo: 'Middleware, Route Handler e sessão server-side.',
        tempoEstimado: '~45 min',
        rota: '/documentacao/react-next',
        icon: 'monitor',
    },
]

export const guiasDocumentacao: DocGuia[] = [
    guiaConfiguracao,
    guiaLaravel,
    guiaReactNext,
]

export { guiaConfiguracao, guiaLaravel, guiaReactNext }
