<?php

namespace App\Foundation\Navigation\DTO;

/**
 * Everything one inspection found, gathered rather than thrown so a single run reports all of it.
 */
final class Report
{
    /**
     * @param  array<int, Problem>  $problems
     * @param  array<string, true>  $fatal  keys whose declarations must be dropped
     */
    public function __construct(
        private readonly array $problems = [],
        private readonly array $fatal = [],
    ) {}

    /**
     * @return array<int, Problem>
     */
    public function problems(): array
    {
        return $this->problems;
    }

    /**
     * @return array<string, true>
     */
    public function fatal(): array
    {
        return $this->fatal;
    }

    public function clean(): bool
    {
        return $this->problems === [];
    }

    /**
     * @return array<int, Problem>
     */
    public function ofType(string $type): array
    {
        return array_values(array_filter(
            $this->problems,
            fn (Problem $problem) => $problem->type === $type,
        ));
    }
}
