@include('layout.partials.foot-core')

        {{-- Ahead of the runtime entries: modules run in tag order, and a page module's App.boot
             call only queues if it runs before the runtime drains the queue — arriving after would
             register a component into a DOM Alpine has already walked without it. --}}
        @stack('scripts')

        @vite([
            'resources/js/plugins.js',
            'resources/js/app.js'
        ])
    </body>
</html>
