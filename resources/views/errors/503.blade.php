@extends('errors.layout', [
    'code' => 503,
    'icon' => 'icon-wrench',
    'accentText' => 'text-warning-accent',
    'accentBg' => 'bg-warning-accent/10',
    'heading' => __("taqbook is down for maintenance"),
    'message' => __("We are updating the system and will be back shortly. Please try again in a few minutes."),
])
