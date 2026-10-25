<?php

namespace App\Trade\Sale\Http\Resource;

use App\Finance\Shared\Support\DomainMoney;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One price: what an item sells for under a sales type in a currency.
 */
class SalePriceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $price = $this->resource;

        return [
            'id' => (int) $price->id,
            'stock_id' => $price->stock_id,
            'sales_type_id' => (int) $price->sales_type_id,
            'sales_type' => $price->sales_type,
            'currency' => $price->curr_abrev,
            'price' => DomainMoney::value(DomainMoney::of($price->price, $price->curr_abrev)),
        ];
    }
}
