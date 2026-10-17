<?php

namespace App\Inventory\Http\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One item at one location, with what is held, wanted and coming there as numbers.
 */
class ItemStatusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $place = $this->resource;

        return [
            'stock_id' => $place->stock_id,
            'loc_code' => $place->loc_code,
            'location_name' => $place->location_name,
            'location_inactive' => (bool) $place->location_inactive,
            'location_fixed_asset' => (bool) $place->location_fixed_asset,
            'mb_flag' => $place->mb_flag,
            'unit_decimals' => $place->unit_decimals === null ? null : (int) $place->unit_decimals,
            'reorder_level' => (float) $place->reorder_level,
            'on_hand' => (float) $place->on_hand,
            'demand' => (float) $place->demand,
            'on_order' => (float) $place->on_order,
            'available' => (float) $place->available,
        ];
    }
}
