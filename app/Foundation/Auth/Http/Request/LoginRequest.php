<?php

namespace App\Foundation\Auth\Http\Request;

use App\Foundation\Auth\Intent\LoginIntent;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function toIntent(): LoginIntent
    {
        return new LoginIntent(
            login: $this->validated('username'),
            password: $this->validated('password'),
            ip: (string) $this->ip(),
        );
    }
}
