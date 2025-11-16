@php
// Set defaults from passed variables or session/globals
$no_menu = $no_menu ?? false;
$is_index = $is_index ?? false;

// Calculate derived values
$shouldShowFooter = !$no_menu && !$is_index && null !== session('wa_current_user');

// Get help text for footer
$help = '';
if ($shouldShowFooter && isset($GLOBALS['Pagehelp'])) {
    $help = implode('; ', $GLOBALS['Pagehelp']);
}
@endphp
            </section>
        </main>
        @if ($shouldShowFooter)
        <footer class="main-footer">
            <div>{{ request()->getHost() . " | " . Today() . " | " . Now() }}</div>
            @if ($help)
            <div><span id="hotkeyshelp">{{ $help }}</span></div>
            @endif
        </footer>
        @endif
    </section>
</section>

