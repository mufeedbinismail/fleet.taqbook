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
        @stack('scripts')
    </body>
</html>