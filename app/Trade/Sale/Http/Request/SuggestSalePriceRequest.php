<?php

namespace App\Trade\Sale\Http\Request;

use App\Foundation\Shared\ValueObject\ShortCode;
use App\Trade\Sale\Intent\SuggestSalePriceIntent;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SuggestSalePriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sales_type_id' => ['required', 'integer', 'min:1'],
            'currency' => ['required', 'string', 'size:3'],
        ];
    }

    public function attributes(): array
    {
        return [
            'sales_type_id' => __('sale.price.attribute.sales_type'),
            'currency' => __('sale.price.attribute.currency'),
        ];
    }

    /**
     * @throws NotFoundHttpException when the address names a code the page cannot hold
     */
    public function toIntent(): SuggestSalePriceIntent
    {
        return new SuggestSalePriceIntent(
            ShortCode::tryFrom((string) $this->route('item')) ?? throw new NotFoundHttpException,
            (int) $this->query('sales_type_id'),
            (string) $this->query('currency'),
        );
    }
}
