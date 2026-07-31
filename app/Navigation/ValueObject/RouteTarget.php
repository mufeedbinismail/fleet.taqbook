<?php

namespace App\Navigation\ValueObject;

use App\Navigation\Contract\Target;
use Illuminate\Http\Request;

/**
 * A named Laravel route.
 */
final class RouteTarget implements Target
{
    /**
     * @param  array<string, scalar>  $parameters
     */
    public function __construct(
        public readonly string $name,
        public readonly array $parameters = [],
    ) {}

    public static function to(string $name, array $parameters = []): self
    {
        return new self($name, $parameters);
    }

    public function url(): string
    {
        return route($this->name, $this->parameters);
    }

    public function matches(Request $request): ?int
    {
        $route = $request->route();

        if ($route === null || $route->getName() !== $this->name) {
            return null;
        }

        foreach ($this->parameters as $key => $value) {
            if ((string) $route->parameter($key) !== (string) $value) {
                return null;
            }
        }

        return 1 + count($this->parameters);
    }

    public function signature(): string
    {
        $parameters = $this->parameters;
        ksort($parameters);

        return 'route:'.$this->name.($parameters === [] ? '' : '?'.http_build_query($parameters));
    }
}
