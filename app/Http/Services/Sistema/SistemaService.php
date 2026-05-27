<?php

namespace App\Http\Services\Sistema;

use App\Http\Requests\SistemaRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RoleHasPermission;
use App\Models\Sistema;
use App\Models\SistemaBanco;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SistemaService
{
    public static function save(SistemaRequest $request): Sistema
    {
        return DB::transaction(function () use ($request): Sistema {
            $dados = $request->safe()->except([
                'upload_caminho_logo',
                'upload_caminho_ilustracao',
                'banco',
                'perfis',
                'orgaos_ids',
            ]);

            if ($request->hasFile('upload_caminho_logo')) {
                $dados['caminho_logo'] = $request->file('upload_caminho_logo')->store('sistemas', 'public');
            }

            if ($request->hasFile('upload_caminho_ilustracao')) {
                $dados['caminho_ilustracao'] = $request->file('upload_caminho_ilustracao')->store('sistemas', 'public');
            }

            if (empty($dados['slug']) && ! empty($dados['nome'])) {
                $dados['slug'] = Str::slug($dados['nome']);
            }

            $id = $request->route('sistema');

            if ($id) {
                $sistema = Sistema::query()->findOrFail($id);
                $sistema->update($dados);
            } else {
                $sistema = Sistema::query()->create($dados);
            }

            self::syncBanco($sistema, $request->input('banco'));
            self::syncRelacionamento($sistema, $request->input('orgaos_ids', []));
            self::syncPerfis($sistema, $request->input('perfis', []));

            return $sistema->fresh(['banco', 'permissions', 'roles.permissions', 'orgaos']);
        });
    }

    public static function sincronizarPermissoesTenant(Sistema $sistema): int
    {
        $banco = $sistema->banco;
        if (! $banco) {
            throw new InvalidArgumentException('Sistema sem configuração de banco para sincronização.');
        }

        $senha = $banco->senha ? Crypt::decryptString($banco->senha) : '';
        $connectionName = 'tenant_sync_'.$sistema->id;

        config()->set("database.connections.$connectionName", [
            'driver' => 'pgsql',
            'host' => $banco->host,
            'port' => $banco->porta,
            'database' => $banco->nome_banco,
            'username' => $banco->usuario,
            'password' => $senha,
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => 'prefer',
        ]);

        DB::purge($connectionName);
        $tenant = DB::connection($connectionName);

        if (! Schema::connection($connectionName)->hasTable('permissoes')
            && ! Schema::connection($connectionName)->hasTable('permissions')) {
            throw new InvalidArgumentException('Tabela de permissões não encontrada no banco tenant.');
        }

        $rows = Schema::connection($connectionName)->hasTable('permissoes')
            ? $tenant->table('permissoes')
                ->select(['id', 'descricao', 'tipo_crud'])
                ->orderBy('id')
                ->get()
            : $tenant->table('permissions')
                ->select(['id', 'name', 'tipo_crud'])
                ->orderBy('id')
                ->get()
                ->map(fn ($row) => (object) [
                    'id' => $row->id,
                    'descricao' => $row->name ?? '',
                    'tipo_crud' => $row->tipo_crud ?? 'N',
                ]);

        $sincronizadas = 0;

        DB::transaction(function () use ($rows, $sistema, &$sincronizadas): void {
            foreach ($rows as $row) {
                $descricao = trim((string) ($row->descricao ?? ''));
                if ($descricao === '') {
                    continue;
                }

                Permission::query()->updateOrCreate(
                    [
                        'sistema_id' => $sistema->id,
                        'name' => $descricao,
                    ],
                    [
                        'tipo_crud' => in_array($row->tipo_crud, ['S', 'N'], true) ? $row->tipo_crud : 'N',
                    ],
                );

                $sincronizadas++;
            }

            $sistema->update(['permissions_synced_at' => now()]);
        });

        DB::disconnect($connectionName);

        return $sincronizadas;
    }

    public static function inativar(Sistema $sistema): Sistema
    {
        $sistema->update(['ativo' => false]);

        return $sistema->fresh();
    }

    public static function reativar(Sistema $sistema): Sistema
    {
        $sistema->update(['ativo' => true]);

        return $sistema->fresh();
    }

    /**
     * @param  array<string, mixed>  $banco
     */
    public static function testarConexaoBanco(array $banco, ?SistemaBanco $existente = null): void
    {
        $host = trim((string) ($banco['host'] ?? ''));
        if ($host === '') {
            throw new InvalidArgumentException('Informe o endereço do host.');
        }

        $senha = trim((string) ($banco['senha'] ?? ''));
        if ($senha === '' && $existente?->senha) {
            $senha = Crypt::decryptString($existente->senha);
        }

        $connectionName = 'tenant_test_'.uniqid();

        config()->set("database.connections.$connectionName", [
            'driver' => 'pgsql',
            'host' => $host,
            'port' => (int) ($banco['porta'] ?? 5432),
            'database' => (string) ($banco['nome_banco'] ?? ''),
            'username' => (string) ($banco['usuario'] ?? ''),
            'password' => $senha,
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => 'prefer',
        ]);

        try {
            DB::connection($connectionName)->getPdo();
        } finally {
            DB::purge($connectionName);
        }
    }

    /**
     * @param  array<string, mixed>|null  $banco
     */
    private static function syncBanco(Sistema $sistema, ?array $banco): void
    {
        if (! is_array($banco) || trim((string) ($banco['host'] ?? '')) === '') {
            return;
        }

        $payload = [
            'tipo' => $banco['tipo'],
            'host' => $banco['host'],
            'porta' => (int) $banco['porta'],
            'nome_banco' => $banco['nome_banco'],
            'usuario' => $banco['usuario'],
        ];

        $senhaInformada = trim((string) ($banco['senha'] ?? ''));
        if ($senhaInformada !== '') {
            $payload['senha'] = Crypt::encryptString($senhaInformada);
        }

        $conexao = SistemaBanco::query()->firstWhere('sistema_id', $sistema->id);

        if ($conexao) {
            $conexao->update($payload);

            return;
        }

        SistemaBanco::query()->create([
            'sistema_id' => $sistema->id,
            ...$payload,
        ]);
    }

    /**
     * @param  array<int, int>  $orgaosIds
     */
    private static function syncRelacionamento(Sistema $sistema, array $orgaosIds): void
    {
        $ids = collect($orgaosIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $sistema->orgaos()->sync($ids);
    }

    /**
     * @param  array<int, array<string, mixed>>  $perfis
     */
    private static function syncPerfis(Sistema $sistema, array $perfis): void
    {
        $roleIdsMantidos = [];

        foreach ($perfis as $perfil) {
            $nome = trim((string) ($perfil['name'] ?? ''));
            if ($nome === '') {
                throw new InvalidArgumentException('Perfil com nome inválido.');
            }

            $role = isset($perfil['id'])
                ? Role::query()->where('sistema_id', $sistema->id)->findOrFail((int) $perfil['id'])
                : new Role(['sistema_id' => $sistema->id]);

            $role->name = $nome;
            $role->save();
            $roleIdsMantidos[] = $role->id;

            $permissoes = collect($perfil['permissoes'] ?? [])
                ->map(fn ($item) => [
                    'permission_id' => (int) $item['permission_id'],
                    'tipo' => (int) $item['tipo'],
                ])
                ->unique(fn ($item) => $item['permission_id'].'-'.$item['tipo'])
                ->values()
                ->all();

            RoleHasPermission::query()->where('role_id', $role->id)->delete();

            foreach ($permissoes as $item) {
                $permission = Permission::query()->findOrFail($item['permission_id']);
                if ($permission->sistema_id !== $sistema->id) {
                    throw new InvalidArgumentException('Permissão fora do sistema informado.');
                }

                RoleHasPermission::query()->create([
                    'role_id' => $role->id,
                    'permission_id' => $item['permission_id'],
                    'tipo' => $item['tipo'],
                ]);
            }
        }

        Role::query()
            ->where('sistema_id', $sistema->id)
            ->whereNotIn('id', $roleIdsMantidos)
            ->delete();
    }

}
