<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Resources\SistemaResource;
use App\Http\Services\Autenticacao\AutenticacaoService;
use App\Models\Sistema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Response;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AutenticacaoController extends Controller
{
    public function index(?string $slug = null): Response|SymfonyResponse
    {
        $slugInformado = $slug !== null && $slug !== '';
        $slugResolvido = $slug ?: 'login';

        $sistema = Sistema::query()
            ->where('slug', $slugResolvido)
            ->where('ativo', true)
            ->first();

        if ($slugInformado && ! $sistema) {
            return Inertia::render('Auth/SlugInvalido', [
                'slug' => $slugResolvido,
            ]);
        }

        $sistemaExterno = $sistema !== null && $sistema->slug !== 'login';

        if ($sistemaExterno) {
            session([
                'return' => $sistema,
                'return_sistema_id' => $sistema->id,
            ]);
        } else {
            session()->forget(['return', 'return_sistema_id']);

            if (auth()->check()) {
                return AutenticacaoService::login(request());
            }
        }

        return Inertia::render('Auth/Login', [
            'sistema' => $sistema ? (new SistemaResource($sistema))->resolve(request()) : null,
        ]);
    }

    public function store(LoginRequest $request): SymfonyResponse
    {
        if (auth()->check() && ! AutenticacaoService::possuiRetornoExterno()) {
            return AutenticacaoService::login($request);
        }

        $eraGuest = auth()->guest();

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if ($eraGuest) {
            $request->session()->regenerate();
        }

        return AutenticacaoService::login($request);
    }

    public function logout(?string $slug = null): SymfonyResponse
    {
        $sistema = $slug ? Sistema::query()->where('slug', $slug)->first() : null;

        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        if ($sistema?->url_logout) {
            return Inertia::location($sistema->url_logout);
        }

        return Inertia::location(route('login'));
    }
}

