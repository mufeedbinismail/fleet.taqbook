<?php

namespace App\Fleet\Http\Request;

use App\Fleet\Enum\Hosting;
use App\Fleet\Intent\EditDeploymentIntent;
use App\Foundation\Shared\ValueObject\DomainDateTime;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditDeploymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'debtor_no' => ['required', 'integer', 'exists:debtors_master,debtor_no'],
            'hosting' => ['required', Rule::enum(Hosting::class)],
            'url' => ['nullable', 'url', 'max:255'],
            'instance_created_date' => ['required', 'date_format:'.implode(',', DomainDateTime::readableDateFormats())],
        ];
    }

    public function toIntent(): EditDeploymentIntent
    {
        return new EditDeploymentIntent(
            debtorNo: (int) $this->validated('debtor_no'),
            hosting: Hosting::from($this->validated('hosting')),
            url: $this->validated('url'),
            instanceCreatedDate: DomainDateTime::readDate($this->validated('instance_created_date')),
        );
    }
}
