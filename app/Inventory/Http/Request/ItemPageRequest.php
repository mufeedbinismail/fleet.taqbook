<?php

namespace App\Inventory\Http\Request;

use App\Foundation\Shared\ValueObject\ShortCode;
use App\Inventory\Enum\ItemTab;
use App\Inventory\ValueObject\ItemSelection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemPageRequest extends FormRequest
{
    /**
     * The route's permission middleware has already decided who may open the page.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item' => ['nullable', 'string', 'max:'.ShortCode::MAX_LENGTH, 'regex:'.ShortCode::PATTERN],
            'tab' => ['nullable', Rule::enum(ItemTab::class)],
        ];
    }

    public function selection(): ItemSelection
    {
        $valid = $this->getValidatorInstance()->validated();

        return new ItemSelection(
            isset($valid['item']) ? ShortCode::tryFrom($valid['item']) : null,
            isset($valid['tab']) ? (ItemTab::tryFrom($valid['tab']) ?? ItemTab::General) : ItemTab::General,
        );
    }
}
