<?php

namespace App\Inventory\View;

use App\Finance\Ledger\Component\Select\LedgerAccountSelect;
use App\Finance\Shared\Component\Select\DimensionSelect;
use App\Finance\Tax\Component\Select\ItemTaxTypeSelect;
use App\Foundation\Component\Select\Contract\SelectDefinitionContract;
use App\Foundation\Component\Select\Exception\SelectException;
use App\Foundation\Component\Select\Service\OptionService;
use App\Foundation\Component\Select\ValueObject\OptionChannel;
use App\Foundation\Framework\Facade\ClientData;
use App\Inventory\Component\Select\ItemCategorySelect;
use App\Inventory\Component\Select\ItemUnitSelect;
use App\Inventory\Enum\ItemAccount;
use App\Inventory\Enum\ItemSourceType;
use Illuminate\View\View;

/**
 * What the item editor opens with: its pick lists, and the few facts its script acts on.
 *
 * Composed with the editor rather than handed over by the page that hosts it, so the editor stands
 * wherever it is drawn: a modal, a page of its own, or a legacy screen that knows nothing of items.
 * The pick lists go to the markup and the facts to the browser, from one place, so the two halves
 * cannot disagree about the same company.
 */
class ItemEditorComposer
{
    public function __construct(
        private OptionService $optionService,
    ) {}

    public function compose(View $view): void
    {
        $level = settings()->dimensionLevel();

        ClientData::registry()
            ->put('itemEditor', [
                'accounts_by_type' => $this->accountsByType(),
                'accounts' => array_column(ItemAccount::cases(), 'value'),
            ])
            ->settings(['enableBarcodeGenerationForItems'])
            ->routes([
                'inventory.items.show',
                'inventory.items.store',
                'inventory.items.update',
                'inventory.items.activation',
                'inventory.items.barcode',
                'inventory.items.image',
                'inventory.items.image.destroy',
                'inventory.categories.show',
            ])
            ->translations([
                'inventory.item.editor.remove_image.title',
                'inventory.item.editor.remove_image.text',
                'inventory.item.editor.remove_image.confirm',
            ]);

        $view->with([
            'categories' => $this->pickList(new ItemCategorySelect),
            'accountLookup' => $this->optionService->lookup(new LedgerAccountSelect),
            'types' => collect(ItemSourceType::labels())->except(ItemSourceType::FixedAsset->value)->all(),
            'units' => $this->pickList(new ItemUnitSelect),
            'taxTypes' => $this->pickList(new ItemTaxTypeSelect),
            'dimensions' => $level >= 1 ? $this->pickList(new DimensionSelect, ['level' => 1]) : null,
            'dimensions2' => $level >= 2 ? $this->pickList(new DimensionSelect, ['level' => 2]) : null,
            'dimensionLevel' => $level,
            'maxImageKb' => (int) config('legacy.max_image_size'),
        ]);
    }

    /**
     * A short list is declared in full so it is there when the form opens; one that has nothing to declare, or
     * too much, is searched at its address instead.
     *
     * @param  array<string, mixed>  $filters
     */
    private function pickList(SelectDefinitionContract $select, array $filters = []): OptionChannel
    {
        try {
            return $this->optionService->channel($select, $filters);
        } catch (SelectException) {
            return $this->optionService->lookup($select, $filters);
        }
    }

    /**
     * The accounts each type has, so the form shows only those and sends the rest empty.
     *
     * @return array<string, list<string>>
     */
    private function accountsByType(): array
    {
        return collect(ItemSourceType::cases())
            ->reject(fn (ItemSourceType $type) => $type->isFixedAsset())
            ->mapWithKeys(fn (ItemSourceType $type) => [$type->value => array_column($type->accounts(), 'value')])
            ->all();
    }
}
