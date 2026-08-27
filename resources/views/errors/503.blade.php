@extends('errors.layout', [
    'code' => 503,
    'icon' => 'icon-wrench',
    'accentText' => 'text-banner-warning-txt',
    'accentBg' => 'bg-banner-warning-bg',
    'heading' => __("taqbook is down for maintenance"),
    'message' => __("We are updating the system and will be back shortly. Please try again in a few minutes."),
])
