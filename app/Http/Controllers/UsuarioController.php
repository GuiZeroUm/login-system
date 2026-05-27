<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsuarioRequest;
use App\Http\Resources\UsuarioListResource;
use App\Http\Services\Usuario\UsuarioService;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class UsuarioController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('can:2.4', only: ['index']),
            new Middleware('can:2.1', only: ['create', 'store']),
            new Middleware('can:2.2', only: ['edit', 'update']),
            new Middleware('can:2.3', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $busca = $request->string('busca')->toString();

        $usuarios = User::query()
            ->when($busca, function ($q) use ($busca) {
                $q->where(function ($inner) use ($busca) {
                    $inner->where('name', 'like', "%{$busca}%")
                        ->orWhere('email', 'like', "%{$busca}%");
                });
            })
            ->orderBy('name')
            ->paginate(min($request->integer('per_page', 15), 100))
            ->withQueryString();

        return Inertia::render('Usuario/Index', [
            'usuarios' => UsuarioListResource::collection($usuarios),
            'filtros' => ['busca' => $busca],
        ]);
    }

    public function create(): Response
    {
        $dados = UsuarioService::dadosFormulario();

        return Inertia::render('Usuario/Form', [
            'usuario' => null,
            ...$dados,
        ]);
    }

    public function store(UsuarioRequest $request): RedirectResponse
    {
        try {
            UsuarioService::salvar($request);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'acessos' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('usuario.index')
            ->with('success', 'Usuário cadastrado com sucesso!');
    }

    public function edit(User $usuario): Response
    {
        $dados = UsuarioService::dadosFormulario();

        return Inertia::render('Usuario/Form', [
            'usuario' => [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'ativo' => $usuario->status_usuario === 'S',
                'administrador_global' => (bool) $usuario->administrador_global,
                'acessos' => UsuarioService::serializarAcessos($usuario),
            ],
            ...$dados,
        ]);
    }

    public function update(UsuarioRequest $request, User $usuario): RedirectResponse
    {
        try {
            UsuarioService::salvar($request, $usuario);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'acessos' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('usuario.index')
            ->with('success', 'Usuário atualizado com sucesso!');
    }

    public function destroy(User $usuario): RedirectResponse
    {
        UsuarioService::excluir($usuario, request()->user()?->id);

        return redirect()
            ->route('usuario.index')
            ->with('success', 'Usuário excluído com sucesso!');
    }
}
