        @if($is_legacy_page ?? false)
            @php
                $js_lib = $js_lib ?? (
                    !empty($GLOBALS['js_lib']) && is_array($GLOBALS['js_lib']) ? $GLOBALS['js_lib'] : []
                );
            @endphp
            <script type="text/javascript">
                @foreach($js_lib as $js){!! $js . "\n" !!}@endforeach
            </script>
        @endif

        {{-- The one point where anything server-side reaches the browser. Everything registered
             over this request lands here at once, so nothing downstream has to know who put what
             there or in what order. A classic script, so it has run by the time any module does;
             ahead of @stack, so pushed scripts can read it too. --}}
        <script>window.App.data = {!! \App\Foundation\Facade\ClientData::render() !!};</script>
        
        @if(! ($is_legacy_page ?? false))
            @vite([
                'resources/js/plugins.js',
                'resources/js/app.js'
            ])
        @endif
        
        @stack('scripts')
    </body>
</html>