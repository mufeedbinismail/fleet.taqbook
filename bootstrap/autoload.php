<?php

require __DIR__.'/../helpers.php';

/*
|--------------------------------------------------------------------------
| Define IS_LEGACY_ROUTE
|--------------------------------------------------------------------------
|
| This is used to determine if the application is serving a legacy route.
| Any request that were previously routed using file & directory based routing
| system in apache will have the IS_LEGACY_ROUTE environment variable set
| to true from the .htaccess file (i.e. the RewriteRule). such routes are
| considered as features from FrontAccounting.
|
| The .htaccess sets IS_LEGACY_ROUTE on the *original* request (a legacy
| .php file or a real directory), which is then internally rewritten to
| entry.php. Apache renames env vars set on a redirecting pass with a
| REDIRECT_ prefix, so by the time this file runs the value lives in
| REDIRECT_IS_LEGACY_ROUTE. We check both so the flag survives the rewrite.
*/
define('IS_LEGACY_ROUTE',
    getenv('IS_LEGACY_ROUTE') === 'true' ||
    getenv('REDIRECT_IS_LEGACY_ROUTE') === 'true'
);

if (IS_LEGACY_ROUTE) {
    require __DIR__.'/frontaccounting.php';
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader
| for our application. We just need to utilize it! We'll require it
| into the script here so that we do not have to worry about the
| loading of any of our classes manually. It's great to relax.
|
*/
require __DIR__.'/../vendor/autoload.php';
