<?php

/**
 * Generate a url for the application.
 * 
 * This replaces the laravel's url utility
 * and build a query param instead of using route params
 *
 * @param  string|null  $path
 * @param  mixed  $parameters
 * @param  bool|null  $secure
 * @return string|\Illuminate\Contracts\Routing\UrlGenerator
 */
function url($path = null, $parameters = [], $secure = null)
{
    if ($path === null) {
        return app(\Illuminate\Contracts\Routing\UrlGenerator::class);
    }

    $url = app(\Illuminate\Contracts\Routing\UrlGenerator::class)->to($path, [], $secure);

    if (!empty($parameters)) {
        $queryString = http_build_query($parameters);
        $url .= (strpos($url, '?') === false ? '?' : '&') . $queryString;
    }
    
    return $url;
}

function url_from_path($path)
{
    if (($resolved_path = realpath($path)) !== false) {
        $root_path = realpath(PATH_TO_ROOT);
        if (strpos($resolved_path, $root_path) === 0) {
            $path = substr($resolved_path, strlen($root_path));
        }
    }

    return url($path);
}

function root_path(string $path = ''): string
{
    return $path ? PATH_TO_ROOT . '/' . ltrim($path, '/') : PATH_TO_ROOT;
}

function session_invalidate()
{
    if ($_SESSION instanceof \App\Session\Store) {
        $_SESSION->invalidate();
    } else {
        $_SESSION = [];
    }
}

/**
 * Get the current session instance
 *
 * @return \App\Session\Store|\Illuminate\Session\SessionManager|\Illuminate\Session\Store
 */
function session($key = null, $default = null)
{
    if (is_null($key)) {
        return app(\App\Session\Store::class);
    }

    if (is_array($key)) {
        return app(\App\Session\Store::class)->put($key);
    }

    return app(\App\Session\Store::class)->get($key, $default);
}