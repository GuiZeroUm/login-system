<?php

namespace App\Http\Middleware;

use App\Models\Api;
use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

class ApiAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearerToken = $request->bearerToken();

        if (!$bearerToken) {
            return response()->json(['message' => 'Token não fornecido'], 401);
        }

        try {
            $tokenDecryptado = Crypt::decrypt($bearerToken);
        } catch (DecryptException) {
            return response()->json(['message' => 'Token inválido'], 401);
        }

        $api = Api::query()
            ->where('token', $tokenDecryptado)
            ->where('ativo', true)
            ->first();

        if (!$api) {
            return response()->json(['message' => 'Token não autorizado'], 401);
        }

        $request->attributes->set('api', $api);

        return $next($request);
    }
}

