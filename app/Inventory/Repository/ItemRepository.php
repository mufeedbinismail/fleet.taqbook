<?php

namespace App\Inventory\Repository;

use App\Foundation\Shared\Exception\ResourceNotFoundException;
use App\Foundation\Shared\ValueObject\ShortCode;
use App\Inventory\Intent\CreateItemIntent;
use App\Inventory\Intent\SaveItemIntent;
use App\Inventory\Intent\UpdateItemIntent;
use App\Inventory\Model\Item;
use App\Inventory\Query\ItemCodeTakenQuery;
use App\Inventory\Query\ItemInUseQuery;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use LogicException;

class ItemRepository
{
    public function findCachedByStockId(string $stockId): ?Item
    {
        return Cache::store('array')->rememberForever(
            "item_{$stockId}",
            fn () => Item::find($stockId)
        );
    }

    /**
     * @throws LogicException when no transaction is open, since the lock would be released at once
     */
    public function findItemForUpdate(string $stockId): ?Item
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException("Item [{$stockId}] was locked outside a transaction.");
        }

        return Item::query()->lockForUpdate()->find($stockId);
    }

    public function isItemCodeTaken(ShortCode $stockId): bool
    {
        return (new ItemCodeTakenQuery)->builder(['code' => (string) $stockId])->exists();
    }

    public function isItemInUse(string $stockId): bool
    {
        return (bool) (new ItemInUseQuery)->builder(['stock_id' => $stockId])->first()?->is_in_use;
    }

    public function createItem(CreateItemIntent $intent): Item
    {
        $stockId = (string) $intent->stockId;

        $item = Item::create(['stock_id' => $stockId, ...$this->fields($intent)]);

        DB::table('loc_stock')->insertUsing(
            ['loc_code', 'stock_id'],
            DB::table('locations')
                ->select('loc_code')
                ->selectRaw('? as stock_id', [$stockId])
        );

        DB::table('item_codes')->insert([
            'item_code' => $stockId,
            'stock_id' => $stockId,
            'description' => $intent->description,
            'category_id' => $intent->categoryId,
            'quantity' => 1,
            'is_foreign' => 0,
        ]);

        return $item;
    }

    public function updateItem(UpdateItemIntent $intent): Item
    {
        $stockId = (string) $intent->stockId;

        Item::whereKey($stockId)->update($this->fields($intent));

        DB::table('item_codes')
            ->where('item_code', $stockId)
            ->where('stock_id', $stockId)
            ->update([
                'description' => $intent->description,
                'category_id' => $intent->categoryId,
            ]);

        return Item::find($stockId) ?? throw new ResourceNotFoundException("Item [{$stockId}] does not exist.");
    }

    /**
     * Marks the item active or inactive, on its code row as well, which carries the same flag.
     */
    public function setItemInactive(ShortCode $stockId, bool $inactive): void
    {
        Item::whereKey((string) $stockId)->update(['inactive' => (int) $inactive]);

        DB::table('item_codes')
            ->where('item_code', (string) $stockId)
            ->where('stock_id', (string) $stockId)
            ->update(['inactive' => (int) $inactive]);
    }

    public function deleteItem(ShortCode $stockId): void
    {
        $code = (string) $stockId;

        Item::whereKey($code)->delete();
        DB::table('loc_stock')->where('stock_id', $code)->delete();
        DB::table('purch_data')->where('stock_id', $code)->delete();
        DB::table('prices')->where('stock_id', $code)->delete();
        DB::table('bom')->where('parent', $code)->delete();
        DB::table('item_codes')->where('item_code', $code)->delete();
    }

    /**
     * The columns an item is created with and changed by, which the code is not one of.
     *
     * @return array<string, mixed>
     */
    private function fields(SaveItemIntent $intent): array
    {
        return [
            'description' => $intent->description,
            'long_description' => $intent->longDescription,
            'category_id' => $intent->categoryId,
            'tax_type_id' => $intent->taxTypeId,
            'units' => $intent->units,
            'mb_flag' => $intent->type->value,
            'sales_account' => $intent->accounts->sales,
            'cogs_account' => $intent->accounts->cogs,
            'inventory_account' => $intent->accounts->inventory,
            'adjustment_account' => $intent->accounts->adjustment,
            'wip_account' => $intent->accounts->wip,
            'dimension_id' => $intent->dimensionId,
            'dimension2_id' => $intent->dimension2Id,
            'no_sale' => (int) $intent->noSale,
            'no_purchase' => (int) $intent->noPurchase,
            'editable' => (int) $intent->editable,
        ];
    }
}
