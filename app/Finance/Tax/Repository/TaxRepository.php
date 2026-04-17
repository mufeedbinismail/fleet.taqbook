<?php

namespace App\Finance\Tax\Repository;

use App\Finance\Support\MoneyFactory;
use App\Finance\Tax\Collection\TaxGroupLineCollection;
use App\Finance\Tax\Entity\ItemTaxSetting;
use App\Finance\Tax\Entity\TaxableItem;
use App\Finance\Tax\Entity\TaxSetting;
use App\Finance\Tax\Enum\TaxAlgorithm;
use App\Finance\Tax\Query\ItemTaxType\ItemTaxTypeExemptionsQuery;
use App\Finance\Tax\Query\ItemTaxType\ItemTaxTypeForItemQuery;
use App\Finance\Tax\Query\TaxGroup\TaxGroupLinesQuery;
use Illuminate\Support\Facades\Cache;

class TaxRepository
{
    public function getTaxGroupLines(int $taxGroupId = null, bool $shippingOnly = false): TaxGroupLineCollection
    {
        return TaxGroupLineCollection::fromCollection(
            (new TaxGroupLinesQuery)
                ->builder($taxGroupId, $shippingOnly)
                ->get()
        );
    }

    public function getItemTaxSetting($stockId): ItemTaxSetting
    {
        return Cache::store('array')->rememberForever(
            "itemTaxSetting.$stockId",
            function () use ($stockId) {
                $itemTaxType = (new ItemTaxTypeForItemQuery)
                    ->builder($stockId)
                    ->first();

                $itemTaxTypeExemptions = (new ItemTaxTypeExemptionsQuery)
                    ->builder($itemTaxType->id)
                    ->pluck('tax_type_id')
                    ->all();

                return new ItemTaxSetting(
                    $itemTaxType->id,
                    $itemTaxType->exempt,
                    $itemTaxTypeExemptions
                );
            }
        );
    }

    public function getTaxableItem($stockId, $price): TaxableItem
    {
        return new TaxableItem(
            $this->getItemTaxSetting($stockId),
            MoneyFactory::of($price)
        );
    }

    public function getTaxSetting(
        $taxGroupId,
        $taxIncluded,
        $taxGroupLines = [],
        $taxAlgorithm = null
    ): TaxSetting
    {
        if (is_null($taxAlgorithm)) {
            $taxAlgorithm = \settings()->taxTotalingAlgorithm();
        } else {
            $taxAlgorithm = TaxAlgorithm::from($taxAlgorithm);
        }

        if (empty($taxGroupLines)) {
            $taxGroupLines = $this->getTaxGroupLines($taxGroupId);
        } else {
            $taxGroupLines = TaxGroupLineCollection::fromArray($taxGroupLines);
        }

        return new TaxSetting(
            (int) $taxGroupId,
            (bool) $taxIncluded,
            $taxAlgorithm,
            $taxGroupLines
        );
    }
}