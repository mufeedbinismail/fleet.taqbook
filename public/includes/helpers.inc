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