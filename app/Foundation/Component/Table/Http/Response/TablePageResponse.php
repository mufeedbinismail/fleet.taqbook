<?php

namespace App\Foundation\Component\Table\Http\Response;

use App\Foundation\Component\Table\ValueObject\TablePage;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;

/**
 * The answer every table endpoint hands back a page in.
 */
final class TablePageResponse implements Responsable
{
    private function __construct(private readonly TablePage $page) {}

    public static function of(TablePage $page): self
    {
        return new self($page);
    }

    public function toResponse($request): JsonResponse
    {
        return new JsonResponse($this->page->toArray());
    }
}
