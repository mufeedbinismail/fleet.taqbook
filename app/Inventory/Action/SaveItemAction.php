<?php

namespace App\Inventory\Action;

use App\Finance\Ledger\Repository\LedgerAccountRepository;
use App\Foundation\Framework\Exception\ValidationException;
use App\Inventory\Enum\ItemAccount;
use App\Inventory\Intent\SaveItemIntent;
use App\Inventory\Model\Item;
use App\Inventory\Repository\ItemCategoryRepository;
use App\Inventory\Repository\ItemImageRepository;
use App\Inventory\Repository\ItemRepository;
use App\Inventory\Repository\ItemTaxTypeRepository;
use App\Inventory\Repository\ItemUnitRepository;
use App\Inventory\ValueObject\ItemImageSnapshot;
use Closure;
use Illuminate\Support\Facades\DB;
use Throwable;

abstract class SaveItemAction
{
    public function __construct(
        protected ItemRepository $repository,
        protected ItemCategoryRepository $categoryRepository,
        protected ItemTaxTypeRepository $taxTypeRepository,
        protected ItemUnitRepository $unitRepository,
        protected LedgerAccountRepository $ledgerAccountRepository,
        protected ItemImageRepository $imageRepository,
    ) {}

    /**
     * Saves the item and stores its picture, so that either both are kept or neither is.
     *
     * The rows and the file cannot share a transaction, and each can fail after the other has succeeded: the write
     * can fail once the rows are saved, and the commit can fail once the file is written. The file is written last
     * inside the transaction, so a failed write rolls the rows back; a file written for rows that were not committed
     * is put back to what it was.
     *
     * @param  Closure(): Item  $saveItem  validates and writes the rows
     */
    protected function saveWithImage(SaveItemIntent $intent, Closure $saveItem): Item
    {
        $image = $intent->image;
        $previous = $image === null ? null : $this->imageRepository->snapshotImage($intent->stockId);
        $touched = false;
        $committed = false;

        try {
            $item = DB::transaction(function () use ($intent, $image, $saveItem, &$touched) {
                $item = $saveItem();

                if ($image !== null) {
                    $touched = true;
                    $this->imageRepository->storeImage($intent->stockId, $image);
                }

                return $item;
            });

            $committed = true;

            return $item;
        } finally {
            if ($touched && ! $committed) {
                $this->putBackImage($intent, $previous);
            }
        }
    }

    /**
     * A failure to put the picture back is reported, not thrown, so it cannot hide the failure that made it necessary.
     */
    private function putBackImage(SaveItemIntent $intent, ItemImageSnapshot $previous): void
    {
        try {
            $this->imageRepository->restoreImage($intent->stockId, $previous);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    protected function validateGeneralFields(SaveItemIntent $intent): void
    {
        if ($this->categoryRepository->findItemCategory($intent->categoryId) === null) {
            throw new ValidationException(__('inventory.item.error.no_category'), 'category_id');
        }

        if (! $this->taxTypeRepository->itemTaxTypeExists($intent->taxTypeId)) {
            throw new ValidationException(__('inventory.item.error.no_tax_type'), 'tax_type_id');
        }

        if (! $this->unitRepository->itemUnitExists($intent->units)) {
            throw new ValidationException(__('inventory.item.error.no_unit'), 'units');
        }

        $this->validateAccounts($intent);
    }

    /**
     * An account the type has is required and must exist; one it has no use for must be left empty.
     */
    private function validateAccounts(SaveItemIntent $intent): void
    {
        $applicable = $intent->type->accounts();

        foreach ($intent->accounts->toArray() as $column => $code) {
            $account = ItemAccount::from($column);
            $attribute = __('inventory.item.attribute.'.$account->value);

            if (! in_array($account, $applicable, true)) {
                if ($code !== '') {
                    throw new ValidationException(__('inventory.item.error.account_inapplicable', ['account' => $attribute]), $account->value);
                }

                continue;
            }

            if ($code === '') {
                throw new ValidationException(__('inventory.item.error.account_required', ['account' => $attribute]), $account->value);
            }

            if (! $this->ledgerAccountRepository->accountExists($code)) {
                throw new ValidationException(__('inventory.item.error.no_account', ['account' => $attribute]), $account->value);
            }
        }
    }
}
