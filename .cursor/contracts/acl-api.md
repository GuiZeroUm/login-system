## [GET] /api/v1/check/{aclToken}

**Descrição:** Verifica se a sessão ACL informada ainda está ativa.

**Parâmetros de entrada:**
- `aclToken` (string, obrigatório): identificador da sessão retornado no campo `acl_token`.

**Resposta 200:**
```json
{
  "ativo": true,
  "user_id": 7,
  "last_activity": 1748350000
}
```

**Erros possíveis:**
- 422: validação falhou
- 403: sem permissão
- 404: recurso não encontrado

## [GET] /api/v1/orgaos/{slug}/{orgaoId?}

**Descrição:** Lista órgãos vinculados a um sistema ativo.

**Parâmetros de entrada:**
- `slug` (string, obrigatório): slug do sistema cadastrado.
- `orgaoId` (integer, opcional): filtra por um órgão específico.

**Resposta 200:**
```json
[
  {
    "id": 10,
    "descricao_orgao": "SECRETARIA DE EDUCACAO",
    "sigla_orgao": "SEDUC",
    "cnpj": "00000000000100",
    "status": "ativo"
  }
]
```

**Erros possíveis:**
- 422: validação falhou
- 403: sem permissão
- 404: recurso não encontrado

## [GET] /api/v1/lotacoes/{slug}/{orgaoId?}

**Descrição:** Lista lotações dos órgãos vinculados ao sistema.

**Parâmetros de entrada:**
- `slug` (string, obrigatório): slug do sistema cadastrado.
- `orgaoId` (integer, opcional): filtra lotações por órgão.

**Resposta 200:**
```json
[
  {
    "id": 50,
    "nome_lotacao": "DIRETORIA DE PLANEJAMENTO",
    "sigla_lotacao": "DIPLAN",
    "nivel_hierarquico": 2,
    "subordinada_id": null
  }
]
```

**Erros possíveis:**
- 422: validação falhou
- 403: sem permissão
- 404: recurso não encontrado

## [POST] /api/sistema

**Descrição:** Retorna sistemas ativos para integrações machine-to-machine.

**Parâmetros de entrada:**
- `Authorization` (Bearer token criptografado, obrigatório): token válido cadastrado em `apis`.

**Resposta 200:**
```json
[
  {
    "id": 2,
    "nome": "Sistema Financeiro",
    "slug": "financeiro",
    "url": "https://financeiro.exemplo.gov.br",
    "url_logout": null,
    "ambiente": "production",
    "descricao": null,
    "caminho_logo": null,
    "caminho_ilustracao": null,
    "ativo": true
  }
]
```

**Erros possíveis:**
- 422: validação falhou
- 403: sem permissão
- 404: recurso não encontrado
