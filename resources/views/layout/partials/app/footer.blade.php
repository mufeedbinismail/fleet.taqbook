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

