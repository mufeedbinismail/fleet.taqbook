<?php

namespace App\Foundation\Auth\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class SetUserStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inactive' => ['required', 'boolean'],
        ];
    }

    public function deactivates(): bool
    {
        return (bool) $this->validated('inactive');
    }
}
