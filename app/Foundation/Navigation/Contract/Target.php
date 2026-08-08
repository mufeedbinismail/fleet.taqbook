<?php

namespace App\Foundation\Navigation\Contract;

use Illuminate\Http\Request;

/**
 * Where a destination points.
 */
interface Target
{
    /**
     * Builds the URL, and may throw when it cannot. Implementations resolve lazily rather than at
     * construction, so a target that turns out to be broken fails on its own.
     *
     * Null where the target names a family of addresses rather than one — it can recognise a
     * request without being able to build the address of any particular one.
     */
    public function url(): ?string;

    /**
     * How specifically this target claims the request, or null for no claim. Higher wins, so a
     * target naming three query parameters beats one naming a bare path.
     */
    public function matches(Request $request): ?int;

    /**
     * Stable identity, independent of host and parameter order.
     */
    public function signature(): string;
}
