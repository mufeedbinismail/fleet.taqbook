<?php

use App\Foundation\Contract\Enum\HasLabelContract;
use App\Foundation\Support\Arr;

/** 
 * Joins array elements into a string based on their truthy values.
 *
 * This function takes an associative array where keys are class names and values are boolean conditions.
 * It filters out the elements with falsy values and joins the remaining keys into a single string separated by the specified glue.
 *
 * @param array $elements An associative array of class names and their corresponding boolean conditions.
 * @param string $glue The string to use as a separator between class names. Default is a single space.
 * @return string A string of class names joined by the specified glue.
 */
function conditional_join(array $elements, string $glue = ' '): string
{
    $elements = array_filter($elements);

    foreach ($elements as $class => $condition) {
        if (is_int($class)) {
            $elements[] = $condition;
        } else if ($condition) {
            $elements[] = $class;
        }
    }

    return implode($glue, array_unique($elements));
}

/**
 * Get / set the specified configuration value.
 *
 * If an array is passed as the key, we will assume you want to set an array of values.
 *
 * @param  array|string|null  $key
 * @param  mixed  $default
 * @return mixed|\App\Foundation\Setting\SettingRepository
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
 * @return mixed|\App\Foundation\Setting\UserSettingRepository
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
 * @return \App\Foundation\Service\LanguageService
 */
function language()
{
    static $language;
    return $language ??= new \App\Foundation\Service\LanguageService;
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