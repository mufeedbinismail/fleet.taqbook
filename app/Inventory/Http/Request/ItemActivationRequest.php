<?php

namespace App\Inventory\Http\Request;

use App\Foundation\Shared\ValueObject\ShortCode;
use App\Inventory\Intent\ItemActivationIntent;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ItemActivationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['inactive' => ['required', 'boolean']];
    }

    /**
     * @throws NotFoundHttpException when the address names a code the page cannot hold
     */
    public function toIntent(): ItemActivationIntent
    {
        $stockId = ShortCode::tryFrom($this->route('item')) ?? throw new NotFoundHttpException;

        return new ItemActivationIntent($stockId, $this->boolean('inactive'));
    }
}
