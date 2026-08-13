<?php

namespace Tests\Feature\Foundation\Component\Select;

use App\Foundation\Component\Select\Contract\NarrowsOptions;
use App\Foundation\Component\Select\Contract\SelectDefinition;
use App\Foundation\Component\Select\Http\Controller\SelectController;
use App\Foundation\Component\Select\Intent\OptionSearchIntent;
use App\Foundation\Component\Select\ValueObject\Option;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * What an option endpoint answers, read as the control reads it.
 *
 * The definitions behind these routes stand over rows this layer owns, because what is under test
 * is the journey from a query string to an answer the browser can act on. Which rows any one domain
 * is entitled to offer is that domain's own promise and is tested where the domain is.
 */
class SelectOptionTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * The term every case searches by. The table it reads is the live one, so a case says which
     * rows are its own rather than assuming it has the table to itself.
     */
    private const MINE = 'ZZ';

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('test/staff/options', SelectController::class)
            ->defaults('select', StaffSelect::class);

        Route::get('test/role-staff/options', SelectController::class)
            ->defaults('select', RoleStaffSelect::class);
    }

    public function test_a_search_offers_only_the_rows_that_match_it(): void
    {
        $this->given(['ZZ Alan Reed' => 1, 'ZZ Bea Novak' => 1]);

        $labels = $this->getJson('test/staff/options?search=ZZ+Bea')->json('data.*.label');

        $this->assertSame(['ZZ Bea Novak'], $labels);
    }

    /**
     * The flag infinite scroll reads. Wrong in one direction the list stops before the rows run
     * out; wrong in the other it goes on asking for pages that will never come.
     */
    public function test_the_list_says_whether_there_is_another_page_to_ask_for(): void
    {
        $this->given(['ZZ Alan Reed' => 1, 'ZZ Bea Novak' => 1, 'ZZ Cal Ortiz' => 2]);

        $this->assertTrue($this->search(['per_page' => 2])->json('meta.has_more'));
        $this->assertFalse($this->search(['per_page' => 2, 'page' => 2])->json('meta.has_more'));
    }

    public function test_a_held_value_the_filters_still_allow_comes_back_as_held(): void
    {
        $ids = $this->given(['ZZ Alan Reed' => 1, 'ZZ Bea Novak' => 1]);

        $response = $this->search(
            ['role_id' => 1, 'selected' => [$ids['ZZ Bea Novak']]],
            'test/role-staff/options',
        );

        $this->assertSame(['ZZ Bea Novak'], $response->json('selected.*.label'));
    }

    /**
     * The one that stops a row belonging to the previous filter — somebody holding another role —
     * reaching the ledger because nobody noticed it was still in the box.
     */
    public function test_a_held_value_the_filters_rule_out_does_not(): void
    {
        $ids = $this->given(['ZZ Alan Reed' => 1, 'ZZ Cal Ortiz' => 2]);

        $response = $this->search(
            ['role_id' => 1, 'selected' => [$ids['ZZ Cal Ortiz']]],
            'test/role-staff/options',
        );

        $this->assertSame([], $response->json('selected'));
    }

    /**
     * Why the verdict is its own key. Answering it out of the returned page would discard a good
     * choice the moment the list grew long enough to be worth searching.
     */
    public function test_a_held_value_is_answered_whichever_page_it_falls_on(): void
    {
        $ids = $this->given(['ZZ Alan Reed' => 1, 'ZZ Cal Ortiz' => 2]);

        $response = $this->search(['per_page' => 1, 'selected' => [$ids['ZZ Cal Ortiz']]]);

        $this->assertSame(['ZZ Alan Reed'], $response->json('data.*.label'));
        $this->assertSame(['ZZ Cal Ortiz'], $response->json('selected.*.label'));
    }

    /**
     * The parameter a screen never declared is the one a client adds for itself.
     */
    public function test_a_narrowing_nobody_declared_never_reaches_the_query(): void
    {
        $this->given(['ZZ Alan Reed' => 1, 'ZZ Cal Ortiz' => 2]);

        $labels = $this->search(['role_id' => 2])->json('data.*.label');

        $this->assertSame(['ZZ Alan Reed', 'ZZ Cal Ortiz'], $labels);
    }

    public function test_a_declared_narrowing_of_the_wrong_kind_is_refused(): void
    {
        $this->search(['role_id' => 'lots'], 'test/role-staff/options')
            ->assertStatus(422);
    }

    /**
     * The list is a round trip somebody is waiting on. A client naming its own page size could
     * otherwise ask for the whole table on every keystroke.
     */
    public function test_a_page_larger_than_the_cap_is_refused(): void
    {
        $this->search(['per_page' => OptionSearchIntent::MAX_PER_PAGE + 1])->assertStatus(422);
    }

    public function test_the_same_held_value_twice_is_answered_once(): void
    {
        $ids = $this->given(['ZZ Bea Novak' => 1]);

        $response = $this->search(['selected' => [$ids['ZZ Bea Novak'], $ids['ZZ Bea Novak']]]);

        $this->assertSame(['ZZ Bea Novak'], $response->json('selected.*.label'));
    }

    /**
     * What the screen reading the chosen option gets to work with — a language to write to somebody
     * in, a currency to convert into. A control that dropped these would leave the screen to fetch
     * each one over again by the value it is holding.
     */
    public function test_a_row_carries_its_own_extra_columns_to_the_client(): void
    {
        $ids = $this->given(['ZZ Alan Reed' => 1], language: 'fr_FR');

        $response = $this->search(['selected' => [$ids['ZZ Alan Reed']]]);

        $this->assertSame([['language' => 'fr_FR']], $response->json('data.*.data'));
        $this->assertSame([['language' => 'fr_FR']], $response->json('selected.*.data'));
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
    private function search(array $query, string $path = 'test/staff/options'): TestResponse
    {
        return $this->getJson($path.'?'.http_build_query(['search' => self::MINE, ...$query]));
    }
}

/**
 * A list of people, unnarrowed — the shape most of the estate's call sites have.
 */
class StaffSelect implements SelectDefinition
{
    public function query(): QueryBuilder
    {
        return DB::table('users')
            ->select('id', 'real_name', 'language')
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

    public function toOption(object $row): Option
    {
        return Option::of($row->id, $row->real_name, data: ['language' => $row->language]);
    }
}

/**
 * The same people, narrowed by a role the screen declares — and so the only one of the two for
 * which `role_id` is anything but noise on the query string.
 */
class RoleStaffSelect extends StaffSelect implements NarrowsOptions
{
    public function applyFilters(EloquentBuilder|QueryBuilder $query, OptionSearchIntent $intent): void
    {
        if ($role = $intent->filter('role_id')) {
            $query->where('role_id', $role);
        }
    }

    public function filterRules(): array
    {
        return ['role_id' => ['required', 'integer']];
    }
}
