<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SistemaRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->routeIs('sistema.store')) {
            return Gate::allows('1.1');
        }

        if ($this->routeIs('sistema.update')) {
            return Gate::allows('1.2');
        }

        return Gate::allows('sistema');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sistemas', 'slug')->ignore($this->route('sistema')),
            ],
            'url' => ['required', 'string', 'max:255'],
            'url_logout' => ['nullable', 'string', 'max:255'],
            'ambiente' => ['required', Rule::in(['production', 'homologacao', 'desenvolvimento'])],
            'descricao' => ['nullable', 'string'],
            'login_nome' => ['nullable', 'string', 'max:255'],
            'tema_login' => ['nullable', Rule::in(['escuro', 'claro'])],
            'ativo' => ['sometimes', 'boolean'],
            'upload_caminho_logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
            'upload_caminho_ilustracao' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
            'banco' => ['nullable', 'array'],
            'banco.tipo' => ['required_with:banco', Rule::in(['postgresql'])],
            'banco.host' => ['required_with:banco', 'string', 'max:255'],
            'banco.porta' => ['required_with:banco', 'integer', 'between:1,65535'],
            'banco.nome_banco' => ['required_with:banco', 'string', 'max:255'],
            'banco.usuario' => ['required_with:banco', 'string', 'max:255'],
            'banco.senha' => ['nullable', 'string', 'max:255'],
            'orgaos_ids' => ['nullable', 'array'],
            'orgaos_ids.*' => ['integer', 'exists:orgaos,id'],
            'perfis' => ['nullable', 'array'],
            'perfis.*.id' => ['nullable', 'integer', 'exists:roles,id'],
            'perfis.*.name' => ['required_with:perfis', 'string', 'max:255'],
            'perfis.*.permissoes' => ['nullable', 'array'],
            'perfis.*.permissoes.*.permission_id' => ['required_with:perfis.*.permissoes', 'integer', 'exists:permissions,id'],
            'perfis.*.permissoes.*.tipo' => ['required_with:perfis.*.permissoes', 'integer', Rule::in([0, 1, 2, 3, 4])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nome.required' => 'O campo nome é obrigatório.',
            'slug.required' => 'O slug é obrigatório.',
            'slug.unique' => 'O slug já está em uso.',
            'url.required' => 'O campo URL é obrigatório.',
            'ambiente.required' => 'O ambiente é obrigatório.',
            'upload_caminho_logo.image' => 'A logo deve ser uma imagem válida.',
            'upload_caminho_logo.max' => 'A logo deve ter no máximo 2 MB.',
            'upload_caminho_ilustracao.image' => 'A ilustração deve ser uma imagem válida.',
            'upload_caminho_ilustracao.max' => 'A ilustração deve ter no máximo 2 MB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('ativo')) {
            $this->merge([
                'ativo' => filter_var($this->input('ativo'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        $banco = $this->input('banco');
        if (is_array($banco) && trim((string) ($banco['host'] ?? '')) === '') {
            $this->merge(['banco' => null]);
        }
    }
}
