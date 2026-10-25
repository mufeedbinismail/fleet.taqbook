<?php

namespace App\Trade\Sale\Http\Controller;

use App\Foundation\Framework\Http\Controller\Controller;
use App\Foundation\Framework\Http\Response\ResponseEnvelope;
use App\Foundation\Shared\ValueObject\ShortCode;
use App\Trade\Sale\Http\Resource\SalePriceResource;
use App\Trade\Sale\Query\ItemSalePriceListQuery;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ItemSalePriceController extends Controller
{
    /**
     * @throws NotFoundHttpException when the address names a code the page cannot hold
     */
    public function index(string $item): ResponseEnvelope
    {
        $code = ShortCode::tryFrom($item) ?? throw new NotFoundHttpException;

        $rows = (new ItemSalePriceListQuery)->builder(['stock_id' => (string) $code])->get();

        return ResponseEnvelope::ok(data: ['rows' => SalePriceResource::collection($rows)->resolve()]);
    }
}
