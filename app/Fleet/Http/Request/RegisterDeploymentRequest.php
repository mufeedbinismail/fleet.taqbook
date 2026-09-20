<?php

namespace App\Fleet\Http\Request;

use App\Fleet\Constant\DeploymentAlias;
use App\Fleet\Enum\DeploymentStatus;
use App\Fleet\Enum\Hosting;
use App\Fleet\Intent\RegisterDeploymentIntent;
use App\Foundation\Shared\ValueObject\DomainDateTime;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterDeploymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'debtor_no' => ['required', 'integer', 'exists:debtors_master,debtor_no'],
            'alias' => ['required', 'string', 'max:'.DeploymentAlias::LENGTH, 'regex:/'.DeploymentAlias::PATTERN.'/', 'unique:deployments,alias'],
            'status' => ['required', Rule::enum(DeploymentStatus::class)],
            'hosting' => ['required', Rule::enum(Hosting::class)],
            'url' => ['nullable', 'url', 'max:255'],
            'instance_created_date' => ['required', 'date_format:'.implode(',', DomainDateTime::readableDateFormats())],
        ];
    }

    public function toIntent(): RegisterDeploymentIntent
    {
        return new RegisterDeploymentIntent(
            debtorNo: (int) $this->validated('debtor_no'),
            alias: $this->validated('alias'),
            status: DeploymentStatus::from($this->validated('status')),
            hosting: Hosting::from($this->validated('hosting')),
            url: $this->validated('url'),
            instanceCreatedDate: DomainDateTime::readDate($this->validated('instance_created_date')),
        );
    }
}
