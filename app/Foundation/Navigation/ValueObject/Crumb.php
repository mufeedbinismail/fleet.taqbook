<?php

namespace App\Foundation\Navigation\ValueObject;

use App\Foundation\Navigation\Contract\Label;
use App\Foundation\Navigation\Entity\Node;

/**
 * One step of a breadcrumb trail.
 *
 * Holds a label rather than the text of one, so a trail built once still reads correctly to a
 * request that disagrees about language.
 *
 * The node is present only for a step that came from the sitemap. A step naming a record has none,
 * which is what tells a renderer that nothing in the tree corresponds to it.
 */
final class Crumb
{
    public function __construct(
        public readonly Label $label,
        public readonly ?string $url = null,
        public readonly ?Node $node = null,
    ) {}

    public static function at(Node $node): self
    {
        return new self($node->label(), $node->url(), $node);
    }

    /**
     * A step naming a record. It carries no address by default: the page that appends one is
     * already on it.
     */
    public static function of(Label|string $label, ?string $url = null): self
    {
        return new self(is_string($label) ? PlainLabel::of($label) : $label, $url);
    }

    public function isDynamic(): bool
    {
        return $this->node === null;
    }
}
