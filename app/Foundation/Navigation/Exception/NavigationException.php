<?php

namespace App\Foundation\Navigation\Exception;

use App\Foundation\Navigation\DTO\Problem;
use RuntimeException;

class NavigationException extends RuntimeException
{
    /**
     * @param  array<int, Problem>  $problems
     */
    public function __construct(public readonly array $problems)
    {
        parent::__construct($this->render());
    }

    /**
     * @param  array<int, string>  $keys
     */
    public static function duplicateKeys(array $keys): self
    {
        return new self(array_map(Problem::duplicateKey(...), $keys));
    }

    /**
     * @param  array<int, string>  $keys
     */
    public static function cycles(array $keys): self
    {
        return new self(array_map(
            fn (string $key) => new Problem(
                Problem::CYCLE,
                $key,
                'Is its own ancestor. Walking up from it comes back to it, so no walk over the '
                .'tree that passes through it can finish.',
            ),
            $keys,
        ));
    }

    public static function unaddressed(string $key): self
    {
        return new self([
            new Problem(
                Problem::MISSING_TARGET,
                $key,
                'Is left out of menus but names no address. Nothing lists it, so its address is '
                .'the only way it could ever be recognised.',
            ),
        ]);
    }

    public static function frozen(string $source): self
    {
        return new self([
            new Problem(
                Problem::FROZEN,
                $source,
                'Registered after the sitemap was closed to further contributions. Register from a '
                .'service provider, so it lands before anything reads the sitemap, and never from '
                .'inside another source.',
            ),
        ]);
    }

    private function render(): string
    {
        $lines = array_map(
            fn (Problem $problem) => "  [{$problem->type}] {$problem->subject}: {$problem->detail}",
            $this->problems,
        );

        return sprintf(
            "The navigation sitemap has %d problem(s):\n%s",
            count($this->problems),
            implode("\n", $lines),
        );
    }
}
