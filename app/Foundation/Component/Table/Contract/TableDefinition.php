<?php

namespace App\Foundation\Component\Table\Contract;

use App\Foundation\Component\Table\Builder\TableBuilder;
use App\Foundation\Component\Table\ValueObject\Table;

/**
 * A domain's answer to one table: the rows it is drawn from, and everything it is prepared to be
 * searched, sorted, filtered and written by.
 *
 * Says what a table shows, never who may reach it — implementing or registering one grants no
 * access on its own. Two tables over the same rows are two definitions.
 */
interface TableDefinition
{
    public function table(TableBuilder $tables): Table;
}
