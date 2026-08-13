<?php

namespace App\Foundation\Component\Select\Http\Response;

use App\Foundation\Component\Select\ValueObject\Option;
use App\Foundation\Component\Select\ValueObject\OptionPage;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;

/**
 * The shape every option endpoint answers in, written here and nowhere else.
 *
 * `selected` is its own key rather than rows inside `data` because a held value is still
 * legitimate when it sorts onto a page nobody asked for. Deciding validity by whether the value
 * came back among the rows would discard a good choice the moment the list grew past one page.
 *
 * `has_more` rather than a total: nothing displays a count, and whether to ask for another page is
 * the only question the list has. `data` and `meta` are named so that a consumer wanting to hand
 * back a paginator directly would already fit.
 */
final class SelectResponse implements Responsable
{
    private function __construct(private readonly OptionPage $page) {}

    public static function of(OptionPage $page): self
    {
        return new self($page);
    }

    public function toResponse($request): JsonResponse
    {
        return response()->json([
            'data' => array_map($this->row(...), $this->page->options),
            'selected' => array_map($this->row(...), $this->page->selected),
            'meta' => ['has_more' => $this->page->hasMore],
        ]);
    }

    /**
     * A row's own `data` is the extra it carries into the markup, and is nested inside the row
     * rather than spread across it so that a column named like one of the keys above cannot quietly
     * take that key's place.
     *
     * @return array{value: string, label: string, description: string|null, disabled: bool,
     *               group: string|null, data: array<string, string>}
     */
    private function row(Option $option): array
    {
        return [
            'value' => $option->value,
            'label' => $option->label,
            'description' => $option->description,
            'disabled' => $option->disabled,
            'group' => $option->group,
            'data' => $option->data,
        ];
    }
}
