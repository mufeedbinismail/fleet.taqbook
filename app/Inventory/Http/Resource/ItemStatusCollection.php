<?php

namespace App\Inventory\Http\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * The places an item is kept at, with how much of it is on hand across all of them.
 */
class ItemStatusCollection extends ResourceCollection
{
    public $collects = ItemStatusResource::class;

    public function toArray(Request $request): array
    {
        return [
            'rows' => $this->collection->map->resolve()->all(),
            'on_hand' => $this->collection->sum(fn (ItemStatusResource $place) => (float) $place->resource->on_hand),
        ];
    }
}
