import type { DocGuia } from './tipos'

export const guiaReactNext: DocGuia = {
    id: 'react-next',
    titulo: 'Integração React / Next.js',
    descricao: 'Integre SSO em apps React ou Next.js com Route Handler server-side. O token nunca deve ser trocado no browser.',
    tempoEstimado: '~45 min',
    rota: '/documentacao/react-next',
    passos: [
        {
            id: 'pre-requisito',
            titulo: 'Pré-requisito: backend obrigatório',
            resumo: 'A troca do token (back-channel) deve ocorrer exclusivamente no servidor. O browser só participa dos redirects.',
            itens: [
                'Next.js App Router: Route Handler em app/login/route.ts.',
                'Next.js Pages Router: API route em pages/api/auth/callback.ts.',
                'React SPA pura: precisa de um backend (BFF) — não chame a API do hub direto do browser.',
            ],
            aviso: 'Nunca exponha a chamada GET /api/v1/login/{slug}?token= no client-side. O token trafega na URL e deve ser trocado imediatamente no servidor.',
        },
        {
            id: 'env-next',
            titulo: 'Variáveis de ambiente',
            resumo: 'Variáveis server-only no Next.js (sem prefixo NEXT_PUBLIC_ para URLs internas).',
            codigo: {
                rotulo: '.env.local',
                conteudo: `ACL_URL=https://login.seudominio.gov.br
ACL_SLUG=meu-sistema

# Server-only (back-channel em Docker/proxy)
ACL_API_URL=http://nginx-interno
ACL_API_HOST=login.seudominio.gov.br

# iron-session: mínimo 32 caracteres
SESSION_SECRET=chave-secreta-de-pelo-menos-32-caracteres

# Público (apenas se precisar montar URL no client — prefira redirect server-side)
# NEXT_PUBLIC_ACL_URL=https://login.seudominio.gov.br`,
            },
        },
        {
            id: 'middleware-next',
            titulo: 'Middleware: proteger rotas',
            resumo: 'Redirecione visitantes sem sessão para a rota de login local, que por sua vez envia ao hub.',
            codigo: {
                rotulo: 'middleware.ts (App Router)',
                conteudo: `import { NextResponse } from 'next/server'
import type { NextRequest } from 'next/server'
import { getIronSession } from 'iron-session'
import { sessionOptions, SessionData } from '@/lib/session'

export async function middleware(request: NextRequest) {
    const response = NextResponse.next()
    const session = await getIronSession<SessionData>(request, response, sessionOptions)

    if (!session.usuario_acl && !request.nextUrl.pathname.startsWith('/login')) {
        const loginUrl = new URL('/login', request.url)
        loginUrl.searchParams.set('next', request.nextUrl.pathname)
        return NextResponse.redirect(loginUrl)
    }

    return response
}

export const config = {
    matcher: ['/((?!_next/static|_next/image|favicon.ico).*)'],
}`,
            },
        },
        {
            id: 'route-handler',
            titulo: 'Route Handler: login e callback',
            resumo: 'Uma rota trata redirect ao hub e recebimento do callback.',
            codigo: {
                rotulo: 'app/login/route.ts',
                conteudo: `import { NextRequest, NextResponse } from 'next/server'
import { getIronSession } from 'iron-session'
import { exchangeToken, buildLoginUrl } from '@/lib/acl'
import { sessionOptions, SessionData } from '@/lib/session'

export async function GET(request: NextRequest) {
    const callback = request.nextUrl.searchParams.get('callback')

    if (callback) {
        try {
            const dados = await exchangeToken(callback)
            const destino = request.nextUrl.searchParams.get('next') ?? '/'
            // IMPORTANTE: sessão deve ser gravada no MESMO response do redirect
            const response = NextResponse.redirect(new URL(destino, request.url))
            const session = await getIronSession<SessionData>(request, response, sessionOptions)
            session.usuario_acl = dados
            session.email = String(dados.email ?? '')
            await session.save()
            return response
        } catch {
            return NextResponse.redirect(new URL('/login/erro', request.url))
        }
    }

    return NextResponse.redirect(buildLoginUrl())
}`,
            },
            aviso: 'Não use NextResponse.next() para salvar a sessão e depois retorne outro NextResponse.redirect() — o cookie da sessão se perde e o middleware manda de volta ao login em loop.',
        },
        {
            id: 'lib-acl',
            titulo: 'Utilitário server-side (lib/acl.ts)',
            resumo: 'Funções reutilizáveis para URL do hub e troca de token.',
            codigo: {
                rotulo: 'lib/acl.ts',
                conteudo: `const aclUrl = process.env.ACL_URL!
const aclSlug = process.env.ACL_SLUG!
const apiUrl = process.env.ACL_API_URL ?? aclUrl
const apiHost = process.env.ACL_API_HOST

export function buildLoginUrl(): string {
    return \`\${aclUrl}/login/\${aclSlug}\`
}

export async function exchangeToken(token: string) {
    const url = new URL(\`/api/v1/login/\${aclSlug}\`, apiUrl)
    url.searchParams.set('token', token)

    const headers: HeadersInit = { Accept: 'application/json' }
    if (apiHost) headers['Host'] = apiHost

    const res = await fetch(url.toString(), { headers, cache: 'no-store' })

    if (!res.ok) {
        const body = await res.json().catch(() => ({}))
        throw new Error(body.message ?? 'Falha ao validar token')
    }

    const json = await res.json()
    return json.data ?? json
}`,
            },
        },
        {
            id: 'sessao',
            titulo: 'Sessão com iron-session',
            resumo: 'Armazene os dados retornados pelo hub em cookie httpOnly criptografado. SESSION_SECRET é obrigatório no .env.local.',
            codigo: {
                rotulo: 'lib/session.ts',
                conteudo: `import { SessionOptions } from 'iron-session'

export type SessionData = {
    usuario_acl?: Record<string, unknown>
    email?: string
}

export const sessionOptions: SessionOptions = {
    password: process.env.SESSION_SECRET!, // obrigatório — mín. 32 caracteres
    cookieName: 'app_session',
    cookieOptions: {
        secure: process.env.NODE_ENV === 'production',
        httpOnly: true,
    },
}`,
            },
            itens: [
                'Você decide o que persistir: email, nome, permissões, flags.',
                'O hub não impõe formato de sessão — igual ao Clerk, você recebe os dados e escolhe o armazenamento.',
            ],
        },
        {
            id: 'logout-next',
            titulo: 'Logout',
            resumo: 'Destrua a sessão local e redirecione ao logout centralizado.',
            codigo: {
                rotulo: 'app/logout/route.ts',
                conteudo: `export async function GET(request: NextRequest) {
    const response = NextResponse.redirect(
        \`\${process.env.ACL_URL}/logout/\${process.env.ACL_SLUG}\`
    )
    const session = await getIronSession<SessionData>(request, response, sessionOptions)
    session.destroy()
    return response
}`,
            },
        },
        {
            id: 'permissoes-ts',
            titulo: 'Opcional: parsePermissions',
            resumo: 'Filtre permissões pelo slug do sistema — lógica portável do cliente Laravel.',
            codigo: {
                rotulo: 'lib/permissions.ts',
                conteudo: `type Permissao = { nome: string; tipo: number }

export function parsePermissions(dados: Record<string, unknown>, slug: string): Permissao[] {
    const lotacoes = (dados.orgaos as { lotacoes?: unknown[] })?.lotacoes ?? []
    const permissoes: Permissao[] = []

    for (const lotacao of lotacoes) {
        if (!lotacao || typeof lotacao !== 'object') continue
        const perfis = (lotacao as { perfis?: unknown[] }).perfis ?? []
        for (const perfil of perfis) {
            const role = (perfil as { role?: { sistema?: { slug?: string }; permissoes?: unknown[] } }).role
            if (role?.sistema?.slug !== slug) continue
            for (const p of role.permissoes ?? []) {
                const perm = (p as { permission?: { name?: string }; tipo?: number }).permission
                if (perm?.name) permissoes.push({ nome: perm.name, tipo: (p as { tipo?: number }).tipo ?? 0 })
            }
        }
    }

    return permissoes
}`,
            },
        },
        {
            id: 'app-vs-pages',
            titulo: 'App Router vs Pages Router',
            resumo: 'Equivalências rápidas entre as duas abordagens do Next.js.',
            itens: [
                'App Router callback: app/login/route.ts (GET).',
                'Pages Router callback: pages/api/auth/callback.ts.',
                'App Router middleware: middleware.ts na raiz.',
                'Pages Router proteção: getServerSideProps com verificação de sessão.',
                'Em ambos: troca de token apenas em código server-side (Route Handler / API route).',
            ],
        },
    ],
}
