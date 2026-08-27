@extends('errors.layout', [
    'code' => 500,
    'icon' => 'icon-warning',
    'accentText' => 'text-banner-error-txt',
    'accentBg' => 'bg-banner-error-bg',
    'heading' => __("Something went wrong on our side"),
    'message' => __("The error has been logged. Nothing you entered was saved, so it is safe to try again — and to report it if it keeps happening."),
])
