@extends('errors.layout', [
    'code' => 404,
    'icon' => 'icon-search',
    'accentText' => 'text-banner-brand-txt',
    'accentBg' => 'bg-banner-brand-bg',
    'heading' => __("This page does not exist"),
    'message' => __("The address you followed may be out of date, or the record it pointed at has since been removed."),
])
