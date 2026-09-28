<?php

return [
    /*
     | --------------------------------------------------------------------------------------
     | The handpicked baseline every page is given. Not everything a route or translation
     | could offer, only what any page might need; anything more specific is registered by
     | whoever needs it, instead of growing this list. i18n keys are plain Laravel translation
     | keys (group.key) — nothing here owns the strings themselves.
     | --------------------------------------------------------------------------------------
     */
    'client_data' => [
        'routes' => [
            'login',
            'preference.skin',
        ],

        'i18n' => [
            'framework.http.expired',
            'framework.http.offline',
            'framework.http.failed',

            // A select can be built on any page, by a screen that never mentioned one — a legacy
            // combo rebinding itself included — so its own strings belong to the baseline rather
            // than to whichever screens happen to know they have one today.
            'component.select.empty',
            'component.select.searching',
            'component.select.failed',
            'component.select.retry',
            'component.select.more',
            'component.select.tooShort',
            'component.select.remove',
            'component.select.clear',

            // The two words a switch falls back on are drawn by the switch itself, wherever one
            // is built and whatever the screen around it knows — a chrome that carries one on
            // every page included — so they belong to the baseline rather than to whoever
            // remembered to send them.
            'component.toggle.on',
            'component.toggle.off',
        ],
    ],
];
