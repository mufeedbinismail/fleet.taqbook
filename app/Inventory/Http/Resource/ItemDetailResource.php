<?php

namespace App\Inventory\Http\Resource;

use App\Foundation\Shared\ValueObject\ShortCode;
use App\Inventory\Enum\ItemSourceType;
use App\Inventory\Repository\ItemImageRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An item as the item page reads it: figures as numbers, flags as booleans, and an unset text as null.
 */
class ItemDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $item = $this->resource;
        $type = ItemSourceType::from($item->mb_flag);

        return [
            'stock_id' => $item->stock_id,
            'description' => $item->description,
            'long_description' => $this->text($item->long_description),
            'mb_flag' => $item->mb_flag,
            'type_label' => $type->label(),
            'is_stocked' => $type->isStocked(),
            'is_manufactured' => $type === ItemSourceType::Manufactured,
            'inactive' => (bool) $item->inactive,
            'no_sale' => (bool) $item->no_sale,
            'no_purchase' => (bool) $item->no_purchase,
            'editable' => (bool) $item->editable,
            'has_image' => $this->hasImage($item->stock_id),
            'is_in_use' => (bool) $item->is_in_use,
            'category_id' => (int) $item->category_id,
            'category_name' => $item->category_name,
            'tax_type_id' => (int) $item->tax_type_id,
            'tax_type_name' => $item->tax_type_name,
            'units' => $item->units,
            'unit_name' => $item->unit_name,
            'unit_decimals' => $item->unit_decimals === null ? null : (int) $item->unit_decimals,
            'dimension' => DimensionResource::from($item->dimension_id, $item->dimension_reference, $item->dimension_name),
            'dimension2' => DimensionResource::from($item->dimension2_id, $item->dimension2_reference, $item->dimension2_name),
            'average_cost' => $this->averageCost($item, $type),
            'normal_price' => $item->normal_price === null ? null : (float) $item->normal_price,
            'markup' => $this->markup($item),
            'sales_account' => $this->text($item->sales_account),
            'sales_account_name' => $item->sales_account_name,
            'cogs_account' => $this->text($item->cogs_account),
            'cogs_account_name' => $item->cogs_account_name,
            'inventory_account' => $this->text($item->inventory_account),
            'inventory_account_name' => $item->inventory_account_name,
            'adjustment_account' => $this->text($item->adjustment_account),
            'adjustment_account_name' => $item->adjustment_account_name,
            'wip_account' => $this->text($item->wip_account),
            'wip_account_name' => $item->wip_account_name,
        ];
    }

    /**
     * Whether a picture is stored for the item; a code the page cannot hold has none.
     */
    private function hasImage(string $stockId): bool
    {
        $code = ShortCode::tryFrom($stockId);

        return $code !== null && app(ItemImageRepository::class)->imageExists($code);
    }

    /**
     * What a unit costs on average; a service is shown one only once it has a cost.
     */
    private function averageCost(object $item, ItemSourceType $type): ?float
    {
        $cost = (float) $item->material_cost;

        return $type->isStocked() || $cost > 0 ? $cost : null;
    }

    /**
     * How far the normal price sits above the average cost, as a percentage of the cost; none without a price or a cost to mark up.
     */
    private function markup(object $item): ?float
    {
        $cost = (float) $item->material_cost;

        return $item->normal_price === null || $cost <= 0 ? null : (((float) $item->normal_price - $cost) / $cost) * 100;
    }

    private function text(?string $value): ?string
    {
        return $value === null || $value === '' ? null : $value;
    }
}
