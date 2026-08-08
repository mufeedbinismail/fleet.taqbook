<?php

namespace App\Legacy\Navigation\Source;

use App\Legacy\Navigation\ValueObject\AcceleratedLabel;
use App\Legacy\Navigation\ValueObject\LegacyPageTarget;
use App\Foundation\Navigation\Contract\Label;
use App\Foundation\Navigation\Contract\NavigationSource;

/**
 * Shared plumbing for the FrontAccounting menu sources. Temporary by design, and not for anything
 * outside App\Legacy to extend.
 */
abstract class LegacySource implements NavigationSource
{
    /**
     * @param  string  $label  a translation key in FrontAccounting's accelerator notation
     */
    protected function label(string $label): Label
    {
        return new AcceleratedLabel($label);
    }

    /**
     * @param  array<string, scalar>  $query
     */
    protected function script(string $script, array $query = []): LegacyPageTarget
    {
        return LegacyPageTarget::at($script, $query);
    }

    protected function reports(int|string $class): LegacyPageTarget
    {
        return LegacyPageTarget::at('reporting/reports_main.php', ['Class' => (string) $class]);
    }
}
