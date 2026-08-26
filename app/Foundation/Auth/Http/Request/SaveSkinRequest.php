<?php

namespace App\Foundation\Auth\Http\Request;

use App\Foundation\Shared\Enum\Skin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSkinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'skin' => ['required', Rule::enum(Skin::class)],
        ];
    }

    public function toSkin(): Skin
    {
        return Skin::from($this->validated('skin'));
    }
}
