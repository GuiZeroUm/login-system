<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->routeIs('usuario.store')) {
            return Gate::allows('2.1');
        }

        if ($this->routeIs('usuario.update')) {
            return Gate::allows('2.2');
        }

        return Gate::allows('sistema');
    }

    protected function prepareForValidation(): void
    {
        $acessos = collect($this->input('acessos', []))
            ->filter(fn ($acesso) => is_array($acesso) && filled($acesso['sistema_id'] ?? null))
            ->unique(fn ($acesso) => $acesso['sistema_id'])
            ->values()
            ->all();

        $this->merge([
            'ativo' => $this->boolean('ativo'),
            'administrador_global' => $this->boolean('administrador_global'),
            'acessos' => $acessos,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->route('usuario');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => [
                $userId ? 'nullable' : 'required',
                'string',
                'min:8',
            ],
            'ativo' => ['sometimes', 'boolean'],
            'administrador_global' => ['sometimes', 'boolean'],
            'acessos' => ['nullable', 'array'],
            'acessos.*.sistema_id' => ['required', 'integer', 'exists:sistemas,id'],
            'acessos.*.administrador_sistema' => ['sometimes', 'boolean'],
            'acessos.*.perfis_ids' => ['nullable', 'array'],
            'acessos.*.perfis_ids.*' => ['integer', 'exists:roles,id'],
            'acessos.*.permissoes' => ['nullable', 'array'],
            'acessos.*.permissoes.*.permission_id' => ['required_with:acessos.*.permissoes', 'integer', 'exists:permissions,id'],
            'acessos.*.permissoes.*.tipo' => ['required_with:acessos.*.permissoes', 'integer', Rule::in([0, 1, 2, 3, 4])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->boolean('administrador_global')) {
                return;
            }

            if ($this->input('acessos', []) === []) {
                $validator->errors()->add(
                    'acessos',
                    'Adicione ao menos um sistema na aba Sistemas ou marque administrador global.',
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome completo.',
            'email.required' => 'Informe o e-mail.',
            'email.unique' => 'Este e-mail já está em uso.',
            'password.required' => 'Informe a senha.',
            'password.min' => 'A senha deve ter no mínimo 8 caracteres.',
            'acessos.*.sistema_id.required' => 'Selecione um sistema válido.',
        ];
    }
}
