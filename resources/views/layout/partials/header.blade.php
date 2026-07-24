@php
$onload = $onload ?? null;
$lang = language();

$is_legacy_page = $is_legacy_page ?? false;
@endphp
<!DOCTYPE html>
<html dir="{{ $lang->getDir() }}" lang="{{ str_replace('_', '-', $lang->getLocale()) }}">
    <head>
        <meta charset="{{ $lang->getEncoding() }}">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="is-legacy-page" content="{{ intval($is_legacy_page) }}">
        <title>@yield('title', $title ?? 'taqbook - ERP')</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <link href="{{ url("/themes/default/images/favicon.ico") }}" rel="icon" type="image/x-icon">

        <!-- Scripts -->
        @vite([
            'resources/css/plugins.css',
            'resources/css/fa.css',
            'resources/js/plugins.js',
            'resources/js/app.js',
            'resources/js/fa.js'
        ])

        @if($is_legacy_page)
            @php
            $js_files = $js_files ?? array_merge(
                (!empty($GLOBALS['js_static']) && is_array($GLOBALS['js_static']) ? $GLOBALS['js_static'] : []),
                (!empty($GLOBALS['js_userlib']) && is_array($GLOBALS['js_userlib']) ? $GLOBALS['js_userlib'] : [])
            );
            $css_files = $css_files ?? (
                !empty($GLOBALS['css_files']) && is_array($GLOBALS['css_files']) ? $GLOBALS['css_files'] : []
            );
            @endphp

            @foreach($css_files as $css_file)
            <link rel="stylesheet" href="{{ url_from_path($css_file) }}">
            @endforeach

            @foreach($js_files as $js_file)
            <script src="{{ legacy_cached_js_url($js_file) }}"></script>
            @endforeach
        @endif

        @yield('head')
    </head>
    <body {!! empty($onload) ? '' : "onload=\"$onload\"" !!}>