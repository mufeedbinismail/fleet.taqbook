<?php

namespace Tests\Unit\Legacy\Navigation;

use App\Legacy\Navigation\Enum\Query;
use App\Legacy\Navigation\ValueObject\LegacyPageTarget;
use Illuminate\Http\Request;
use Tests\TestCase;

class LegacyPageTargetTest extends TestCase
{
    // ------------------------------------------------------------------ value

    public function test_it_claims_a_script_in_the_mode_it_names(): void
    {
        $target = LegacyPageTarget::at('sales/sales_order_entry.php', ['NewOrder' => 'Yes']);

        $this->assertNotNull($target->matches($this->requestTo('sales/sales_order_entry.php?NewOrder=Yes')));
    }

    public function test_it_lets_go_of_the_same_script_in_another_mode(): void
    {
        $target = LegacyPageTarget::at('sales/sales_order_entry.php', ['NewOrder' => 'Yes']);

        $this->assertNull($target->matches($this->requestTo('sales/sales_order_entry.php?NewQuotation=Yes')));
    }

    /**
     * A filter or a page number arriving on the query string is not a change of location.
     */
    public function test_it_ignores_parameters_it_does_not_name(): void
    {
        $target = LegacyPageTarget::at('sales/inquiry/customer_inquiry.php');

        $this->assertNotNull($target->matches($this->requestTo('sales/inquiry/customer_inquiry.php?page=3')));
    }

    // --------------------------------------------------------------- wildcard

    public function test_a_wildcard_takes_the_parameter_whatever_it_holds(): void
    {
        $target = LegacyPageTarget::at('sales/sales_order_entry.php', ['ModifyOrderNumber' => Query::ANY]);

        $this->assertNotNull($target->matches($this->requestTo('sales/sales_order_entry.php?ModifyOrderNumber=42')));
        $this->assertNotNull($target->matches($this->requestTo('sales/sales_order_entry.php?ModifyOrderNumber=7')));
    }

    public function test_a_wildcard_still_wants_the_parameter_there(): void
    {
        $target = LegacyPageTarget::at('sales/sales_order_entry.php', ['ModifyOrderNumber' => Query::ANY]);

        $this->assertNull($target->matches($this->requestTo('sales/sales_order_entry.php')));
    }

    /**
     * Both targets claim the request, so the ranking is the whole answer: naming the value is a
     * stronger claim than requiring the parameter to be present.
     */
    public function test_naming_a_value_outranks_merely_requiring_the_parameter(): void
    {
        $request = $this->requestTo('admin/tags.php?type=account');

        $pinned = LegacyPageTarget::at('admin/tags.php', ['type' => 'account']);
        $wildcard = LegacyPageTarget::at('admin/tags.php', ['type' => Query::ANY]);

        $this->assertGreaterThan($wildcard->matches($request), $pinned->matches($request));
    }

    public function test_more_parameters_still_outrank_fewer(): void
    {
        $request = $this->requestTo('sales/sales_order_entry.php?Marketplace=Yes&ModifyOrderNumber=42');

        $plain = LegacyPageTarget::at('sales/sales_order_entry.php', ['ModifyOrderNumber' => Query::ANY]);
        $marketplace = LegacyPageTarget::at('sales/sales_order_entry.php', [
            'Marketplace' => 'Yes',
            'ModifyOrderNumber' => Query::ANY,
        ]);

        $this->assertGreaterThan($plain->matches($request), $marketplace->matches($request));
    }

    // -------------------------------------------------------------- addresses

    public function test_it_builds_an_address_when_every_parameter_is_pinned(): void
    {
        $target = LegacyPageTarget::at('sales/sales_order_entry.php', ['NewOrder' => 'Yes']);

        $this->assertStringContainsString('sales/sales_order_entry.php?NewOrder=Yes', $target->url());
    }

    /**
     * There is no address for "edit an order" — only for editing a particular one, which is the
     * record's to supply and not the declaration's.
     */
    public function test_a_wildcard_has_no_address_to_build(): void
    {
        $target = LegacyPageTarget::at('sales/sales_order_entry.php', ['ModifyOrderNumber' => Query::ANY]);

        $this->assertNull($target->url());
    }

    public function test_the_empty_path_is_the_index_script(): void
    {
        $this->assertSame(
            LegacyPageTarget::at('index.php')->signature(),
            LegacyPageTarget::at('')->signature(),
        );
    }

    // ------------------------------------------------------------- signatures

    public function test_two_declarations_of_one_address_share_a_signature(): void
    {
        $this->assertSame(
            LegacyPageTarget::at('gl/gl_bank.php', ['NewPayment' => 'Yes', 'type' => '1'])->signature(),
            LegacyPageTarget::at('gl/gl_bank.php', ['type' => '1', 'NewPayment' => 'Yes'])->signature(),
        );
    }

    public function test_a_wildcard_is_not_the_same_address_as_a_pinned_value(): void
    {
        $this->assertNotSame(
            LegacyPageTarget::at('admin/tags.php', ['type' => Query::ANY])->signature(),
            LegacyPageTarget::at('admin/tags.php', ['type' => 'account'])->signature(),
        );
    }

    /**
     * The wildcard is written into a signature as a character a value could also hold, so the two
     * have to stay distinguishable once encoded.
     */
    public function test_a_parameter_holding_a_star_is_not_a_wildcard(): void
    {
        $this->assertNotSame(
            LegacyPageTarget::at('admin/tags.php', ['type' => Query::ANY])->signature(),
            LegacyPageTarget::at('admin/tags.php', ['type' => '*'])->signature(),
        );
    }

    private function requestTo(string $uri): Request
    {
        return Request::create('/'.$uri);
    }
}
