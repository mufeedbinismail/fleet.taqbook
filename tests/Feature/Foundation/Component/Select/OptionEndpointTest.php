<?php

namespace Tests\Feature\Foundation\Component\Select;

use App\Foundation\Component\Select\Contract\NarrowsOptions;
use App\Foundation\Component\Select\Contract\SelectDefinition;
use App\Foundation\Component\Select\Service\OptionService;
use App\Foundation\Component\Select\ValueObject\SelectState;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * What a screen's list of choices is handed, read the way the control reads it.
 *
 * The definitions behind these routes stand over rows this file seeds, because what is under test
 * is the journey from a query string to the rows a clerk is offered — which rows a domain is
 * entitled to offer is that domain's own promise and is tested where the domain is.
 */
class OptionEndpointTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Route::optionList('test/staff', StaffSelect::class);
        Route::optionList('test/role-staff', RoleStaffSelect::class);
    }

    public function test_a_clerk_searching_is_offered_only_the_rows_that_match(): void
    {
        $this->given(['ZZ Alan Reed' => 1, 'ZZ Bea Novak' => 1, 'ZZ Cal Ortiz' => 2]);

        $this->assertSame(['ZZ Bea Novak'], $this->offered(['search' => 'ZZ Bea'])->json('data.*.label'));
    }

    /**
     * Five to a page and ten that match: the first page says there is another, the second — full
     * to the brim — says there is not. Wrong one way the list stops before the rows run out and
     * looks complete; wrong the other it asks forever for a page that never comes, and a last page
     * that happens to be full is where the second mistake hides.
     */
    public function test_a_long_list_pages_and_the_last_page_says_so(): void
    {
        $this->given(array_fill_keys(array_map(fn ($letter) => "ZZ {$letter}", range('A', 'J')), 1));

        $first = $this->offered(['per_page' => 5]);
        $second = $this->offered(['per_page' => 5, 'page' => 2]);

        $this->assertSame(['ZZ A', 'ZZ B', 'ZZ C', 'ZZ D', 'ZZ E'], $first->json('data.*.label'));
        $this->assertTrue($first->json('meta.has_more'));
        $this->assertSame(['ZZ F', 'ZZ G', 'ZZ H', 'ZZ I', 'ZZ J'], $second->json('data.*.label'));
        $this->assertFalse($second->json('meta.has_more'));
    }

    /**
     * The department changed and the employee box beside it was still holding whoever was picked
     * under the old one. The verdict on what is held is its own key: answered whichever page the
     * row falls on, withheld once the department rules it out, and carrying what the screen reads
     * off the chosen row.
     */
    public function test_a_held_employee_is_kept_only_while_the_department_still_allows_them(): void
    {
        $ids = $this->given(['ZZ Alan Reed' => 1, 'ZZ Bea Novak' => 1, 'ZZ Cal Ortiz' => 2], language: 'fr_FR');

        $response = $this->offered(
            ['role_id' => 1, 'per_page' => 1, 'selected' => [$ids['ZZ Bea Novak'], $ids['ZZ Cal Ortiz']]],
            'test/role-staff/options',
        );

        $this->assertSame(['ZZ Alan Reed'], $response->json('data.*.label'));
        $this->assertSame(['ZZ Bea Novak'], $response->json('selected.*.label'));
        $this->assertSame([['language' => 'fr_FR']], $response->json('selected.*.data'));
    }

    /**
     * A parameter no screen declared is one a client added for itself.
     */
    public function test_a_narrowing_the_screen_never_declared_is_ignored(): void
    {
        $this->given(['ZZ Alan Reed' => 1, 'ZZ Cal Ortiz' => 2]);

        $this->assertSame(
            ['ZZ Alan Reed', 'ZZ Cal Ortiz'],
            $this->offered(['role_id' => 2])->json('data.*.label'),
        );
    }

    /**
     * A set small enough is listed in full so the screen never fetches; one row past that it is
     * offered as the address it is searched at, narrowed the same way on both sides of the switch.
     * Cut to fit instead, the rows past the cut vanish from a filter with nothing to say they did.
     */
    public function test_a_set_too_large_to_list_is_offered_by_address_instead(): void
    {
        $this->given(['ZZ Alan Reed' => 1, 'ZZ Bea Novak' => 1, 'ZZ Cal Ortiz' => 2]);

        config(['component.select.inline_up_to' => 2]);

        $listed = app(OptionService::class)->channel(new RoleStaffSelect, ['role_id' => 1]);

        $this->assertFalse($listed->isFetchedFromSource());
        $this->assertSame(['ZZ Alan Reed', 'ZZ Bea Novak'], array_column($listed->options()->toArray(), 'label'));

        $fetched = app(OptionService::class)->channel(new StaffSelect);

        $this->assertTrue($fetched->isFetchedFromSource());
        $this->assertTrue($fetched->options()->isEmpty());
        $this->assertSame('test.staff.options', $fetched->source()->url);
    }

    /**
     * @param  array<string, int>  $staff  name to the role they hold
     * @return array<string, int> name to id
     */
    private function given(array $staff, string $language = 'en_GB'): array
    {
        $ids = [];

        foreach ($staff as $name => $role) {
            $ids[$name] = DB::table('users')->insertGetId([
                'user_id' => strtolower(str_replace(' ', '.', $name)),
                'real_name' => $name,
                'password' => '',
                'role_id' => $role,
                'language' => $language,
            ]);
        }

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function offered(array $query, string $path = 'test/staff/options'): TestResponse
    {
        return $this->getJson($path.'?'.http_build_query(['search' => 'ZZ', ...$query]));
    }
}

/**
 * The people this file seeded and nobody else, unnarrowed.
 */
class StaffSelect implements SelectDefinition
{
    public function query(): QueryBuilder
    {
        return DB::table('users')
            ->select(['id as value', 'real_name as label', 'language as data_language'])
            ->where('real_name', 'like', 'ZZ %')
            ->orderBy('real_name');
    }

    public function searchColumns(): array
    {
        return ['real_name'];
    }

    public function valueColumn(): string
    {
        return 'id';
    }

    public static function routeName(): string
    {
        return 'test.staff.options';
    }
}

/**
 * The same people, narrowed by a role the screen declares.
 */
class RoleStaffSelect extends StaffSelect implements NarrowsOptions
{
    public static function routeName(): string
    {
        return 'test.role-staff.options';
    }

    public function applyFilters(EloquentBuilder|QueryBuilder $query, SelectState $state): void
    {
        if ($role = $state->filter('role_id')) {
            $query->where('role_id', $role);
        }
    }

    public function filterRules(): array
    {
        return ['role_id' => ['required', 'integer']];
    }
}
