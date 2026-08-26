<?php

/*
 | ------------------------------------------------------------------------------------------
 | The handpicked baseline every page is given. Not everything a route or translation could
 | offer, only what any page might need; anything more specific is registered by whoever needs
 | it, instead of growing this file. i18n keys are plain Laravel translation keys (group.key) —
 | nothing here owns the strings themselves.
 | ------------------------------------------------------------------------------------------
*/
return [
    'routes' => [
        'login',
        'preference.skin',
    ],

    'i18n' => [
        'foundation.http.expired',
        'foundation.http.offline',
        'foundation.http.failed',

        // A select can be built on any page, by a screen that never mentioned one — a legacy combo
        // rebinding itself included — so its own strings belong to the baseline rather than to
        // whichever screens happen to know they have one today.
        'foundation.select.empty',
        'foundation.select.searching',
        'foundation.select.failed',
        'foundation.select.retry',
        'foundation.select.more',
        'foundation.select.tooShort',
        'foundation.select.remove',
        'foundation.select.clear',

        // The two words a switch falls back on are drawn by the switch itself, wherever one is
        // built and whatever the screen around it knows — a chrome that carries one on every page
        // included — so they belong to the baseline rather than to whoever remembered to send them.
        'foundation.toggle.on',
        'foundation.toggle.off',
    ],
];
