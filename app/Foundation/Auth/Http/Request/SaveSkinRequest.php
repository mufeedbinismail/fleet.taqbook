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

    /*
     * An empty skin is the one that leaves the choice to the browser, and it arrives here as null:
     * every empty string in a request is turned into one before anything of ours is asked about it.
     * Put back, because here it is a value and not the absence of one.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('skin')) {
            $this->merge(['skin' => $this->input('skin') ?? '']);
        }
    }

    public function rules(): array
    {
        return [
            'skin' => ['present', Rule::enum(Skin::class)],
        ];
    }

    public function toSkin(): Skin
    {
        return Skin::from($this->validated('skin'));
    }
}
