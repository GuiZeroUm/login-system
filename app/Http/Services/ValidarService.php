<?php

namespace App\Http\Services;

use App\Http\Resources\UserResource;
use App\Http\Services\Usuario\AcessoSistemaResolver;
use App\Models\Sistema;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;

class ValidarService
{
    public static function validar(string $slug, string $tokenEncryptado): UserResource|JsonResponse
    {
        try {
            $payload = Crypt::decrypt($tokenEncryptado);
        } catch (DecryptException) {
            return response()->json(['message' => 'Token inválido'], 401);
        }

        $validade = $payload['validade'] ?? null;
        if (!$validade || now()->isAfter($validade)) {
            return response()->json(['message' => 'Token expirado'], 401);
        }

        if (($payload['sistema'] ?? null) !== $slug) {
            return response()->json(['message' => 'Token não pertence a este sistema'], 401);
        }

        $token = $payload['token'] ?? null;
        if (!$token) {
            return response()->json(['message' => 'Token inválido'], 401);
        }

        $usuario = User::query()->where('remember_token', $token)->first();
        if (!$usuario) {
            return response()->json(['message' => 'Usuário não encontrado'], 401);
        }

        $usuario->remember_token = null;
        $usuario->save();

        $sistema = Sistema::query()
            ->where('slug', $slug)
            ->where('ativo', true)
            ->first();

        if (! $sistema) {
            return response()->json(['message' => 'Sistema não encontrado'], 404);
        }

        $acesso = AcessoSistemaResolver::resolver($usuario, $sistema);

        if (! $acesso['permitido']) {
            return response()->json(['message' => 'Sem permissão para acessar este sistema'], 403);
        }

        return new UserResource($usuario, $sistema, $acesso);
    }
}

