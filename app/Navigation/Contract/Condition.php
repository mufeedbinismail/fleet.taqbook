<?php

namespace App\Navigation\Contract;

/**
 * Decides whether a declaration exists at all for this request.
 *
 * Deliberately a class rather than a closure: it keeps the whole sitemap serializable, so it can
 * be dumped, diffed in review and audited. Conditions are container-resolved, so they may take
 * dependencies, and one condition can gate many declarations.
 *
 * A condition answers "does this exist at all", identically for every user. A permission answers
 * "may you see it", which does not.
 */
interface Condition
{
    public function __invoke(): bool;
}
