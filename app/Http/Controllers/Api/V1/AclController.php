<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListarLotacoesRequest;
use App\Http\Requests\Api\V1\ListarOrgaosRequest;
use App\Http\Resources\LotacaoResource;
use App\Http\Resources\OrgaoResource;
use App\Http\Resources\SistemaResource;
use App\Models\Lotacao;
use App\Models\Sistema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class AclController extends Controller
{
    public function check(string $aclToken): JsonResponse
    {
        $sessao = DB::table('sessions')
            ->where('id', $aclToken)
            ->first();

        if (!$sessao) {
            return response()->json(['ativo' => false]);
        }

        return response()->json([
            'ativo' => true,
            'user_id' => $sessao->user_id,
            'last_activity' => $sessao->last_activity,
        ]);
    }

    public function orgaos(ListarOrgaosRequest $request, string $slug, ?int $orgaoId = null): AnonymousResourceCollection
    {
        $sistema = $this->buscarSistemaAtivo($slug);
        $orgaos = $sistema->orgaos()->orderBy('descricao_orgao');

        if ($orgaoId) {
            $orgaos->whereKey($orgaoId);
        }

        return OrgaoResource::collection($orgaos->get());
    }

    public function lotacoes(ListarLotacoesRequest $request, string $slug, ?int $orgaoId = null): AnonymousResourceCollection
    {
        $sistema = $this->buscarSistemaAtivo($slug);
        $orgaosIds = $sistema->orgaos()->pluck('orgaos.id');

        $lotacoes = Lotacao::query()
            ->whereIn('orgao_id', $orgaosIds)
            ->orderBy('nome_lotacao');

        if ($orgaoId) {
            $lotacoes->where('orgao_id', $orgaoId);
        }

        return LotacaoResource::collection($lotacoes->get());
    }

    public function sistemas(): AnonymousResourceCollection
    {
        $sistemas = Sistema::query()
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();

        return SistemaResource::collection($sistemas);
    }

    private function buscarSistemaAtivo(string $slug): Sistema
    {
        return Sistema::query()
            ->where('slug', $slug)
            ->where('ativo', true)
            ->firstOrFail();
    }
}
