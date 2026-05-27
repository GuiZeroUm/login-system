<?php

namespace App\Http\Services\Autenticacao;

use App\Models\Sistema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class AutenticacaoService
{
    public static function possuiRetornoExterno(): bool
    {
        return self::resolverSistemaRetorno() !== null;
    }

    public static function resolverSistemaRetorno(): ?Sistema
    {
        $return = session('return');

        if ($return instanceof Sistema && $return->ativo && $return->slug !== 'login') {
            return $return;
        }

        $id = session('return_sistema_id');

        if (! $id) {
            return null;
        }

        $sistema = Sistema::query()
            ->whereKey($id)
            ->where('ativo', true)
            ->first();

        if ($sistema === null || $sistema->slug === 'login') {
            return null;
        }

        return $sistema;
    }

    /**
     * Realiza o pós-login: redireciona para callback do sistema (se existir)
     * ou segue para o dashboard do próprio login.
     */
    public static function login(Request $request): Response
    {
        $user = auth()->user();
        $sistema = self::resolverSistemaRetorno();

        $user->ultimo_login = now();

        if ($sistema !== null) {
            $token = uniqid('', true);

            $user->remember_token = $token;
            $user->save();

            $payload = Crypt::encrypt([
                'token' => $token,
                'session' => $request->session()->getId(),
                'sistema' => $sistema->slug,
                'validade' => now()->addMinute(),
            ]);

            session()->forget(['return', 'return_sistema_id']);

            return Inertia::location($sistema->url.'?callback='.urlencode($payload));
        }

        session()->forget(['return', 'return_sistema_id']);

        $user->save();

        return Inertia::location(route('dashboard'));
    }
}

