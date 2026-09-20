<?php

namespace App\Fleet\Http\Request;

use App\Fleet\Enum\DeploymentStatus;
use App\Fleet\Intent\ChangeDeploymentStatusIntent;
use App\Foundation\Shared\ValueObject\DomainDateTime;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeDeploymentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(DeploymentStatus::class)],
            'changed_at' => ['required', 'date_format:'.implode(',', DomainDateTime::readableDateTimeFormats())],
        ];
    }

    public function toIntent(): ChangeDeploymentStatusIntent
    {
        return new ChangeDeploymentStatusIntent(
            status: DeploymentStatus::from($this->validated('status')),
            changedAt: DomainDateTime::readDateTime($this->validated('changed_at')),
        );
    }
}
