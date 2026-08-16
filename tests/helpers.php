<?php

/**
 * The address of a file in the test tree.
 *
 * Laravel's own path helpers resolve through the container, which a data provider cannot count on:
 * providers are static and PHPUnit calls them while it is collecting tests, before any application
 * has been booted. This one is arithmetic on its own location, so it answers at every point in a
 * run rather than only after a boot somebody has to remember to have arranged.
 */
function test_path(string $path = ''): string
{
    return __DIR__.($path !== '' ? DIRECTORY_SEPARATOR.$path : '');
}
