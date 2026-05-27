<?php

namespace App\Http\Controllers;

use App\Http\Requests\SistemaRequest;
use App\Http\Resources\SistemaResource;
use App\Models\Orgao;
use App\Models\Permission;
use App\Http\Services\Sistema\SistemaService;
use App\Models\Sistema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use App\Models\SistemaBanco;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class SistemaController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('can:1.4', only: ['index']),
            new Middleware('can:1.1', only: ['create', 'store']),
            new Middleware('can:1.2', only: ['edit', 'update', 'reativar', 'sincronizarPermissoes', 'atualizarPersonalizacao']),
            new Middleware('can:1.3', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $sistemas = Sistema::query()
            ->search($request->string('search')->toString() ?: null)
            ->ambiente($request->string('ambiente')->toString() ?: null)
            ->withCount(['permissions', 'roles'])
            ->orderBy(
                $request->string('sort_by')->toString() ?: 'nome',
                $request->string('sort_direction')->toString() ?: 'asc',
            )
            ->paginate(min($request->integer('per_page', 15), 100))
            ->withQueryString();

        return Inertia::render('Sistema/Index', [
            'dados' => SistemaResource::collection($sistemas),
            'filtros' => $request->only(['search', 'ambiente', 'sort_by', 'sort_direction', 'per_page']),
        ]);
    }

    public function create(Request $request): Response
    {
        $orgaos = Orgao::query()->orderBy('descricao_orgao')->get(['id', 'descricao_orgao']);

        return Inertia::render('Sistema/Create', [
            'params' => $request->all(),
            'catalogoPermissoes' => [],
            'orgaosDisponiveis' => $orgaos,
            'hubUrl' => rtrim(config('app.url'), '/'),
        ]);
    }

    public function store(SistemaRequest $request): RedirectResponse
    {
        SistemaService::save($request);

        return redirect()
            ->route('sistema.index')
            ->with('success', 'Sistema cadastrado com sucesso!');
    }

    public function edit(Request $request, int $sistema): Response
    {
        $registro = Sistema::query()
            ->with([
                'banco',
                'orgaos:id,descricao_orgao',
                'permissions:id,sistema_id,name,tipo_crud',
                'roles.permissions:id,role_id,permission_id,tipo',
            ])
            ->findOrFail($sistema);

        $orgaos = Orgao::query()->orderBy('descricao_orgao')->get(['id', 'descricao_orgao']);

        return Inertia::render('Sistema/Create', [
            'params' => $request->all(),
            'dados' => (new SistemaResource($registro))->resolve($request),
            'catalogoPermissoes' => Permission::query()
                ->where('sistema_id', $registro->id)
                ->orderBy('name')
                ->get(['id', 'name', 'tipo_crud']),
            'orgaosDisponiveis' => $orgaos,
            'hubUrl' => rtrim(config('app.url'), '/'),
        ]);
    }

    public function update(SistemaRequest $request, int $sistema): RedirectResponse
    {
        SistemaService::save($request);

        return redirect()
            ->route('sistema.index')
            ->with('success', 'Sistema atualizado com sucesso!');
    }

    public function destroy(int $sistema): RedirectResponse
    {
        $registro = Sistema::query()->findOrFail($sistema);
        SistemaService::inativar($registro);

        return redirect()
            ->route('sistema.index')
            ->with('success', 'Sistema inativado com sucesso!');
    }

    public function reativar(int $sistema): RedirectResponse
    {
        $registro = Sistema::query()->findOrFail($sistema);
        SistemaService::reativar($registro);

        return redirect()
            ->route('sistema.index')
            ->with('success', 'Sistema reativado com sucesso!');
    }

    public function testarBanco(Request $request): JsonResponse
    {
        if (! Gate::allows('1.1') && ! Gate::allows('1.2')) {
            abort(403);
        }

        $dados = $request->validate([
            'sistema_id' => ['nullable', 'integer', 'exists:sistemas,id'],
            'banco' => ['required', 'array'],
            'banco.tipo' => ['required', Rule::in(['postgresql'])],
            'banco.host' => ['required', 'string', 'max:255'],
            'banco.porta' => ['required', 'integer', 'between:1,65535'],
            'banco.nome_banco' => ['required', 'string', 'max:255'],
            'banco.usuario' => ['required', 'string', 'max:255'],
            'banco.senha' => ['nullable', 'string', 'max:255'],
        ]);

        $existente = isset($dados['sistema_id'])
            ? SistemaBanco::query()->firstWhere('sistema_id', $dados['sistema_id'])
            : null;

        try {
            SistemaService::testarConexaoBanco($dados['banco'], $existente);

            return response()->json([
                'ok' => true,
                'message' => 'Conexão estabelecida com sucesso.',
            ]);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'ok' => false,
                'message' => $exception->getMessage(),
            ], 422);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'ok' => false,
                'message' => 'Não foi possível conectar ao banco. Verifique host, porta, credenciais e se o PostgreSQL está acessível.',
            ], 422);
        }
    }

    public function sincronizarPermissoes(int $sistema): RedirectResponse
    {
        $registro = Sistema::query()->with('banco')->findOrFail($sistema);

        try {
            $total = SistemaService::sincronizarPermissoesTenant($registro);
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('sistema.index')
                ->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('sistema.index')
                ->with('error', 'Falha ao sincronizar permissões do tenant.');
        }

        return redirect()
            ->route('sistema.edit', ['sistema' => $registro->id, 'aba' => 'perfis'])
            ->with('success', "Sincronização concluída com {$total} permissões.");
    }

    public function atualizarPersonalizacao(Request $request, int $sistema): RedirectResponse
    {
        $dados = $request->validate([
            'upload_caminho_logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
            'upload_caminho_ilustracao' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
            'login_nome' => ['nullable', 'string', 'max:255'],
            'tema_login' => ['nullable', 'in:escuro,claro'],
            'login_subtitulo' => ['nullable', 'string', 'max:255'],
            'login_painel_eyebrow' => ['nullable', 'string', 'max:255'],
            'login_painel_titulo' => ['nullable', 'string', 'max:255'],
            'login_painel_descricao' => ['nullable', 'string'],
            'exibir_logo_topo' => ['sometimes', 'boolean'],
            'exibir_bloco_inferior' => ['sometimes', 'boolean'],
            'exibir_degrade_ilustracao' => ['sometimes', 'boolean'],
        ]);

        $registro = Sistema::query()->findOrFail($sistema);

        $payload = [];

        foreach (['login_nome', 'login_subtitulo', 'login_painel_eyebrow', 'login_painel_titulo', 'login_painel_descricao'] as $campo) {
            if (array_key_exists($campo, $dados)) {
                $payload[$campo] = $dados[$campo];
            }
        }

        if (array_key_exists('tema_login', $dados) && $dados['tema_login']) {
            $payload['tema_login'] = $dados['tema_login'];
        }

        foreach (['exibir_logo_topo', 'exibir_bloco_inferior', 'exibir_degrade_ilustracao'] as $campo) {
            if ($request->has($campo)) {
                $payload[$campo] = $request->boolean($campo);
            }
        }

        if ($request->hasFile('upload_caminho_logo')) {
            $payload['caminho_logo'] = $request->file('upload_caminho_logo')->store('sistemas', 'public');
        }

        if ($request->hasFile('upload_caminho_ilustracao')) {
            $payload['caminho_ilustracao'] = $request->file('upload_caminho_ilustracao')->store('sistemas', 'public');
        }

        if ($payload !== []) {
            $registro->update($payload);
        }

        return redirect()
            ->route('sistema.edit', ['sistema' => $registro->id, 'aba' => 'login'])
            ->with('success', 'Personalização atualizada com sucesso!');
    }
}
