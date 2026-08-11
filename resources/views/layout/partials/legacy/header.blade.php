@include('layout.partials.head-core')

        <meta name="is-legacy-page" content="1">

        {{-- Blocking rather than deferred: inline scripts further down the page need these globals. --}}
        @vite([
            'resources/css/fa.css',
            'resources/js/plugins.js',
            'resources/js/app.js',
            'resources/js/fa.js'
        ])

        @foreach($css_files as $css_file)
        <link rel="stylesheet" href="{{ url_from_path($css_file) }}">
        @endforeach

        @foreach($js_files as $js_file)
        <script src="{{ legacy_cached_js_url($js_file) }}"></script>
        @endforeach
    </head>
    <body {!! empty($onload) ? '' : "onload=\"$onload\"" !!}>
