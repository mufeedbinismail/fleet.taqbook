<?php

namespace Tests\Feature\Foundation\Auth;

use App\Foundation\Auth\Model\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
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

    /**
     * Read off the tag that states it rather than out of the page at large: the same words appear
     * in the script below, where they are being talked about rather than worn.
     */
    private function skinOf(TestResponse $response): ?string
    {
        preg_match('/<html\b[^>]*>/', $response->getContent(), $tag);
        preg_match('/\bdata-skin="([^"]*)"/', $tag[0] ?? '', $skin);

        return $skin[1] ?? null;
    }

    public function test_a_skin_somebody_chose_is_worn_by_the_next_screen_they_open(): void
    {
        $actor = $this->actor();

        $this->actingAs($actor)->post('/preference/skin', ['skin' => 'dark']);

        $this->assertSame('dark', $this->skinOf($this->actingAs($actor->fresh())->get($this->aScreen())));
    }

    /**
     * Saying light and saying nothing part company here: one is a skin, the other is a question
     * passed to the browser, and a document that drew them alike could not tell them apart again.
     */
    public function test_choosing_the_plain_skin_is_said_out_loud_rather_than_left_unsaid(): void
    {
        $actor = $this->actor();

        $this->actingAs($actor)->post('/preference/skin', ['skin' => 'dark']);
        $this->actingAs($actor->fresh())->post('/preference/skin', ['skin' => 'light']);

        $this->assertSame('light', $this->skinOf($this->actingAs($actor->fresh())->get($this->aScreen())));
    }

    public function test_somebody_who_has_never_chosen_leaves_it_to_their_browser(): void
    {
        $actor = $this->actor();

        User::query()->whereKey($actor->id)->update(['skin' => '']);

        $this->assertSame('', $this->skinOf($this->actingAs($actor->fresh())->get($this->aScreen())));
    }

    /**
     * Nobody is signed in to hold an opinion, and the silence is what the document owes: it is the
     * one signal the login screen has that the choice kept on the device is the choice to wear.
     */
    public function test_a_screen_nobody_is_signed_in_to_states_no_skin_at_all(): void
    {
        $this->assertNull($this->skinOf($this->get('/login')));
    }
}
