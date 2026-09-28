<?php

namespace App\Foundation\Component\Table\Filter;

use App\Foundation\Component\Control\Contract\ControlContract;
use App\Foundation\Component\Table\Contract\FilterContract;
use App\Foundation\Framework\DTO\ValidationResult;

abstract class ColumnFilter implements FilterContract
{
    public function __construct(private readonly ?string $column) {}

    final public function validate(string $key, mixed $raw): ValidationResult
    {
        return $this->control()->validate($key, $raw);
    }

    final public function config(): array
    {
        return $this->control()->config();
    }

    abstract protected function control(): ControlContract;

    protected function column(string $key): string
    {
        return $this->column ?? $key;
    }
}
