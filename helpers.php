<?php

use App\Foundation\Framework\Contract\Enum\HasLabelContract;
use App\Foundation\Framework\Support\Arr;

/**
 * The address of a FrontAccounting script, with its parameters as a query string.
 *
 * A script is addressed by path and asks for its arguments by name, so parameters have to arrive
 * as a query rather than as further path segments — which is the whole of the difference between
 * this and Laravel's url(). The two are named apart so that which one is meant is written down at
 * every call, and so that neither depends on the other having been loaded.
 *
 * @param  array<string, scalar>  $parameters
 */
function legacy_url(string $path, array $parameters = [], ?bool $secure = null): string
{
    $url = app(\Illuminate\Contracts\Routing\UrlGenerator::class)->to($path, [], $secure);

    if ($parameters === []) {
        return $url;
    }

    return $url.(str_contains($url, '?') ? '&' : '?').http_build_query($parameters);
}

/**
 * Get / set the specified configuration value.
 *
 * If an array is passed as the key, we will assume you want to set an array of values.
 *
 * @param  array|string|null  $key
 * @param  mixed  $default
 * @return mixed|\App\Foundation\Shared\Setting\GlobalSetting
 */
function settings($key = null, $default = Arr::NOT_SET)
{
    if (is_null($key)) {
        return app('settings');
    }

    if (is_array($key)) {
        return app('settings')->set($key);
    }

    return app('settings')->get($key, $default);
}

/**
 * Get / set the specified configuration value.
 *
 * If an array is passed as the key, we will assume you want to set an array of values.
 *
 * @param  array|string|null  $key
 * @param  mixed  $default
 * @return mixed|\App\Foundation\Shared\Setting\UserSetting
 */
function user_settings($key = null, $default = Arr::NOT_SET)
{
    if (is_null($key)) {
        return app('user.settings');
    }

    if (is_array($key)) {
        return app('user.settings')->set($key);
    }

    return app('user.settings')->get($key, $default);
}

/**
 * Get the language service bound to the current locale.
 *
 * @return \App\Foundation\Framework\Support\Language
 */
function language()
{
    static $language;
    return $language ??= new \App\Foundation\Framework\Support\Language;
}

/**
 * Get the labels for an enum.
 *
 * @param class-string<UnitEnum> $enum The enum to get the labels for.
 * @return array The labels for the enum.
 */
function get_labels_from_enum(string $enum): array
{
    if (is_subclass_of($enum, HasLabelContract::class)) {
        return call_user_func([$enum, 'labels']);
    } else if (is_subclass_of($enum, UnitEnum::class)) {
        return array_column(call_user_func([$enum, 'cases']), 'name');
    }
    
    throw new \InvalidArgumentException("Invalid enum class: $enum");
}