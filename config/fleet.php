<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Messages
    |--------------------------------------------------------------------------
    |
    | Where on a deployment's address a message is posted, how long a signed
    | message stays valid, and how long a delivery may wait on the deployment.
    | The path is the product's route, so it is the same on every server.
    |
    */

    'message' => [
        'path' => '/api/fleet/messages',
        'lifetime_minutes' => (int) env('FLEET_MESSAGE_LIFETIME_MINUTES', 5),
        'connect_timeout_seconds' => (int) env('FLEET_MESSAGE_CONNECT_TIMEOUT_SECONDS', 5),
        'timeout_seconds' => (int) env('FLEET_MESSAGE_TIMEOUT_SECONDS', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Identity
    |--------------------------------------------------------------------------
    |
    | How long an issued identity stays installable. It carries a live tenant
    | secret, so one that is never installed should not stay usable for long.
    |
    */

    'identity' => [
        'lifetime_days' => (int) env('FLEET_IDENTITY_LIFETIME_DAYS', 1),
    ],

];
