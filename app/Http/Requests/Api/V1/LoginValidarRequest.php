<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class LoginValidarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return is_string($this->route('slug')) && $this->route('slug') !== '';
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
        ];
    }
}

