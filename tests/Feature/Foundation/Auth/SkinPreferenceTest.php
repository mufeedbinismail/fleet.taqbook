<?php

namespace Tests\Feature\Foundation\Auth;

use App\Foundation\Auth\Model\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * These cases read the served document rather than the row: every screen here is a fresh page load,
 * so the document is where a user finds out whether their choice outlived the page they made it on.
 */
class SkinPreferenceTest extends TestCase
{
    use DatabaseTransactions;

    private function actor(): User
    {
        return User::first();
    }

    /**
     * Which screen is immaterial: the skin is worn by the document, not by anything a page puts
     * inside it.
     */
    private function aScreen(): string
    {
        return '/area/trade.sale';
    }

    public function test_a_skin_somebody_chose_is_worn_by_the_next_screen_they_open(): void
    {
        $actor = $this->actor();

        $this->actingAs($actor)->post('/preference/skin', ['skin' => 'dark']);

        $this->actingAs($actor->fresh())
            ->get($this->aScreen())
            ->assertSee('data-skin="dark"', false);
    }

    public function test_choosing_the_plain_skin_takes_the_mark_back_off(): void
    {
        $actor = $this->actor();

        $this->actingAs($actor)->post('/preference/skin', ['skin' => 'dark']);
        $this->actingAs($actor->fresh())->post('/preference/skin', ['skin' => 'light']);

        $this->actingAs($actor->fresh())
            ->get($this->aScreen())
            ->assertDontSee('data-skin', false);
    }

    public function test_somebody_who_has_never_chosen_is_drawn_plain(): void
    {
        $this->actingAs($this->actor())
            ->get($this->aScreen())
            ->assertDontSee('data-skin', false);
    }
}
