<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Root Public Key
    |--------------------------------------------------------------------------
    |
    | The public half of the fleet's root key, as <algorithm>:<base64url>.
    | Every statement from the fleet is believed only under a delegation this
    | key signed. Not a secret, and written once at install.
    |
    */

    'root_key' => env('FLEET_ROOT_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Operational Key
    |--------------------------------------------------------------------------
    |
    | The private key the fleet signs with, as <algorithm>:<base64url>. A
    | secret, set only on the fleet, and never by hand: it changes only
    | together with the delegation naming it.
    |
    */

    'operational_key' => env('FLEET_OPERATIONAL_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Delegation
    |--------------------------------------------------------------------------
    |
    | The root's signed note vouching for the operational key above until a
    | date, set only on the fleet. It means nothing beside any other key, so
    | the two change together.
    |
    */

    'delegation' => env('FLEET_DELEGATION'),

];
