<?php

namespace Tests\Concern;

use App\Foundation\Framework\Exception\ValidationException;
use Closure;

trait AssertsRefusal
{
    protected function assertRefusedOn(string $field, Closure $attempt, string $message = ''): void
    {
        try {
            $attempt();
        } catch (ValidationException $refusal) {
            $this->assertSame($field, $refusal->field, $message);

            return;
        }

        $this->fail($message !== '' ? $message : 'Nothing was refused.');
    }

    protected function assertNotRefused(Closure $attempt, string $message = ''): void
    {
        try {
            $attempt();
        } catch (ValidationException $refusal) {
            $this->fail(($message !== '' ? $message.': ' : '').'refused with "'.$refusal->getMessage().'"');
        }

        $this->addToAssertionCount(1);
    }
}
