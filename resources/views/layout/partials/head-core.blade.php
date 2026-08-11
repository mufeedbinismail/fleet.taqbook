@php
$lang = language();
$skin = auth()->check() ? user_settings()->skin()->value : null;
@endphp
{{-- Deliberately ends inside an open <head>. --}}
<!DOCTYPE html>
<html
    dir="{{ $lang->getDir() }}"
    lang="{{ str_replace('_', '-', $lang->getLocale()) }}"
    @if ($skin !== null) data-skin="{{ $skin }}" @endif
>
    <head>
        <script src="{{ asset('js/bootstrap.js').'?v='.filemtime(public_path('js/bootstrap.js')) }}"></script>

        <meta charset="{{ $lang->getEncoding() }}">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        {{-- Trailing slash is load-bearing: the browser's URL constructor treats a base without
             one as ending in a "file" (dropping the last path segment, e.g. a subdirectory like
             /php81/taqbook) rather than a directory to resolve relative paths against. --}}
        <meta name="base-url" content="{{ rtrim(url('/'), '/') }}/">
        <title>@yield('title', $title ?? 'taqbook - ERP')</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <link href="{{ url("/themes/default/images/favicon.ico") }}" rel="icon" type="image/x-icon">

        @vite([
            'resources/css/plugins.css',
            'resources/css/index.css'
        ])
