<?php

namespace App\Inventory\Http\Controller;

use App\Foundation\Component\Select\Service\OptionService;
use App\Foundation\Component\Table\Http\Request\TableRequest;
use App\Foundation\Component\Table\Repository\TableRepository;
use App\Foundation\Framework\Http\Controller\Controller;
use App\Foundation\Framework\Http\Response\ResponseEnvelope;
use App\Inventory\Component\Select\ItemCategorySelect;
use App\Inventory\Component\Select\ItemSelect;
use App\Inventory\Component\Table\ItemTable;
use App\Inventory\Enum\ItemSourceType;
use App\Inventory\Http\Request\ItemPageRequest;
use App\Inventory\Http\Resource\ItemDetailResource;
use App\Inventory\Http\Resource\ItemStatusCollection;
use App\Inventory\Query\ItemDetailQuery;
use App\Inventory\Query\ItemStatusQuery;
use Illuminate\Contracts\View\View;
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
        ]);
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

    public function status(string $item): ResponseEnvelope
    {
        $rows = (new ItemStatusQuery)->builder(['stock_id' => $item])->get();

        return ResponseEnvelope::ok(data: ItemStatusCollection::make($rows)->resolve());
    }
}
