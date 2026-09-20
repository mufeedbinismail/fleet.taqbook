<?php

namespace App\Fleet\Http\Request;

use App\Fleet\Constant\DeploymentAlias;
use Illuminate\Foundation\Http\FormRequest;

class RenameDeploymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'alias' => [
                'required',
                'string',
                'max:'.DeploymentAlias::LENGTH,
                'regex:/'.DeploymentAlias::PATTERN.'/',
            ],
        ];
    }

    public function alias(): string
    {
        return $this->validated('alias');
    }
}
