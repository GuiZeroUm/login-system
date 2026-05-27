Integração ACL Login (SSO Acre)
Documento técnico para implantar o login via ACL (Single Sign-On do Governo do Acre) em um novo projeto. Não é necessário criar tela de login própria — o ACL é o provedor de identidade. A aplicação apenas redireciona, recebe o token, consulta a API do ACL e cria/atualiza o usuário local.

1. Visão geral
O ACL (https://dev.sead.ac.gov.br/acl em dev / https://acl.ac.gov.br em produção) é o sistema central de login do Governo do Acre. Ele resolve:

Autenticação do cidadão (gov.br) e do servidor.
Retorno dos dados do usuário (CPF, nome, e-mail, foto, nível, órgãos, lotações).
Lista de permissões por lotação que o sistema integrado deve respeitar.
A aplicação cliente:

Redireciona o usuário para a tela de login do ACL.
Recebe um token via querystring (?callback=TOKEN) após o login.
Consulta a API do ACL com esse token para obter os dados completos.
Persiste o usuário e as lotações no banco local.
Inicia a sessão local (auth do Laravel + cookies) e redireciona para o destino.
2. Pré-requisitos no ACL (não é código — é configuração externa)
Antes de qualquer linha de código, é preciso solicitar ao time responsável pelo ACL:

Item	O que é	Onde vai
Slug do sistema	Identificador único da aplicação (ex: dev-guilherme, ECIDADAO)	.env → ACL_SLUG
URL do ACL	URL base do ACL (dev ou prod)	.env → ACL_URL
Token de API	Token estático para chamadas server-to-server (sincronizações)	.env → ACL_API_TOKEN
URL de callback registrada	A URL para onde o ACL deve redirecionar após o login (ex: https://app.ac.gov.br/login)	Cadastrada dentro do ACL, não no .env
Sem isso o login não funciona. O ACL precisa conhecer a aplicação cliente (slug + URL de callback) antes de qualquer teste.

3. Variáveis de ambiente (`.env`)
# URL base do ACL (sem barra no final)
ACL_URL=https://dev.sead.ac.gov.br/acl

# Slug deste sistema dentro do ACL (fornecido pelo time do ACL)
ACL_SLUG=dev-guilherme

# Slug usado pelo app mobile (opcional, só se houver app)
ACL_SLUG_APP=dev-app

# Token de API para chamadas server-to-server (sincronizar órgãos/lotações)
ACL_API_TOKEN=EXEMPLO

# Define se o SSO usa ambiente de produção do gov.br (true/false)
SSO_PRODUCAO=false
Atenção em produção:

ACL_URL=https://acl.ac.gov.br
SSO_PRODUCAO=true
ACL_SLUG muda para o slug de produção da aplicação.
4. Fluxo de autenticação (passo a passo)
┌──────────┐      1. clica "Entrar"        ┌──────────┐
│  Usuário │ ───────────────────────────► │   App    │
└──────────┘                              │ (Laravel)│
                                          └────┬─────┘
                                               │ 2. redirect
                                               ▼
                                  GET {ACL_URL}/login/{ACL_SLUG}
                                               │
                                               ▼
                                          ┌──────────┐
                                          │   ACL    │ ──► gov.br / login interno
                                          └────┬─────┘
                                               │ 3. autentica
                                               ▼
                              redirect ► {APP_URL}/login?callback={TOKEN}
                                               │
                                               ▼
                                          ┌──────────┐
                                          │   App    │ 4. GET {ACL_URL}/api/v1/login/{ACL_SLUG}
                                          │          │    Header: Authorization: Bearer {TOKEN}
                                          └────┬─────┘
                                               │ 5. recebe JSON com dados do usuário
                                               ▼
                                  - upsert em `usuarios`
                                  - upsert em `lotacoes_usuarios`
                                  - cria sessão (auth + cookies)
                                  - redirect para a página original
Detalhamento
Cidadão clica em Entrar. O link aponta para a rota /login da aplicação.
A rota /login redireciona para {ACL_URL}/login/{ACL_SLUG} (guardando antes a URL anterior na sessão para retornar a ela depois).
O ACL autentica o usuário (via gov.br ou login interno).
O ACL redireciona de volta para a URL registrada (a mesma /login da aplicação) com ?callback={TOKEN}.
A aplicação:
Faz GET {ACL_URL}/api/v1/login/{ACL_SLUG} com header Authorization: Bearer {TOKEN}.
Recebe um JSON completo com dados do usuário, órgãos, lotações e permissões.
Faz updateOrCreate no usuarios por CPF.
Sincroniza lotacoes_usuarios.
Grava na sessão: dados do usuário, permissões, lotação atual, órgão atual, lotações vinculadas/subordinadas.
Cria os cookies SSO-USER-ACL (id do usuário no ACL) e ACL-TOKEN (opcional).
Faz auth()->loginUsingId(...).
Redireciona para a URL de origem (ou para o painel).
5. Endpoints do ACL consumidos
Método	Endpoint	Quando é usado	Auth
GET	{ACL_URL}/login/{ACL_SLUG}	Redirect inicial (browser)	—
GET	{ACL_URL}/logout?return={ACL_SLUG}	Logout (browser)	—
GET	{ACL_URL}/api/v1/login/{ACL_SLUG}	Trocar callback por dados do usuário	Authorization: Bearer {TOKEN}
GET	{ACL_URL}/api/v1/orgaos/{ACL_SLUG}	Sincronizar órgãos (comando)	(público com slug)
GET	{ACL_URL}/api/v1/lotacoes/{ACL_SLUG}	Sincronizar lotações (comando)	(público com slug)
Observação: as chamadas usam withoutVerifying() / verify => false em ambientes de homologação por causa de certificado SSL. Em produção, manter verificação.

6. Estrutura do JSON retornado por `GET /api/v1/login/{ACL_SLUG}`
Estrutura mínima esperada (com base no consumo atual em AutenticacaoService):

{
  "id": 1234,
  "cpf": "00000000000",
  "nome": "Fulano de Tal",
  "nivel": "Servidor Público Estadual",
  "email": "fulano@email.com",
  "email_funcional": "fulano@ac.gov.br",
  "foto": "https://.../foto.jpg",
  "usuario_interno": "S",
  "origem": "TURMALINA",
  "cod_situacao_funcional": 1,
  "acl_token": "JWT_OPCIONAL",
  "sistemas": [ { } ],
  "orgaos": [
    {
      "cod_orgao": 10,
      "lotacoes": [
        {
          "cod_lotacao": 200,
          "lotacao_exercicio": true,
          "administrador": false,
          "permissoes": [
            { "id": 1, "tipo": 1 },
            { "id": 1, "tipo": 2 },
            { "id": 5, "tipo": 0 }
          ],
          "lotacoes_subordinadas": [ [ { "cod_lotacao": 201 } ] ]
        }
      ]
    }
  ]
}
Campos críticos:

cpf é a chave primária para o updateOrCreate em usuarios.
orgaos[].lotacoes[].lotacao_exercicio == true define a lotação atual do usuário.
orgaos[].lotacoes[].administrador == true concede acesso total naquela lotação.
permissoes[].id + permissoes[].tipo viram o array de permissões da sessão (formato "{id}.{tipo}" quando tipo != 0, senão só "{id}").
7. Banco de dados — tabelas mínimas
7.1 `usuarios`
CREATE TABLE usuarios (
  id BIGSERIAL PRIMARY KEY,
  id_orgao_exercicio BIGINT NULL,
  nome VARCHAR(255) NOT NULL,
  email VARCHAR(255) NULL,
  cpf VARCHAR(20) NULL UNIQUE,
  nivel VARCHAR(255) NULL,
  email_funcional VARCHAR(255) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
);
A foto não é persistida: vem da sessão (session('user.foto')).

7.2 `orgaos`
CREATE TABLE orgaos (
  id_orgao BIGSERIAL PRIMARY KEY,
  nome_orgao VARCHAR(255) NOT NULL,
  sigla_orgao VARCHAR(100) NULL,
  cnpj VARCHAR(255) NULL,
  status VARCHAR(255) NULL,
  telefone VARCHAR(40) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
);
7.3 `lotacoes`
CREATE TABLE lotacoes (
  id_lotacao BIGINT PRIMARY KEY,
  id_orgao BIGINT NULL,
  nome_lotacao VARCHAR(255) NULL,
  sigla_lotacao VARCHAR(100) NULL,
  tipo_lotacao VARCHAR(100) NULL,
  id_lotacao_pai BIGINT NULL
);
7.4 `lotacoes_usuarios`
CREATE TABLE lotacoes_usuarios (
  id_lotacao_usuario BIGSERIAL PRIMARY KEY,
  id_lotacao BIGINT NOT NULL REFERENCES lotacoes(id_lotacao),
  id_usuario BIGINT NOT NULL REFERENCES usuarios(id),
  data_cadastro TIMESTAMP NULL,
  data_ultima_alteracao TIMESTAMP NULL,
  data_exclusao TIMESTAMP NULL
);
7.5 `permissoes` (opcional, mas recomendado)
Catálogo das permissões locais do sistema. Cada id aqui deve bater com o id que o ACL retorna em orgaos[].lotacoes[].permissoes[].

CREATE TABLE permissoes (
  id BIGSERIAL PRIMARY KEY,
  descricao VARCHAR(255) NOT NULL,
  tipo_crud CHAR(1) NOT NULL DEFAULT 'N',  -- 'S' = CRUD (gera 4 sub-permissões), 'N' = simples
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
);
Sem esta tabela, o sistema não consegue traduzir o id numérico do ACL em uma habilidade nomeada (ex: Servicos, Locais).

8. Código a ser criado no novo projeto (Laravel + Vue)
Como o novo projeto é Vue, assume-se a stack típica: Laravel como backend (controlling auth e API) + Vue 3 no front. O fluxo do ACL precisa ser server-side (cookies HTTP-only, sessão Laravel) — não dá para fazer tudo do front.

8.1 Rotas (`routes/web.php`)
use App\Http\Controllers\AclController;

Route::get('/login', [AclController::class, 'index'])->name('login');
Route::get('/logout', [AclController::class, 'logout'])->name('logout');
A rota /login precisa estar registrada no ACL como callback (sem ela, o redirect de volta não chega).

8.2 `AclController` (`app/Http/Controllers/AclController.php`)
Responsabilidades: redirecionar para o ACL, receber o callback, delegar ao service, redirecionar para o destino.

<?php

namespace App\Http\Controllers;

use App\Services\AutenticacaoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AclController extends Controller
{
    public function index(Request $request)
    {
        if ($request->filled('callback')) {
            $autenticar = new AutenticacaoService($request->get('callback'));
            $autenticar->login();

            if (session('redirect')) {
                return redirect(session('redirect'));
            }

            return redirect()->route('painel');
        }

        session()->flash('redirect', url()->previous());

        return redirect(env('ACL_URL') . '/login/' . env('ACL_SLUG'));
    }

    public function logout()
    {
        Auth::logout();
        session()->flush();
        setcookie('SSO-USER-ACL', '', time() - 3600, '/');
        setcookie('ACL-TOKEN', '', time() - 3600, '/');

        return redirect(env('ACL_URL') . '/logout?return=' . env('ACL_SLUG'));
    }
}
8.3 `AutenticacaoService` (`app/Services/AutenticacaoService.php`)
Coração da integração. Recebe o token, consulta a API do ACL, persiste e cria a sessão.

<?php

namespace App\Services;

use App\Models\Lotacao;
use App\Models\Usuario;
use Illuminate\Support\Facades\Http;

class AutenticacaoService
{
    protected array $dados;
    protected array $permissoes = [];
    protected ?Usuario $user = null;
    protected $lotacao_atual = null;
    protected array $lotacoes = [];
    protected array $lotacoes_vinculadas = [];
    protected array $lotacoes_subordinadas = [];

    public function __construct(string $token, ?string $aclSlug = null)
    {
        $slug = $aclSlug ?? env('ACL_SLUG');

        $resposta = Http::withoutVerifying()
            ->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get(env('ACL_URL') . '/api/v1/login/' . $slug);

        if ($resposta->status() !== 200) {
            abort(403, 'Falha ao autenticar no ACL.');
        }

        $this->dados = $resposta->json();
        $this->upsertUsuario();
        $this->montarPermissoes();
        $this->sincronizarLotacoesUsuario();
    }

    protected function upsertUsuario(): void
    {
        $this->user = Usuario::updateOrCreate(
            ['cpf' => $this->dados['cpf']],
            [
                'nome' => $this->dados['nome'],
                'nivel' => $this->dados['nivel'] ?? null,
                'email' => $this->dados['email'] ?? null,
                'email_funcional' => $this->dados['email_funcional'] ?? null,
            ]
        );
    }

    protected function montarPermissoes(): void
    {
        foreach ($this->dados['orgaos'] ?? [] as $orgao) {
            $this->lotacoes_vinculadas = array_merge(
                $this->lotacoes_vinculadas,
                $orgao['lotacoes'] ?? []
            );

            foreach ($orgao['lotacoes'] ?? [] as $lotacao) {
                if (empty($lotacao['cod_lotacao'])) continue;

                $dadoLotacao = Lotacao::with('orgao')->find($lotacao['cod_lotacao']);
                if ($dadoLotacao) {
                    $this->lotacoes[] = $dadoLotacao;

                    if (!empty($lotacao['lotacao_exercicio'])) {
                        $this->lotacao_atual = $dadoLotacao;
                        foreach ($lotacao['lotacoes_subordinadas'] ?? [] as $sub) {
                            if (isset($sub[0])) {
                                $this->lotacoes_subordinadas[] = $sub[0];
                            }
                        }
                    }
                }

                $this->permissoes[$lotacao['cod_lotacao']] = [];

                if (!empty($lotacao['administrador'])) {
                    $this->permissoes[$lotacao['cod_lotacao']]['administrador'] = true;
                    continue;
                }

                foreach ($lotacao['permissoes'] ?? [] as $p) {
                    $chave = $p['id'] . ($p['tipo'] != 0 ? '.' . $p['tipo'] : '');
                    $this->permissoes[$lotacao['cod_lotacao']][$chave] = true;
                }
            }
        }
    }

    protected function sincronizarLotacoesUsuario(): void
    {
        $ids = [];
        foreach ($this->lotacoes as $lotacao) {
            $vinculo = $this->user->lotacoesUsuarios()->updateOrCreate([
                'id_lotacao' => $lotacao->id_lotacao,
                'id_usuario' => $this->user->id,
            ]);
            $ids[] = $vinculo->id_lotacao;
        }
        $this->user->lotacoesUsuarios()
            ->whereNotIn('id_lotacao', $ids)
            ->delete();
    }

    public function login(): void
    {
        setcookie('SSO-USER-ACL', $this->dados['id'], time() + (86400 * 30), '/');

        if (!empty($this->dados['acl_token'])) {
            setcookie('ACL-TOKEN', $this->dados['acl_token'], time() + (86400 * 30), '/');
        }

        auth()->loginUsingId($this->user->id);

        session([
            'user' => $this->dados,
            'permissions' => $this->permissoes,
            'lotacao_atual' => $this->lotacao_atual,
            'orgao_atual' => $this->lotacao_atual->orgao ?? null,
            'lotacoes' => $this->lotacoes,
            'lotacoes_vinculadas' => $this->lotacoes_vinculadas,
            'lotacoes_subordinadas' => $this->lotacoes_subordinadas,
            'sistemas' => $this->dados['sistemas'] ?? null,
            'SSO-USER-ACL' => $this->dados['id'],
            'servidor' => $this->dados['usuario_interno'] ?? 'N',
        ]);
    }

    public function getDados(): array
    {
        return $this->dados;
    }

    public static function logout(): void
    {
        session()->forget([
            'user', 'permissions', 'lotacao_atual',
            'orgao_atual', 'lotacoes',
            'lotacoes_vinculadas', 'lotacoes_subordinadas',
        ]);
        auth()->logout();
        setcookie('SSO-USER-ACL', '', time() - 3600, '/');
        setcookie('ACL-TOKEN', '', time() - 3600, '/');
    }
}
8.4 Middleware `Authenticate` (`app/Http/Middleware/Authenticate.php`)
Garante que cookie SSO-USER-ACL e sessão estão consistentes — se não, força logout e novo login.

public function handle($request, Closure $next, ...$guards)
{
    $cookieAcl = $_COOKIE['SSO-USER-ACL'] ?? null;
    $sessionAcl = session('SSO-USER-ACL');

    if (!$cookieAcl || !auth()->check() || (int) $cookieAcl !== (int) $sessionAcl) {
        AutenticacaoService::logout();
        return redirect()->route('login');
    }

    return $next($request);
}
8.5 Middleware `CheckPermissao` (opcional, recomendado)
Aplica autorização por permissão na rota. Uso: ->middleware('permissao:5|7').

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CheckPermissao
{
    public function handle(Request $request, Closure $next, string $perm)
    {
        $permissoes = explode('|', $perm);

        if (Gate::any($permissoes)) {
            return $next($request);
        }

        return abort(403);
    }
}
Registrar em app/Http/Kernel.php:

protected $routeMiddleware = [
    // ...
    'permissao' => \App\Http\Middleware\CheckPermissao::class,
];
8.6 `PermissoesService` (definição dos `Gate`s)
Lê a tabela permissoes e registra um Gate para cada permissão, baseado na sessão.

namespace App\Services;

use App\Models\Permissao;
use App\Models\Usuario;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class PermissoesService
{
    public static function init(): void
    {
        if (!Schema::hasTable('permissoes')) return;

        Gate::define('administrador', function (Usuario $u) {
            $idLotacao = session('lotacao_atual')->id_lotacao ?? null;
            return (bool) ($idLotacao && (session('permissions')[$idLotacao]['administrador'] ?? false));
        });

        foreach (Permissao::all() as $p) {
            Gate::define((string) $p->id, function (Usuario $u) use ($p) {
                $idLotacao = session('lotacao_atual')->id_lotacao ?? null;
                return Gate::allows('administrador')
                    || (bool) ($idLotacao && (session('permissions')[$idLotacao][$p->id] ?? false));
            });

            if ($p->tipo_crud === 'S') {
                for ($i = 1; $i <= 4; $i++) {
                    Gate::define($p->id . '.' . $i, function (Usuario $u) use ($p, $i) {
                        $idLotacao = session('lotacao_atual')->id_lotacao ?? null;
                        return Gate::allows('administrador')
                            || (bool) ($idLotacao && (session('permissions')[$idLotacao][$p->id . '.' . $i] ?? false));
                    });
                }
            }
        }
    }
}
Inicializar em AuthServiceProvider::boot():

public function boot()
{
    $this->registerPolicies();
    \App\Services\PermissoesService::init();
}
8.7 Models necessários
Usuario (extends Authenticatable) — tabela usuarios, com relação lotacoesUsuarios().
Lotacao — primaryKey = 'id_lotacao', relação orgao() para Orgao.
LotacaoUsuario — pivot lotacoes_usuarios (com SoftDeletes e timestamps customizados).
Orgao — primaryKey = 'id_orgao'.
Permissao — tabela permissoes.
8.8 Comandos de sincronização (opcionais)
Para popular orgaos e lotacoes antes do primeiro login (ou periodicamente). Sem eles, o Lotacao::find() retorna null e o usuário fica sem lotação.

app/Console/Commands/SincronizarOrgaos.php:

public function handle()
{
    $url = env('ACL_URL') . '/api/v1/orgaos/' . env('ACL_SLUG');
    foreach (Http::get($url)->json() as $orgao) {
        Orgao::updateOrCreate(
            ['id_orgao' => $orgao['id']],
            [
                'nome_orgao' => $orgao['descricao_orgao'] ?? null,
                'sigla_orgao' => $orgao['sigla_orgao'] ?? null,
                'cnpj' => $orgao['cnpj'] ?? null,
            ]
        );
    }
}
app/Console/Commands/SincronizarLotacoes.php:

public function handle()
{
    $url = env('ACL_URL') . '/api/v1/lotacoes/' . env('ACL_SLUG');
    foreach (Http::get($url)->json() as $lotacao) {
        Lotacao::updateOrCreate(
            [
                'id_lotacao' => $lotacao['cod_lotacao'],
                'id_orgao' => $lotacao['cod_orgao'] ?? null,
            ],
            [
                'nome_lotacao' => $lotacao['nome_lotacao'] ?? null,
                'sigla_lotacao' => $lotacao['sigla_lotacao'] ?? null,
            ]
        );
    }
}
Rodar antes de testar o login:

php artisan sync:orgaos
php artisan sync:lotacoes
9. Integração no front Vue
O Vue não dispara o login direto no ACL. Quem faz isso é o backend Laravel. No componente Vue, basta:

9.1 Botão de entrar
<template>
  <a :href="urlLogin" class="btn btn-primary">Entrar</a>
</template>

<script setup lang="ts">
const urlLogin = '/login'
</script>
O link deve apontar para /login (rota Laravel). É o backend que redireciona para o ACL. Não tente montar a URL do ACL no Vue — perde-se o controle do redirect de retorno.

9.2 Exibir dados do usuário logado
O backend deve expor um endpoint do tipo GET /api/me que devolve os dados da sessão:

Route::middleware('auth')->get('/api/me', function () {
    return [
        'usuario' => auth()->user(),
        'lotacao_atual' => session('lotacao_atual'),
        'orgao_atual' => session('orgao_atual'),
        'permissoes' => session('permissions'),
    ];
});
Composable Vue:

// composables/usarAutenticacao.ts
import { ref } from 'vue'

const usuario = ref(null)

export function usarAutenticacao() {
  async function carregar() {
    const r = await fetch('/api/me', { credentials: 'include' })
    if (r.ok) usuario.value = await r.json()
  }

  function entrar() {
    window.location.href = '/login'
  }

  function sair() {
    window.location.href = '/logout'
  }

  return { usuario, carregar, entrar, sair }
}
Importante: o fetch precisa de credentials: 'include' para enviar o cookie de sessão. Se o front e o back estão em domínios diferentes, configurar CORS com supports_credentials: true e SESSION_DOMAIN corretamente.

10. Checklist de implantação
Marcar item por item ao implantar em um novo projeto:

 Solicitar ao time do ACL: ACL_SLUG + URL de callback registrada.
 Preencher .env com ACL_URL, ACL_SLUG, ACL_API_TOKEN, ACL_SLUG_APP, SSO_PRODUCAO.
 Criar/conferir as 5 tabelas: usuarios, orgaos, lotacoes, lotacoes_usuarios, permissoes.
 Criar os Models: Usuario, Lotacao, LotacaoUsuario, Orgao, Permissao.
 Configurar config/auth.php apontando o provider para App\Models\Usuario.
 Criar AclController com index e logout.
 Criar AutenticacaoService.
 Criar/ajustar middleware Authenticate para validar cookie + sessão.
 Criar middleware CheckPermissao (se houver controle por permissão).
 Criar PermissoesService e registrar no AuthServiceProvider.
 Registrar rotas /login e /logout em routes/web.php.
 Criar comandos sync:orgaos e sync:lotacoes.
 Rodar php artisan sync:orgaos && php artisan sync:lotacoes.
 Front: link "Entrar" → /login; "Sair" → /logout.
 Front: endpoint /api/me para hidratar estado.
 Configurar CORS / SESSION_DOMAIN se front e back forem domínios distintos.
 Testar: clicar em Entrar → autenticar no ACL → voltar logado.
11. Pontos de atenção (gotchas)
A URL de callback precisa estar registrada no ACL. Se o ACL não conhece a URL, ele recusa o redirecionamento.
Slug é case-sensitive. ECIDADAO ≠ ecidadao.
Lotação e órgão precisam existir localmente (lotacoes, orgaos) antes do primeiro login. Caso contrário, Lotacao::find() retorna null e o usuário entra sem lotação atual — o middleware Authenticate aborta com 403.
permissoes.id precisa bater com o ID do ACL. O ACL devolve id numérico; o sistema local registra Gate por esse mesmo id. Se os IDs divergirem, ninguém tem permissão de nada.
Cookies SSO-USER-ACL e ACL-TOKEN são setados via setcookie() (não pelo Laravel) — não passam por EncryptCookies. Se a app usa EncryptCookies, excluir esses dois nomes do encryptionlist.
HTTPS em produção é obrigatório. Cookies de sessão e SSO sem HTTPS quebram entre domínios.
SESSION_DRIVER=file funciona em servidor único. Em cluster, usar redis ou database.
Middleware Authenticate deriruba o usuário se o cookie SSO-USER-ACL sumir (logout em outra aba do gov.br) — comportamento esperado.
Foto não é salva no banco, só na sessão. Se a sessão expirar, foto some até o próximo login.
Para apps mobile, existe um fluxo paralelo (AutenticacaoAplicativoController) usando ACL_SLUG_APP e deep link acgov://open.my.app/acl-callback. Não confundir com o fluxo web.
12. Referência rápida — arquivos do projeto atual
Caminhos no projeto-modelo (acgovbr) para consulta direta ao implementar:

Arquivo	Função
app/Http/Controllers/AclController.php	Entry point do login/logout
app/Services/AutenticacaoService.php	Núcleo da integração com a API
app/Services/PermissoesService.php	Define os Gates
app/Http/Middleware/Authenticate.php	Valida sessão + cookie
app/Http/Middleware/CheckPermissao.php	Autorização por permissão
app/Http/Requests/AutenticacaoRequest.php	FormRequest do callback
app/Models/Usuario.php	Model do usuário (com accessors da sessão)
app/Models/Lotacao.php	Model da lotação
app/Models/LotacaoUsuario.php	Pivot lotação ⇄ usuário
app/Models/Orgao.php	Model do órgão
app/Console/Commands/SincronizarOrgaos.php	Comando sync:orgaos
app/Console/Commands/SincronizarLotacoes.php	Comando sync:lotacoes
app/Providers/AuthServiceProvider.php	Inicializa PermissoesService
routes/web.php	Rotas /login e /logout
.env (linhas 7–13)	Variáveis do ACL
13. TL;DR para implementar em outro projeto
Configurar .env com ACL_URL, ACL_SLUG, ACL_API_TOKEN, ACL_SLUG_APP, SSO_PRODUCAO.
Pedir ao ACL para registrar a URL de callback do novo projeto.
Criar as tabelas usuarios, orgaos, lotacoes, lotacoes_usuarios, permissoes.
Copiar/adaptar AclController, AutenticacaoService, PermissoesService, middlewares e models.
Rodar sync:orgaos e sync:lotacoes.
Apontar o botão "Entrar" do Vue para /login.
Testar.