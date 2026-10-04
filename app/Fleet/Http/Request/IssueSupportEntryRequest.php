<?php

namespace App\Fleet\Http\Request;

use App\Fleet\Intent\IssueSupportEntryIntent;
use Illuminate\Foundation\Http\FormRequest;

class IssueSupportEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_login' => ['nullable', 'string', 'max:60'],
        ];
    }

    /**
     * The fleet does not know the client's users, so the login goes as typed and the install judges it.
     */
    public function toIntent(): IssueSupportEntryIntent
    {
        $login = trim((string) $this->validated('target_login'));

        return new IssueSupportEntryIntent(
            employee: $this->user(),
            targetLogin: $login === '' ? null : $login,
        );
    }
}
