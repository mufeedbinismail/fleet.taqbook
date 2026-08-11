@include('layout.partials.foot-core')

        @vite([
            'resources/js/plugins.js',
            'resources/js/app.js'
        ])

        @stack('scripts')
    </body>
</html>
