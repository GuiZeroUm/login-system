<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginValidarRequest;
use App\Http\Services\ValidarService;
use App\Models\Sistema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginController extends Controller
{
    public function __invoke(LoginValidarRequest $request, string $slug): JsonResource|JsonResponse
    {
        Sistema::query()
            ->where('slug', $slug)
            ->where('ativo', true)
            ->firstOrFail();

        return ValidarService::validar($slug, $request->string('token')->toString());
    }
}

