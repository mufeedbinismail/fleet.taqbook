<?php

namespace App\Inventory\Repository;

use App\Inventory\Model\ItemCategory;

class ItemCategoryRepository
{
    public function findItemCategory(int $categoryId): ?ItemCategory
    {
        return ItemCategory::find($categoryId);
    }
}
