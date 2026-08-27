@extends('errors.layout', [
    'code' => 403,
    'icon' => 'icon-block',
    'accentText' => 'text-banner-error-txt',
    'accentBg' => 'bg-banner-error-bg',
    'heading' => __("You do not have access to this page"),
    'message' => __("The security settings on your account do not permit you to use this function. Ask your system administrator to grant you the matching permission."),
])
