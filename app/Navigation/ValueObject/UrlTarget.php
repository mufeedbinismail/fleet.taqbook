<?php

namespace App\Navigation\ValueObject;

use App\Navigation\Contract\Target;
use Illuminate\Http\Request;

/**
 * A literal URL, for destinations that leave the application — documentation, a support portal,
 * a marketplace console. Never claims a request, since nothing inside the app can be it.
 */
final class UrlTarget implements Target
{
    public function __construct(
        public readonly string $url,
        public readonly bool $external = true,
    ) {}

    public static function to(string $url, bool $external = true): self
    {
        return new self($url, $external);
    }

    public function url(): string
    {
        return $this->url;
    }

    public function matches(Request $request): ?int
    {
        return null;
    }

    public function signature(): string
    {
        return 'url:'.$this->url;
    }
}
