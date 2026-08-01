<?php

function url_from_path($path)
{
    if (strpos($path, PATH_TO_ROOT) === 0) {
        $path = substr($path, strlen(PATH_TO_ROOT));
    } elseif (($resolved_path = realpath($path)) !== false) {
        $root_path = realpath(PATH_TO_ROOT);
        if (strpos($resolved_path, $root_path) === 0) {
            $path = substr($resolved_path, strlen($root_path));
        }
    }

    return legacy_url($path);
}

function root_path(string $path = ''): string
{
    return $path ? PATH_TO_ROOT . '/' . ltrim($path, '/') : PATH_TO_ROOT;
}

function session_invalidate()
{
    if ($_SESSION instanceof \App\Legacy\Session\Store) {
        $_SESSION->invalidate();
    } else {
        $_SESSION = [];
    }
}

/**
 * Get the current session instance
 *
 * @return \App\Legacy\Session\Store|\Illuminate\Session\SessionManager|\Illuminate\Session\Store
 */
function session($key = null, $default = null)
{
    if (is_null($key)) {
        return app(\App\Legacy\Session\Store::class);
    }

    if (is_array($key)) {
        return app(\App\Legacy\Session\Store::class)->put($key);
    }

    return app(\App\Legacy\Session\Store::class)->get($key, $default);
}

/**
 * Get the URL to a legacy cached JavaScript file.
 * 
 * This function checks if a cached version of the JavaScript file exists,
 * and if not, it creates one. It then returns the URL to the cached file.
 *
 * @param string $file
 * @return string
 */
function legacy_cached_js_url($file)
{
    global $SysPrefs, $js_path;

    $cached_name = basename($file);
    $fpath = user_js_cache().'/'.$cached_name;

    $actual_file_path = $js_path.$file;
    if (strpos($file, PATH_TO_ROOT) !== false) {
        $actual_file_path = $file;
    }

    // compress also static files
    if (!file_exists($fpath) || $SysPrefs->go_debug)
        cache_js_file($fpath, file_get_contents($actual_file_path));

    return url_from_path($fpath);
}