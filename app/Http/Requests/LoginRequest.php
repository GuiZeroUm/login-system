<?php

namespace App\Http\Requests;

use App\Http\Services\Autenticacao\AutenticacaoService;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->guest()
            || AutenticacaoService::possuiRetornoExterno()
            || $this->routeIs('login.store');
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }
}

