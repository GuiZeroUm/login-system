<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ListarOrgaosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return is_string($this->route('slug')) && $this->route('slug') !== '';
    }

    public function rules(): array
    {
        return [
            'slug' => ['required', 'string'],
            'orgaoId' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function validationData()
    {
        return [
            ...$this->all(),
            'slug' => $this->route('slug'),
            'orgaoId' => $this->route('orgaoId'),
        ];
    }
}
