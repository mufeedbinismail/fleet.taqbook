<?php

namespace App\Foundation\Auth\Http\Request;

use App\Foundation\Auth\Intent\SaveUserIntent;
use Illuminate\Foundation\Http\FormRequest;

class SaveUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Lengths are enforced before the query rather than after: a value too long for its column
     * reaches MariaDB as a QueryException — a 500 — where the admin should be reading an inline
     * message instead.
     */
    public function rules(): array
    {
        $rules = [
            // Enforced on create and update alike: the account most likely to be set up carelessly
            // is a new one, so exempting a create would exempt exactly the wrong half.
            'password' => [$this->isEditing() ? 'nullable' : 'required', 'string', 'min:6', 'max:100'],
            'real_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'role_id' => ['required', 'integer', 'exists:security_roles,id'],
            'pos' => ['required', 'integer', 'exists:sales_pos,id'],
        ];

        // Absent altogether on an edit, so raw input for it is never read at all: a login is what
        // somebody types to sign in, and moving it would change how they get in without telling
        // them.
        if (! $this->isEditing()) {
            $rules['user_id'] = ['required', 'string', 'min:4', 'max:60'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'user_id' => __('foundation.user.attribute.login'),
            'password' => __('foundation.user.attribute.password'),
            'real_name' => __('foundation.user.attribute.real_name'),
            'role_id' => __('foundation.user.attribute.role'),
            'pos' => __('foundation.user.attribute.pos'),
        ];
    }

    public function toIntent(): SaveUserIntent
    {
        return new SaveUserIntent(
            userId: $this->userId(),
            login: $this->isEditing() ? null : $this->validated('user_id'),
            password: $this->input('password'),
            realName: $this->validated('real_name'),
            phone: (string) ($this->validated('phone') ?? ''),
            email: (string) ($this->validated('email') ?? ''),
            roleId: (int) $this->validated('role_id'),
            pos: (int) $this->validated('pos'),
        );
    }

    /**
     * The user being edited, or null when one is being created — which is the difference between
     * the two routes reaching this, and the only one.
     */
    private function userId(): ?int
    {
        $id = $this->route('user');

        return $id === null ? null : (int) $id;
    }

    private function isEditing(): bool
    {
        return $this->userId() !== null;
    }
}
