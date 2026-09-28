<?php

namespace App\Legacy\Navigation\Source;

use App\Foundation\Navigation\Contract\LabelContract;
use App\Foundation\Navigation\Contract\NavigationSourceContract;
use App\Legacy\Navigation\Label\AcceleratedLabel;
use App\Legacy\Navigation\Target\LegacyPageTarget;

/**
 * Shared plumbing for the FrontAccounting menu sources. Temporary by design, and not for anything
 * outside App\Legacy to extend.
 */
abstract class LegacySource implements NavigationSourceContract
{
    /**
     * @param  string  $label  a translation key in FrontAccounting's accelerator notation
     */
    protected function label(string $label): LabelContract
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
