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

];
