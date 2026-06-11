import type { DocGuia } from './tipos'

export const guiaLaravel: DocGuia = {
    id: 'laravel',
    titulo: 'Integração Laravel',
    descricao: 'Implemente SSO no seu app Laravel com redirect, callback server-side e sessão local. Referência: projeto teste/ neste repositório.',
    tempoEstimado: '~30 min',
    rota: '/documentacao/laravel',
    passos: [
        {
            id: 'env',
            titulo: 'Variáveis de ambiente',
            resumo: 'Configure a URL pública do hub (browser) e, se necessário, URL interna para chamadas server-side (Docker/proxy).',
            codigo: {
                rotulo: '.env',
                conteudo: `ACL_URL=http://login.test
ACL_SLUG=meu-sistema

# Back-channel (server-side): omita se front e API usam o mesmo host
ACL_API_URL=http://lerd-nginx
ACL_API_HOST=login.test`,
            },
            itens: [
                'ACL_URL: usada em redirects do navegador (/login/{slug}, /logout/{slug}).',
                'ACL_SLUG: idêntico ao slug cadastrado em Sistemas.',
                'ACL_API_URL + ACL_API_HOST: quando o PHP roda em container e login.test não resolve internamente.',
            ],
            aviso: 'Em Docker, o browser acessa login.test no host, mas o PHP pode precisar chamar o nginx interno (lerd-nginx) com header Host: login.test.',
        },
        {
            id: 'config',
            titulo: 'Arquivo config/acl.php',
            resumo: 'Centralize as configurações de integração.',
            codigo: {
                rotulo: 'config/acl.php',
                conteudo: `<?php

return [
    'url' => rtrim(env('ACL_URL', 'http://login.test'), '/'),
    'slug' => env('ACL_SLUG', 'meu-sistema'),
    'api_url' => rtrim(env('ACL_API_URL', env('ACL_URL', 'http://login.test')), '/'),
    'api_host' => env('ACL_API_HOST'),
];`,
            },
        },
        {
            id: 'rota-login',
            titulo: 'Rota GET /login',
            resumo: 'Sem callback, redireciona ao hub. Com ?callback=, troca o token no servidor e cria sessão local.',
            codigo: {
                rotulo: 'AutenticacaoController@index (essencial)',
                conteudo: `public function index(Request $request): RedirectResponse|InertiaResponse
{
    if ($request->filled('callback')) {
        try {
            AutenticacaoAclService::autenticarComCallback(
                $request->string('callback')->toString()
            );
        } catch (InvalidArgumentException $e) {
            return Inertia::render('Auth/ErroLogin', [
                'mensagem' => $e->getMessage(),
                'urlRetry' => AutenticacaoAclService::urlLogin(),
            ]);
        }

        return redirect()->to(session()->pull('url_intended', route('dashboard')));
    }

    return redirect()->away(AutenticacaoAclService::urlLogin());
}`,
            },
            itens: [
                'Registre a rota fora do middleware de autenticação.',
                'Salve url_intended antes de redirecionar ao hub (middleware ou controller).',
                'Em falha, exiba erro em vez de redirecionar silenciosamente ao hub (evita loop).',
            ],
        },
        {
            id: 'trocar-token',
            titulo: 'Trocar token pela API (back-channel)',
            resumo: 'Chame o endpoint imediatamente ao receber o callback. Token de uso único, validade de 1 minuto.',
            codigo: {
                rotulo: 'AutenticacaoAclService::trocarTokenPorDados',
                conteudo: `$url = config('acl.api_url').'/api/v1/login/'.config('acl.slug');

$request = Http::timeout(15)->acceptJson();

if ($host = config('acl.api_host')) {
    $request = $request->withHeaders(['Host' => $host]);
}

$response = $request->get($url, ['token' => $token]);

// 403 → sem permissão | 401 → token inválido/expirado/usado
// 200 → JSON com id, nome, email, orgaos, acesso_sistema...`,
            },
            aviso: 'Use GET com query token. Não use POST com Bearer. O token é revogado após a primeira troca bem-sucedida.',
        },
        {
            id: 'middleware',
            titulo: 'Proteger rotas',
            resumo: 'Middleware que exige sessão ACL e autenticação Laravel.',
            codigo: {
                rotulo: 'AutenticadoAcl middleware',
                conteudo: `public function handle(Request $request, Closure $next): Response
{
    if ($request->session()->has('usuario_acl') && auth()->check()) {
        return $next($request);
    }

    $request->session()->put('url_intended', $request->fullUrl());

    return redirect()->route('login');
}`,
            },
        },
        {
            id: 'logout',
            titulo: 'Logout federado',
            resumo: 'Limpe a sessão local e redirecione ao hub para encerrar a sessão central.',
            codigo: {
                rotulo: 'AutenticacaoController@logout',
                conteudo: `Auth::logout();
$request->session()->forget(['usuario_acl', 'permissoes_acl', /* ... */]);
$request->session()->invalidate();

return redirect()->away(
    config('acl.url').'/logout/'.config('acl.slug')
);`,
            },
        },
        {
            id: 'opcional',
            titulo: 'Opcional: permissões e Inertia',
            resumo: 'O hub retorna permissões aninhadas em orgaos.lotacoes. Filtre pelo slug do sistema e grave na sessão conforme sua necessidade.',
            itens: [
                'User local (firstOrNew por email) — opcional, mas útil com Auth::login().',
                'permissoes_acl na sessão — lista flat { nome, tipo } para gates.',
                'HandleInertiaRequests::share — expõe auth.acl ao frontend.',
                'AclPermissao::pode() — helper local para autorização.',
            ],
        },
        {
            id: 'erros-comuns',
            titulo: 'Erros comuns',
            resumo: 'Checklist de diagnóstico quando a integração não funciona.',
            itens: [
                'Loop infinito no login: back-channel falha (cURL error 7) — ajuste ACL_API_URL/ACL_API_HOST.',
                '403 na API: usuário sem vínculo em user_sistemas para este sistema.',
                '401 token expirado: callback processado após 1 minuto ou token reutilizado.',
                'URL de callback errada no cadastro: deve apontar para /login (rota que lê ?callback=).',
                'ACL_SLUG diferente do slug cadastrado no hub.',
            ],
        },
    ],
}
