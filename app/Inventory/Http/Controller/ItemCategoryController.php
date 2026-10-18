<?php

namespace App\Inventory\Http\Controller;

use App\Foundation\Framework\Http\Controller\Controller;
use App\Foundation\Framework\Http\Response\ResponseEnvelope;
use App\Inventory\Http\Resource\ItemCategoryResource;
use App\Inventory\Query\ItemCategoryDetailQuery;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ItemCategoryController extends Controller
{
    /**
     * @throws NotFoundHttpException
     */
    public function show(int $category): ResponseEnvelope
    {
        $found = (new ItemCategoryDetailQuery)->builder(['category_id' => $category])->first() ?? throw new NotFoundHttpException;

        return ResponseEnvelope::ok(data: ItemCategoryResource::make($found)->resolve());
    }
}
