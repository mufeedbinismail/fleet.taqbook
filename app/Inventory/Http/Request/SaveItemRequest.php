<?php

namespace App\Inventory\Http\Request;

use App\Foundation\Shared\ValueObject\ShortCode;
use App\Inventory\Enum\ItemSourceType;
use App\Inventory\Intent\CreateItemIntent;
use App\Inventory\Intent\UpdateItemIntent;
use App\Inventory\ValueObject\ItemAccounts;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SaveItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $creating = $this->route('item') === null;

        return [
            // A code is chosen once; an update takes it from the address, so a posted one is not read.
            ...($creating ? ['stock_id' => ['required', 'string', 'max:'.ShortCode::MAX_LENGTH, 'regex:'.ShortCode::PATTERN]] : []),
            'description' => ['required', 'string', 'max:200'],
            // The column is a tinytext, so a longer text would be cut or refused by the database.
            'long_description' => ['nullable', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'min:1'],
            'tax_type_id' => ['required', 'integer', 'min:0'],
            'units' => ['required', 'string', 'max:20'],
            'mb_flag' => ['required', Rule::in([
                ItemSourceType::Manufactured->value,
                ItemSourceType::Bought->value,
                ItemSourceType::Service->value,
            ])],
            'sales_account' => ['required', 'string', 'max:15'],
            'cogs_account' => ['required', 'string', 'max:15'],
            'inventory_account' => ['nullable', 'string', 'max:15'],
            'adjustment_account' => ['nullable', 'string', 'max:15'],
            'wip_account' => ['nullable', 'string', 'max:15'],
            'dimension_id' => ['nullable', 'integer', 'min:0'],
            'dimension2_id' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,gif', 'max:'.config('legacy.max_image_size')],
            'no_sale' => ['boolean'],
            'no_purchase' => ['boolean'],
            'editable' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'stock_id' => __('inventory.item.attribute.stock_id'),
            'description' => __('inventory.item.attribute.description'),
            'long_description' => __('inventory.item.attribute.long_description'),
            'category_id' => __('inventory.item.attribute.category'),
            'tax_type_id' => __('inventory.item.attribute.tax_type'),
            'units' => __('inventory.item.attribute.units'),
            'mb_flag' => __('inventory.item.attribute.type'),
            'sales_account' => __('inventory.item.attribute.sales_account'),
            'cogs_account' => __('inventory.item.attribute.cogs_account'),
            'inventory_account' => __('inventory.item.attribute.inventory_account'),
            'adjustment_account' => __('inventory.item.attribute.adjustment_account'),
            'wip_account' => __('inventory.item.attribute.wip_account'),
            'image' => __('inventory.item.attribute.image'),
        ];
    }

    public function messages(): array
    {
        return [
            'stock_id.regex' => __('shared.error.short_code'),
            'image.image' => __('inventory.item.error.image_type'),
            'image.mimes' => __('inventory.item.error.image_type'),
            'image.max' => __('inventory.item.error.image_size', ['max' => config('legacy.max_image_size')]),
            'image.uploaded' => __('inventory.item.error.image_size', ['max' => config('legacy.max_image_size')]),
        ];
    }

    public function toCreateIntent(): CreateItemIntent
    {
        return new CreateItemIntent(...['stockId' => new ShortCode($this->validated('stock_id')), ...$this->fields()]);
    }

    /**
     * @throws NotFoundHttpException when the address names a code the page cannot hold
     */
    public function toUpdateIntent(): UpdateItemIntent
    {
        $stockId = ShortCode::tryFrom($this->route('item')) ?? throw new NotFoundHttpException;

        return new UpdateItemIntent(...['stockId' => $stockId, ...$this->fields()]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fields(): array
    {
        return [
            'description' => $this->validated('description'),
            'longDescription' => $this->validated('long_description') ?? '',
            'categoryId' => (int) $this->validated('category_id'),
            'taxTypeId' => (int) $this->validated('tax_type_id'),
            'units' => $this->validated('units'),
            'type' => ItemSourceType::from($this->validated('mb_flag')),
            'accounts' => new ItemAccounts(
                sales: $this->validated('sales_account'),
                cogs: $this->validated('cogs_account'),
                inventory: $this->validated('inventory_account') ?? '',
                adjustment: $this->validated('adjustment_account') ?? '',
                wip: $this->validated('wip_account') ?? '',
            ),
            'dimensionId' => $this->nullableInt('dimension_id'),
            'dimension2Id' => $this->nullableInt('dimension2_id'),
            'noSale' => $this->boolean('no_sale'),
            'noPurchase' => $this->boolean('no_purchase'),
            'editable' => $this->boolean('editable'),
            'image' => $this->file('image'),
        ];
    }

    private function nullableInt(string $key): ?int
    {
        $value = $this->validated($key);

        return $value === null ? null : (int) $value;
    }
}
