<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Area index
    |--------------------------------------------------------------------------
    |
    | Whether opening an area lands on its index — every entry the area holds,
    | laid out as cards — rather than on the area's own dashboard.
    |
    | One answer for the installation. It decides which of two addresses an
    | area's name leads to, and nothing about what any area holds.
    |
    */

    'area_index' => (bool) env('NAVIGATION_AREA_INDEX', true),

];
