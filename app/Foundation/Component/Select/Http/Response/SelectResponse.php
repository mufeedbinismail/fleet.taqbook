<?php

namespace App\Foundation\Component\Select\Http\Response;

use App\Foundation\Component\Select\ValueObject\OptionPage;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;

/**
 * The shape every option endpoint answers in.
 *
 * `selected` is its own key rather than rows inside `data` because a held value is still
 * legitimate when it sorts onto a page nobody asked for. `has_more` rather than a total, because
 * whether to ask for another page is the only question the list has.
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
            'data' => $this->page->options->toArray(),
            'selected' => $this->page->selected->toArray(),
            'meta' => ['has_more' => $this->page->hasMore],
        ]);
    }
}
