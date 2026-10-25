<?php

namespace App\Inventory\Http\Controller;

use App\Finance\Currency\Component\Select\CurrencySelect;
use App\Foundation\Component\Select\Service\OptionService;
use App\Foundation\Component\Table\Http\Request\TableRequest;
use App\Foundation\Component\Table\Repository\TableRepository;
use App\Foundation\Framework\Http\Controller\Controller;
use App\Foundation\Framework\Http\Response\ResponseEnvelope;
use App\Foundation\Shared\ValueObject\ShortCode;
use App\Inventory\Action\CreateItemAction;
use App\Inventory\Action\DeleteItemAction;
use App\Inventory\Action\DeleteItemImageAction;
use App\Inventory\Action\SetItemActivationAction;
use App\Inventory\Action\UpdateItemAction;
use App\Inventory\Component\Select\ItemCategorySelect;
use App\Inventory\Component\Select\ItemSelect;
use App\Inventory\Component\Table\ItemTable;
use App\Inventory\Enum\ItemSourceType;
use App\Inventory\Http\Request\ItemActivationRequest;
use App\Inventory\Http\Request\ItemPageRequest;
use App\Inventory\Http\Request\SaveItemRequest;
use App\Inventory\Http\Resource\ItemDetailResource;
use App\Inventory\Http\Resource\ItemStatusCollection;
use App\Inventory\Query\ItemDetailQuery;
use App\Inventory\Query\ItemStatusQuery;
use App\Inventory\Repository\ItemImageRepository;
use App\Inventory\Service\ItemBarcodeService;
use App\Trade\Sale\Component\Select\SalesTypeSelect;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ItemController extends Controller
{
    public function index(
        ItemPageRequest $request,
        TableRequest $asked,
        TableRepository $tableRepository,
        OptionService $optionService,
        ItemTable $itemTable
    ): View {
        $table = $itemTable->table();
        $state = $asked->toState($table);

        return view('pages.inventory.items', [
            'title' => __('inventory.item.title'),
            'selection' => $request->selection(),
            'definition' => $table,
            'initial' => $tableRepository->initialPage($table, $state),
            'lookup' => $optionService->lookup(new ItemSelect),
            'categories' => $optionService->channel(new ItemCategorySelect),
            'types' => collect(ItemSourceType::labels())->except(ItemSourceType::FixedAsset->value)->toArray(),
            'salesTypes' => $optionService->channel(new SalesTypeSelect),
            'currencies' => $optionService->channel(new CurrencySelect),
        ]);
    }

    /**
     * @throws AccessDeniedHttpException when the company has not turned barcode generation on
     */
    public function barcode(ItemBarcodeService $service): ResponseEnvelope
    {
        if (! settings()->enableBarcodeGenerationForItems()) {
            throw new AccessDeniedHttpException(__('inventory.item.error.barcode_disabled'));
        }

        return ResponseEnvelope::ok(data: ['barcode' => (string) $service->generate()]);
    }

    /**
     * @throws NotFoundHttpException
     */
    public function show(string $item): ResponseEnvelope
    {
        $detail = (new ItemDetailQuery)->builder(['stock_id' => $item], 'items')->first();

        if ($detail === null) {
            throw new NotFoundHttpException;
        }

        return ResponseEnvelope::ok(data: ItemDetailResource::make($detail)->resolve());
    }

    public function store(SaveItemRequest $request, CreateItemAction $action): ResponseEnvelope
    {
        $item = $action->execute($request->toCreateIntent());

        return ResponseEnvelope::ok(__('inventory.item.notice.created'), ['item' => $item->stock_id]);
    }

    public function update(SaveItemRequest $request, UpdateItemAction $action): ResponseEnvelope
    {
        $item = $action->execute($request->toUpdateIntent());

        return ResponseEnvelope::ok(__('inventory.item.notice.updated'), ['item' => $item->stock_id]);
    }

    /**
     * @throws NotFoundHttpException when the address names a code the page cannot hold
     */
    public function destroy(string $item, DeleteItemAction $action): ResponseEnvelope
    {
        $action->execute(ShortCode::tryFrom($item) ?? throw new NotFoundHttpException);

        return ResponseEnvelope::ok(__('inventory.item.notice.deleted'), ['item' => $item]);
    }

    public function setActivation(ItemActivationRequest $request, SetItemActivationAction $action): ResponseEnvelope
    {
        $intent = $request->toIntent();

        $action->execute($intent);

        return ResponseEnvelope::ok(
            __($intent->inactive ? 'shared.record.deactivated' : 'shared.record.activated', ['record' => __('inventory.item.noun')]),
            ['item' => (string) $intent->stockId, 'inactive' => $intent->inactive],
        );
    }

    /**
     * @throws NotFoundHttpException when the item has no picture
     */
    public function image(string $item, ItemImageRepository $imageRepository): StreamedResponse
    {
        $code = ShortCode::tryFrom($item) ?? throw new NotFoundHttpException;

        return $imageRepository->streamImage($code) ?? throw new NotFoundHttpException;
    }

    /**
     * @throws NotFoundHttpException when the address names a code the page cannot hold
     */
    public function destroyImage(string $item, DeleteItemImageAction $action): ResponseEnvelope
    {
        $action->execute(ShortCode::tryFrom($item) ?? throw new NotFoundHttpException);

        return ResponseEnvelope::ok(__('inventory.item.notice.image_deleted'));
    }

    public function status(string $item): ResponseEnvelope
    {
        $rows = (new ItemStatusQuery)->builder(['stock_id' => $item])->get();

        return ResponseEnvelope::ok(data: ItemStatusCollection::make($rows)->resolve());
    }
}
