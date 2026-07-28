@extends('errors.layout', [
    'code' => 404,
    'icon' => 'icon-search',
    'accentText' => 'text-primary-accent',
    'accentBg' => 'bg-primary-accent/10',
    'heading' => __("This page does not exist"),
    'message' => __("The address you followed may be out of date, or the record it pointed at has since been removed."),
])
