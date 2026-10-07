<?php

namespace App\Foundation\Auth\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class RoleEditorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['nullable', 'uuid'],
        ];
    }

    /**
     * Which role the editor should open on. An address naming one that no longer exists opens
     * blank rather than failing: the link outliving the role is the ordinary case, not an error.
     */
    public function roleId(): ?string
    {
        return $this->validated('role');
    }
}
