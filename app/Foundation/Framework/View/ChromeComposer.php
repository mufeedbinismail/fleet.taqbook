<?php

namespace App\Foundation\Framework\View;

use Illuminate\View\View;

/**
 * The state the application chrome is drawn from.
 *
 * The chrome opens in one view and closes in another, so both are composed rather than one: a value
 * worked out where the chrome opens is out of scope by the time the closing half draws, and working
 * it out again there is how the two halves come to disagree about the same page.
 *
 * Whether a menu is drawn arrives as view data, so it is read here rather than decided: a caller
 * that says nothing gets the full chrome.
 */
class ChromeComposer
{
    public function compose(View $view): void
    {
        $data = $view->getData();

        $noMenu = (bool) ($data['no_menu'] ?? false);
        $isIndex = (bool) ($data['is_index'] ?? false);

        // Read from the legacy session rather than the guard, and deliberately: what this gates is
        // hotkey help, which only a FrontAccounting-booted request produces. A logged-in user is
        // not the question.
        $shouldShowFooter = ! $noMenu && ! $isIndex && session('wa_current_user') !== null;

        $view->with([
            'no_menu' => $noMenu,
            'is_index' => $isIndex,
            'shouldShowFooter' => $shouldShowFooter,
            'help' => $this->help($shouldShowFooter),
        ]);
    }

    /**
     * The hotkey hints for this page, empty when there is nowhere to show them.
     */
    private function help(bool $shouldShowFooter): string
    {
        if (! $shouldShowFooter || ! isset($GLOBALS['Pagehelp'])) {
            return '';
        }

        return implode('; ', $GLOBALS['Pagehelp']);
    }
}
