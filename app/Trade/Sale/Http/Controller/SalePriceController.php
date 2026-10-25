<?php

namespace App\Trade\Sale\Http\Controller;

use App\Finance\Shared\Support\DomainMoney;
use App\Foundation\Framework\Http\Controller\Controller;
use App\Foundation\Framework\Http\Response\ResponseEnvelope;
use App\Trade\Sale\Action\CreateSalePriceAction;
use App\Trade\Sale\Action\DeleteSalePriceAction;
use App\Trade\Sale\Action\SuggestSalePriceAction;
use App\Trade\Sale\Action\UpdateSalePriceAction;
use App\Trade\Sale\Http\Request\SaveSalePriceRequest;
use App\Trade\Sale\Http\Request\SuggestSalePriceRequest;

class SalePriceController extends Controller
{
    public function store(SaveSalePriceRequest $request, CreateSalePriceAction $action): ResponseEnvelope
    {
        $id = $action->execute($request->toCreateIntent());

        return ResponseEnvelope::ok(__('sale.price.notice.created'), ['price' => $id]);
    }

    public function update(SaveSalePriceRequest $request, UpdateSalePriceAction $action): ResponseEnvelope
    {
        $intent = $request->toUpdateIntent();

        $action->execute($intent);

        return ResponseEnvelope::ok(__('sale.price.notice.updated'), ['price' => $intent->priceId]);
    }

    public function destroy(int $price, DeleteSalePriceAction $action): ResponseEnvelope
    {
        $action->execute($price);

        return ResponseEnvelope::ok(__('sale.price.notice.deleted'), ['price' => $price]);
    }

    public function suggest(SuggestSalePriceRequest $request, SuggestSalePriceAction $action): ResponseEnvelope
    {
        $suggestion = $action->execute($request->toIntent());

        return ResponseEnvelope::ok(data: [
            'price' => DomainMoney::value($suggestion->value),
            'source' => $suggestion->source->value,
        ]);
    }
}
