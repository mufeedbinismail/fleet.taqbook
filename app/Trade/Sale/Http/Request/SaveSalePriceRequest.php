<?php

namespace App\Trade\Sale\Http\Request;

use App\Foundation\Shared\ValueObject\ShortCode;
use App\Trade\Sale\Intent\CreateSalePriceIntent;
use App\Trade\Sale\Intent\UpdateSalePriceIntent;
use Brick\Math\BigDecimal;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SaveSalePriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $adding = $this->route('price') === null;

        return [
            // A price stays with its item, so only an added price names one.
            ...($adding ? ['stock_id' => ['required', 'string', 'max:'.ShortCode::MAX_LENGTH, 'regex:'.ShortCode::PATTERN]] : []),
            'sales_type_id' => ['required', 'integer', 'min:1'],
            'currency' => ['required', 'string', 'size:3'],
            'price' => ['required', 'string', 'regex:/^\d+(\.\d+)?$/'],
        ];
    }

    public function attributes(): array
    {
        return [
            'stock_id' => __('sale.price.attribute.stock_id'),
            'sales_type_id' => __('sale.price.attribute.sales_type'),
            'currency' => __('sale.price.attribute.currency'),
            'price' => __('sale.price.attribute.price'),
        ];
    }

    /**
     * @throws NotFoundHttpException when the body names a code the page cannot hold
     */
    public function toCreateIntent(): CreateSalePriceIntent
    {
        return new CreateSalePriceIntent(
            ShortCode::tryFrom($this->input('stock_id')) ?? throw new NotFoundHttpException,
            (int) $this->input('sales_type_id'),
            (string) $this->input('currency'),
            BigDecimal::of($this->input('price')),
        );
    }

    public function toUpdateIntent(): UpdateSalePriceIntent
    {
        return new UpdateSalePriceIntent(
            (int) $this->route('price'),
            (int) $this->input('sales_type_id'),
            (string) $this->input('currency'),
            BigDecimal::of($this->input('price')),
        );
    }
}
