<?php

namespace App\Foundation\Component\Select\ValueObject;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Where a select's choices are fetched from, for a set too large to be declared in full.
 *
 * Decides what is offered, never what is made of what was picked.
 */
final class OptionSource implements Arrayable
{
    /**
     * @param  string  $url  the list to read from: a route name, or a path
     * @param  array<string, mixed>  $params  narrowing fixed where this select is declared rather
     *                                        than chosen later
     * @param  array<string, string>  $dependsOn  parameter name => the name of the control whose
     *                                            value it takes, re-read whenever that one changes
     * @param  int  $minSearch  how much has to be typed before this list is read at all
     */
    public function __construct(
        public readonly string $url,
        public readonly array $params = [],
        public readonly array $dependsOn = [],
        public readonly int $minSearch = 0,
    ) {}

    /**
     * @return array{url: string, params: array<string, mixed>, dependsOn: array<string, string>, minSearch: int}
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'params' => $this->params,
            'dependsOn' => $this->dependsOn,
            'minSearch' => $this->minSearch,
        ];
    }
}
