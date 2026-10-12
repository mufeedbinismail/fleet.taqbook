<?php

namespace Tests\Feature\Foundation\Component\Table;

use App\Foundation\Component\Table\Repository\TableRepository;
use App\Foundation\Component\Table\ValueObject\InitialPage;
use App\Foundation\Component\Table\ValueObject\TableState;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Concern\SeedsStock;
use Tests\TestCase;

/**
 * Where a table opens: on the position the address asked for, and only where it asked nothing, on
 * the filters the table declares.
 */
class TableOpeningTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsStock;

    /**
     * *Fails if* an address that asked nothing opens wide, or opens narrowed while telling the
     * browser it is not: the controls are then drawn from a position the rows are not in.
     */
    public function test_an_address_that_asked_nothing_opens_on_the_declared_filters_and_says_so(): void
    {
        $this->stock('active');
        $this->stock('retired', ['inactive' => 1]);

        $opened = $this->opened($this->asked([]));

        $this->assertSame(['active'], $this->codes($opened->page));
        $this->assertSame([false], array_values($opened->asked->filters));
    }

    /**
     * *Fails if* the default is put back key by key: a link written after somebody ticked "show
     * inactive" and narrowed by group carries the group alone, and opens hiding what they asked
     * to see.
     */
    public function test_a_default_is_not_put_back_on_an_address_that_asked_for_something_else(): void
    {
        $this->stock('active', ['category_id' => 1]);
        $this->stock('retired', ['category_id' => 1, 'inactive' => 1]);
        $this->stock('elsewhere', ['category_id' => 2]);

        $opened = $this->opened($this->asked(['filter' => ['group' => 1]]));

        $this->assertEqualsCanonicalizing(['active', 'retired'], $this->codes($opened->page));
        $this->assertSame(['group' => '1'], $opened->asked->filters);
    }

    private function opened(TableState $asked): InitialPage
    {
        return app(TableRepository::class)->initialPage(
            $this->table()->defaultFilters(['inactive' => false])->definition(),
            $asked,
        );
    }
}
