<?php

namespace App\Foundation\Auth\Http\Request;

use App\Foundation\Auth\Intent\SaveRoleIntent;
use Illuminate\Foundation\Http\FormRequest;

class SaveRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // max:30 needs to be enforced before the query, not after: a name too long for the
            // column reaches MariaDB as a QueryException — a 500 — where the user should be
            // seeing an inline message instead.
            'name' => ['required', 'string', 'max:30'],
            'inactive' => ['required', 'boolean'],
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('foundation.role.attribute.name'),
            'permissions' => __('foundation.role.attribute.permissions'),
        ];
    }

    public function toIntent(): SaveRoleIntent
    {
        return new SaveRoleIntent(
            roleId: $this->roleId(),
            name: $this->validated('name'),
            inactive: $this->boolean('inactive'),
            // array_values, because the validator preserves the client's keys — re-indexing here
            // is what keeps this a plain list rather than a sparse/associative array.
            permissions: array_values(array_unique($this->validated('permissions'))),
        );
    }

    /**
     * The role being edited, or null when one is being created — which is the difference between
     * the two routes reaching this, and the only one.
     */
    private function roleId(): ?int
    {
        $id = $this->route('role');

        return $id === null ? null : (int) $id;
    }
}
