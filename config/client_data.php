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
    ],

    'i18n' => [
        'foundation.http.expired',
        'foundation.http.offline',
        'foundation.http.failed',
    ],
];
