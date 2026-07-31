<?php

namespace Tests\Unit\Legacy\Navigation;

use App\Legacy\Navigation\ValueObject\AcceleratedLabel;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AcceleratedLabelTest extends TestCase
{
    /**
     * Each translation marks whichever character suits its own wording, so the accelerator has to
     * be read off the translated string rather than the message it came from.
     */
    public function test_an_accelerator_is_taken_from_the_translation_not_the_message(): void
    {
        $label = new AcceleratedLabel('&Sales');

        $this->assertSame('S', $label->accessKey());

        $this->translate('&Sales', 'Ve&ntes');

        $this->assertSame('Ventes', $label->text());
        $this->assertSame('N', $label->accessKey());
    }

    /**
     * Both quirks are load-bearing: either one changing silently reassigns access keys across the
     * menu, and neither is obvious enough to survive a well-meaning tidy-up unprotected.
     */
    #[DataProvider('notations')]
    public function test_accelerator_notation(string $message, string $text, ?string $accessKey): void
    {
        $label = new AcceleratedLabel($message);

        $this->assertSame($text, $label->text());
        $this->assertSame($accessKey, $label->accessKey());
    }

    /**
     * @return array<string, array{string, string, string|null}>
     */
    public static function notations(): array
    {
        return [
            'the marker is stripped from the text' => ['&Sales', 'Sales', 'S'],
            'the marked character is upper-cased' => ['Sales &order', 'Sales order', 'O'],
            'the last marker wins, and an earlier one is left as text' => ['&Sales &Order', '&Sales Order', 'O'],
            'a doubled marker is a literal ampersand' => ['Cash && Bank', 'Cash & Bank', null],
            'nothing marked leaves no access key' => ['Transactions', 'Transactions', null],
        ];
    }

    private function translate(string $message, string $line): void
    {
        $this->app['translator']->addLines(['*.'.$message => $line], $this->app->getLocale());
    }
}
