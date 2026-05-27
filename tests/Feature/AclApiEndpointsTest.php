<?php

use App\Models\Api;
use App\Models\Lotacao;
use App\Models\Orgao;
use App\Models\Sistema;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

function criarSistemaAcl(): Sistema
{
    return Sistema::query()->create([
        'nome' => 'Sistema ACL',
        'slug' => 'sistema-acl',
        'url' => 'https://sistema-acl.exemplo.local',
        'ambiente' => 'desenvolvimento',
        'ativo' => true,
    ]);
}

it('GET /api/v1/check/{aclToken} retorna sessão ativa quando existe', function () {
    $sessaoId = 'sessao-ativa-teste';

    DB::table('sessions')->insert([
        'id' => $sessaoId,
        'user_id' => 7,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'payload' => 'payload',
        'last_activity' => now()->timestamp,
    ]);

    $response = $this->getJson("/api/v1/check/{$sessaoId}");

    $response->assertOk()->assertJson([
        'ativo' => true,
        'user_id' => 7,
    ]);
});

it('GET /api/v1/check/{aclToken} retorna inativo quando sessão não existe', function () {
    $response = $this->getJson('/api/v1/check/sessao-inexistente');

    $response->assertOk()->assertJson([
        'ativo' => false,
    ]);
});

it('GET /api/v1/orgaos/{slug} lista somente órgãos vinculados ao sistema', function () {
    $sistema = criarSistemaAcl();

    $orgaoVinculado = Orgao::query()->create([
        'descricao_orgao' => 'Secretaria de Teste',
        'sigla_orgao' => 'SETE',
        'status' => 'ativo',
    ]);

    $orgaoSemVinculo = Orgao::query()->create([
        'descricao_orgao' => 'Órgão Sem Vínculo',
        'sigla_orgao' => 'OSV',
        'status' => 'ativo',
    ]);

    $sistema->orgaos()->attach($orgaoVinculado->id);

    $response = $this->getJson("/api/v1/orgaos/{$sistema->slug}");

    $response->assertOk();
    $response->assertJsonFragment(['id' => $orgaoVinculado->id]);
    $response->assertJsonMissing(['id' => $orgaoSemVinculo->id]);
});

it('GET /api/v1/lotacoes/{slug}/{orgaoId} filtra lotações por órgão informado', function () {
    $sistema = criarSistemaAcl();

    $orgaoA = Orgao::query()->create([
        'descricao_orgao' => 'Orgao A',
        'sigla_orgao' => 'OA',
        'status' => 'ativo',
    ]);

    $orgaoB = Orgao::query()->create([
        'descricao_orgao' => 'Orgao B',
        'sigla_orgao' => 'OB',
        'status' => 'ativo',
    ]);

    $sistema->orgaos()->attach([$orgaoA->id, $orgaoB->id]);

    $lotacaoA = Lotacao::query()->create([
        'nome_lotacao' => 'Lotacao A',
        'orgao_id' => $orgaoA->id,
        'nivel_hierarquico' => 1,
    ]);

    $lotacaoB = Lotacao::query()->create([
        'nome_lotacao' => 'Lotacao B',
        'orgao_id' => $orgaoB->id,
        'nivel_hierarquico' => 1,
    ]);

    $response = $this->getJson("/api/v1/lotacoes/{$sistema->slug}/{$orgaoA->id}");

    $response->assertOk();
    $response->assertJsonFragment(['id' => $lotacaoA->id]);
    $response->assertJsonMissing(['id' => $lotacaoB->id]);
});

it('POST /api/sistema exige token de API válido e retorna sistemas ativos', function () {
    $ativo = criarSistemaAcl();

    Sistema::query()->create([
        'nome' => 'Sistema Inativo',
        'slug' => 'sistema-inativo',
        'url' => 'https://inativo.exemplo.local',
        'ambiente' => 'desenvolvimento',
        'ativo' => false,
    ]);

    $tokenPlano = 'token-api-teste';

    Api::query()->create([
        'nome' => 'Integração Externa',
        'token' => $tokenPlano,
        'sistema_id' => $ativo->id,
        'ativo' => true,
    ]);

    $tokenCriptografado = Crypt::encrypt($tokenPlano);

    $response = $this->withHeader('Authorization', "Bearer {$tokenCriptografado}")
        ->postJson('/api/sistema');

    $response->assertOk();
    $response->assertJsonFragment(['id' => $ativo->id]);
    $response->assertJsonMissing(['slug' => 'sistema-inativo']);
});
