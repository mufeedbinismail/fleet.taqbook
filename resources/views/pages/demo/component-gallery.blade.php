@extends('layout.app')
@section('title', $title)

{{--
    The reference screen for the theme: every colour below arrives through a role token, and every
    control below is the component the application actually draws, invoked the way a screen invokes
    it. Nothing here is a copy of anything — a copy agrees with the app on the day it is written and
    then drifts without saying so, which is the failure this page exists to make impossible.

    The two exceptions are stated where they occur: the palette card, whose swatches are the fixed
    palette itself and so cannot be said in role terms, and the sample data, which is invented
    because there is no record to show.
--}}

@php
    /*
        The palette, named rung by rung so the card below can draw it without repeating itself. The
        values are never written here — each swatch is painted with `var(--c-…)` and prints the hex
        the browser resolved, so a rung added or moved in the stylesheet shows up here by itself.

        `ink` names which rungs need the light label rather than the dark one. Both are palette
        references rather than hexes for the same reason everything else on the page is: the palette
        does not move between themes, so one pair is legible on these swatches in either.
    */
    $families = [
        [
            'label' => 'light — the 60%',
            'stacks' => [['name' => 'light', 'rungs' => ['50', '100', '200', '300', '400', '500', '600', '700']]],
            'ink' => ['700'],
        ],
        [
            'label' => 'dark — the 30%',
            'stacks' => [['name' => 'dark', 'rungs' => ['50', '100', '200', '300', '400', '500', '600', '700']]],
            'ink' => ['50', '100', '200', '300', '400', '500', '600', '700'],
        ],
        [
            'label' => 'neutral — hueless',
            'stacks' => [['name' => 'neutral', 'rungs' => ['0', '100', '200', '300', '400', '900', '1000']]],
            'ink' => ['900', '1000'],
        ],
        [
            'label' => 'brand · status · info · accents — 800 is the dark wash, added for the dark theme',
            'stacks' => array_map(
                fn ($name) => ['name' => $name, 'rungs' => ['50', '100', '400', '500', '600', '800']],
                ['brand', 'success', 'warning', 'danger', 'info', 'accent-1', 'accent-2'],
            ),
            'ink' => ['500', '600', '800'],
        ],
    ];

    // Sample rows for the two selects. Shaped the way a screen's own rows arrive — value, label,
    // description, group, disabled — so the control is exercised through the same flattening a real
    // caller's data goes through rather than a hand-made shortcut.
    $documents = [
        ['value' => 'so', 'label' => 'Sales orders', 'description' => 'Open and pending', 'group' => 'Sales'],
        ['value' => 'si', 'label' => 'Sales invoices', 'description' => 'Posted to the ledger', 'group' => 'Sales'],
        ['value' => 'cn', 'label' => 'Credit notes', 'description' => 'Against a posted invoice', 'group' => 'Sales'],
        ['value' => 'pp', 'label' => 'Prepayments', 'description' => 'Not available yet', 'group' => 'Sales', 'disabled' => true],
        ['value' => 'po', 'label' => 'Purchase orders', 'group' => 'Purchases'],
        ['value' => 'pi', 'label' => 'Supplier invoices', 'group' => 'Purchases'],
        ['value' => 'gr', 'label' => 'Goods received notes', 'group' => 'Purchases'],
    ];

    $customers = [
        ['value' => '1', 'label' => 'Acme Ltd'],
        ['value' => '2', 'label' => 'Globex'],
        ['value' => '3', 'label' => 'Initech'],
        ['value' => '4', 'label' => 'Northwind Traders'],
        ['value' => '5', 'label' => 'Contoso', 'description' => 'Inactive'],
    ];

    // Frames for the real backtrace partial, in the shape the backtrace partial groups them: a run of
    // the app's own, then a folded run from vendor.
    $backtrace = [
        [
            'vendor' => false,
            'frames' => [
                ['index' => 0, 'dir' => 'public/includes', 'file' => 'errors.inc', 'line' => 103, 'call' => 'get_backtrace'],
                ['index' => 1, 'dir' => 'public/sales', 'file' => 'sales_order_entry.php', 'line' => 448, 'call' => 'display_error'],
            ],
        ],
        [
            'vendor' => true,
            'frames' => [
                ['index' => 2, 'dir' => 'vendor/laravel/framework/…', 'file' => 'ControllerDispatcher.php', 'line' => 43, 'call' => 'Controller->callAction'],
                ['index' => 3, 'dir' => 'vendor/laravel/framework/…', 'file' => 'Route.php', 'line' => 260, 'call' => 'Route->runController'],
                ['index' => 4, 'dir' => 'vendor/laravel/framework/…', 'file' => 'Router.php', 'line' => 806, 'call' => 'Router->runRoute'],
            ],
        ],
    ];

    /*
        The days the calendars below are built around, counted off the month somebody is looking at:
        every state a cell can be in needs a day to stand on, and fixed dates would put most of them
        in a month nobody pages to.
    */
    $month = now()->startOfMonth();
    $on = fn (int $day) => $month->copy()->addDays($day - 1)->format('Y-m-d');

    $calendar = [
        'chosen' => $on(12),
        'from' => $on(8),
        'to' => $on(19),
        'floor' => $on(4),
        'ceiling' => $on(26),
        // The last falls inside the period below, which is the only way to see a refusal read
        // against covered ground.
        'refused' => [$on(10), $on(11), $on(17)],
        'marked' => [$on(15), $on(22)],
        'moment' => $month->copy()->addDays(11)->setTime(14, 30)->format('Y-m-d H:i:s'),
    ];
@endphp

@section('head')
{{-- The message boxes, the tab strip and the table styles this page documents are keyed to
     FrontAccounting's own markup, and drawn by a stylesheet no page receives unless it asks. --}}
@vite(['resources/css/fa.css'])

{{-- Ahead of the stylesheet's first paint, so reloading the page in the dark theme does not flash
     the light one. A private window can throw on any storage access at all, so the read is guarded
     rather than the value checked. --}}
<script>
(function () {
    try {
        if (localStorage.getItem('taqbook-preview-theme') === 'dark') {
            document.documentElement.setAttribute('data-skin', 'dark');
        }
    } catch (e) {}
})();
</script>
@endsection

@section('content')
<div class="mx-auto max-w-7xl p-4 md:p-6" x-data>

    {{-- The theme switch belongs to this page rather than to the chrome — nothing else in the
         application offers one yet — so it sits in the page's own header beside the title, where a
         screen puts its own controls, instead of floating over the content. --}}
    <div class="mb-6 flex flex-wrap items-center gap-4 rounded-xl border border-card-border bg-card-bg p-4 shadow-sm">
        <div class="me-auto min-w-0">
            <h1 class="m-0 text-lg font-semibold text-card-title-txt">Component gallery</h1>
            <p class="mt-1 text-sm text-card-txt">
                Served by the application, through the application's layout. The shell, sidebar,
                header, breadcrumbs and footer around this card are the real chrome, and every
                control below is the real component.
            </p>
        </div>

        <x-button variant="outline" icon="style" data-skin-toggle><span data-skin-toggle-label></span></x-button>
    </div>

    <h2 class="mb-1 mt-8 text-base font-semibold text-card-title-txt">
        The palette — read live from the stylesheet, and fixed in every theme
    </h2>
    <p class="mb-3 max-w-3xl text-sm text-card-txt">
        Every swatch is <code>var(--c-…)</code> resolved by the browser, and every hex is printed by
        reading it back. <b>This card does not change when the theme is flipped, and that is the
        point:</b> the palette is fixed, and a theme moves only the role tokens that point into it —
        which is why <code>light-50</code> is still the lightest surface and <code>dark-700</code>
        still the deepest in both. Everything below this card <em>does</em> flip, because everything
        below this card names a role.
    </p>

    <div class="rounded-xl border border-card-border bg-card-bg p-4 shadow-sm">
        @foreach ($families as $family)
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-card-txt {{ $loop->first ? '' : 'mt-4' }}">
                {{ $family['label'] }}
            </p>

            <div class="flex flex-wrap items-start gap-3">
                @foreach ($family['stacks'] as $stack)
                    <div class="w-[126px]">
                        <div class="overflow-hidden rounded-lg shadow">
                            @foreach ($stack['rungs'] as $rung)
                                @php $token = '--c-'.$stack['name'].'-'.$rung; @endphp
                                <div class="flex h-7 items-center justify-between px-2 font-mono text-[9.5px] font-semibold"
                                     style="background: var({{ $token }});
                                            color: var({{ in_array($rung, $family['ink'], true) ? '--c-light-50' : '--c-dark-300' }})">
                                    <span>{{ $rung }}</span>
                                    <span class="opacity-80" data-rung="{{ $token }}"></span>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-2 text-sm font-semibold text-card-title-txt">{{ $stack['name'] }}</div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    {{--
        Every section below is one component type, and shows that component's variations side by
        side within it. The shape is the same throughout — a label naming the variation, the
        variations themselves in a row, and a note where a rung or a caveat is worth saying — so a
        reader learns the layout once and then only reads the differences.

        Nothing on this page wears a class that only exists on this page. Where a control is bare it
        is because `reset.css` already draws it, and anything added on top would be this page's
        opinion being read back as the theme's.
    --}}

    <h2 class="mb-1 mt-8 text-base font-semibold text-card-title-txt">Button</h2>
    <p class="mb-3 max-w-3xl text-sm text-card-txt">
        <code>&lt;x-button&gt;</code> and its three variants, each in the states a screen puts it in.
        <code>.ghost</code> is the separate recipe for an action carrying no chrome of its own.
    </p>

    <div class="rounded-xl border border-card-border bg-card-bg p-4 shadow-sm">
        <div class="flex flex-col gap-3">
            @foreach ([['primary', null, 'button-ok', 'Save changes'], ['outline', 'outline', 'data', 'Clone'], ['danger', 'danger', 'trash', 'Delete role']] as [$name, $variant, $icon, $label])
                <div class="flex flex-wrap items-center gap-3">
                    <span class="w-32 flex-none text-xs font-semibold uppercase tracking-wide text-card-txt">{{ $name }}</span>
                    <x-button :variant="$variant ?? 'primary'" :icon="$icon">{{ $label }}</x-button>
                    <x-button :variant="$variant ?? 'primary'">{{ $label }}</x-button>
                    <x-button :variant="$variant ?? 'primary'" :icon="$icon" disabled>Disabled</x-button>
                    <span class="text-xs text-card-txt">with icon · without · disabled</span>
                </div>
            @endforeach

            <div class="flex flex-wrap items-center gap-3">
                <span class="w-32 flex-none text-xs font-semibold uppercase tracking-wide text-card-txt">ghost</span>
                <button type="button" class="ghost text-link-txt">Expand all</button>
                <button type="button" class="ghost text-link-txt">Collapse all</button>
                <button type="button" class="ghost text-button-danger-txt">Clear</button>
                <span class="text-xs text-card-txt">a recipe, not a variant — link colour and danger colour</span>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span class="w-32 flex-none text-xs font-semibold uppercase tracking-wide text-card-txt">bare</span>
                <button type="button">Search</button>
                <button type="button" disabled>Search</button>
                <span class="text-xs text-card-txt">no class at all — what <code>reset.css</code> draws, and what a legacy screen shows</span>
            </div>
        </div>
    </div>

    <h2 class="mb-1 mt-8 text-base font-semibold text-card-title-txt">Badge</h2>
    <p class="mb-3 max-w-3xl text-sm text-card-txt">
        <code>&lt;x-badge&gt;</code> is one shape only — a 12&thinsp;% tint of its own colour with the
        label in that colour. It takes a variant name, a CSS colour, or a class of the caller's own,
        so a colour nobody thought of still tints to match.
    </p>

    <div class="rounded-xl border border-card-border bg-card-bg p-4 shadow-sm">
        <div class="flex flex-col gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <span class="w-32 flex-none text-xs font-semibold uppercase tracking-wide text-card-txt">variants</span>
                <x-badge color="success">Settled</x-badge>
                <x-badge color="warning">Own access</x-badge>
                <x-badge color="danger">Overdue</x-badge>
                <x-badge color="info">Draft</x-badge>
                <x-badge color="neutral">Closed</x-badge>
                <x-badge>Default</x-badge>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span class="w-32 flex-none text-xs font-semibold uppercase tracking-wide text-card-txt">own colour</span>
                <x-badge color="var(--c-accent-1-500)">accent-1</x-badge>
                <x-badge color="var(--c-accent-2-500)">accent-2</x-badge>
                <span class="text-xs text-card-txt">a rung the component was never told about, tinting from the colour it was given</span>
            </div>
        </div>
    </div>

    <h2 class="mb-1 mt-8 text-base font-semibold text-card-title-txt">Count pill</h2>
    <p class="mb-3 max-w-3xl text-sm text-card-txt">
        <code>.badge</code> in <code>forms.css</code> — a separate thing from
        <code>&lt;x-badge&gt;</code> despite the name: no variants, no tint, one fill and tabular
        figures so a column of them does not jitter.
    </p>

    <div class="rounded-xl border border-card-border bg-card-bg p-4 shadow-sm">
        <div class="flex flex-wrap items-center gap-3">
            <span class="w-32 flex-none text-xs font-semibold uppercase tracking-wide text-card-txt">counts</span>
            <span class="badge">6</span>
            <span class="badge">128</span>
            <span class="badge">0</span>
        </div>
    </div>

    <h2 class="mb-1 mt-8 text-base font-semibold text-card-title-txt">Banner</h2>
    <p class="mb-3 max-w-3xl text-sm text-card-txt">
        Not a component: a recipe of role-token utilities, written where a screen needs one. Shown
        stacked because a banner is always full width — the variations are the four accents and the
        two shapes, a bar across the top of a card and a boxed notice inside one.
    </p>

    <div class="rounded-xl border border-card-border bg-card-bg p-4 shadow-sm">
        <div class="flex flex-col gap-3">
            {{-- Written out whole: Tailwind reads templates as text, so a class assembled from a
                 variable is one it never sees and never emits. --}}
            @foreach ([
                ['success', 'circle-check', 'border-banner-success-border bg-banner-success-bg text-banner-success-txt', 'Role saved. Permissions applied to 12 users.'],
                ['error', 'warning', 'border-banner-error-border bg-banner-error-bg text-banner-error-txt', 'Could not save the role. The name is already in use.'],
                ['warning', 'clock', 'border-banner-warning-border bg-banner-warning-bg text-banner-warning-txt', 'Your session expires in 5 minutes.'],
                ['brand', 'info', 'border-banner-brand-bg bg-banner-brand-bg text-banner-brand-txt', 'This screen is being ported. Some actions still open the legacy page.'],
            ] as [$tone, $icon, $look, $line])
                <div class="flex flex-wrap items-start gap-3">
                    <span class="w-32 flex-none pt-3 text-xs font-semibold uppercase tracking-wide text-card-txt">{{ $tone }}</span>
                    <div class="min-w-0 grow rounded-lg border px-4 py-3 text-sm {{ $look }}">
                        <div class="flex items-center gap-2">
                            <span class="icon icon-{{ $icon }}"></span>
                            <span class="grow">{{ $line }}</span>
                            <button type="button" class="icon icon-close shrink-0 cursor-pointer border-0 bg-transparent"></button>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="flex flex-wrap items-center gap-3">
                <span class="w-32 flex-none text-xs font-semibold uppercase tracking-wide text-card-txt">as a pill</span>
                <span class="inline-flex items-center gap-1 rounded-full bg-banner-warning-bg px-2 py-0.5 text-xs font-semibold text-banner-warning-txt">
                    <span class="icon icon-lock text-xs"></span> owns access
                </span>
                <span class="inline-flex items-center gap-1 rounded-full bg-banner-brand-bg px-2 py-0.5 text-xs font-semibold text-banner-brand-txt">404 accent</span>
                <span class="inline-flex items-center gap-1 rounded-full bg-banner-error-bg px-2 py-0.5 text-xs font-semibold text-banner-error-txt">500 accent</span>
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-banner-success-bg text-2xl text-banner-success-txt">
                    <span class="icon icon-circle-check"></span>
                </span>
                <span class="text-xs text-card-txt">the same fills at the sizes the error pages use them</span>
            </div>
        </div>
    </div>

    <h2 class="mb-1 mt-8 text-base font-semibold text-card-title-txt">Message</h2>
    <p class="mb-3 max-w-3xl text-sm text-card-txt">
        FrontAccounting's own <code>err_msg</code> / <code>warn_msg</code> / <code>note_msg</code>
        boxes. The legacy stylesheet draws them from its own classes; the tokens under it are the
        ones below, so the two cannot drift.
    </p>

    <div class="rounded-xl border border-card-border bg-card-bg p-4 shadow-sm">
        <div class="flex flex-col gap-3">
            {{-- Each variant's classes are written out whole. Tailwind reads the templates as text
                 to decide what to generate, so a class assembled from a variable — `bg-message-{$tone}-bg`
                 — is a class it never sees and never emits: the box renders with no fill at all,
                 and nothing anywhere reports it. --}}
            @foreach ([
                ['error', 'border-message-error-border bg-message-error-bg text-message-error-txt', 'You must enter at least one non empty item line.'],
                ['warning', 'border-message-warning-border bg-message-warning-bg text-message-warning-txt', 'The reference is already used by another transaction.'],
                ['note', 'border-message-note-border bg-message-note-bg text-message-note-txt', 'The sales order has been entered.'],
            ] as [$tone, $look, $line])
                <div class="flex flex-wrap items-start gap-3">
                    <span class="w-32 flex-none pt-2 text-xs font-semibold uppercase tracking-wide text-card-txt">{{ $tone }}</span>
                    <div class="min-w-0 grow rounded border-s-4 px-4 py-2 text-sm {{ $look }}">{{ $line }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <h2 class="mb-1 mt-8 text-base font-semibold text-card-title-txt">Field</h2>
    <p class="mb-3 max-w-3xl text-sm text-card-txt">
        Bare elements, every one of them. The edge, the padding, the radius and the colours are what
        <code>reset.css</code> draws before anybody has said anything — 30px tall, 4px of inline
        padding, a 2px radius — and that is what a control in this application is. Every shared
        component is built to match it, so anything this page put on top would make the components
        look wrong rather than the page.
    </p>

    <div class="rounded-xl border border-card-border bg-card-bg p-4 shadow-sm">
        <div class="flex flex-col gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <span class="w-32 flex-none text-xs font-semibold uppercase tracking-wide text-card-txt">text</span>
                <input value="FBN Trading LLC">
                <input placeholder="Search customers…">
                <input value="Locked" disabled>
                <span class="text-xs text-card-txt">value · placeholder · disabled</span>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span class="w-32 flex-none text-xs font-semibold uppercase tracking-wide text-card-txt">select</span>
                <select><option>Sales invoices</option><option>Credit notes</option></select>
                <select disabled><option>Sales invoices</option></select>
                <span class="text-xs text-card-txt">the caret is a role token — a colour cannot be escaped into the data URI that draws it</span>
            </div>

            <div class="flex flex-wrap items-start gap-3">
                <span class="w-32 flex-none pt-1 text-xs font-semibold uppercase tracking-wide text-card-txt">textarea</span>
                <textarea rows="2">Delivery held at the depot.</textarea>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span class="w-32 flex-none text-xs font-semibold uppercase tracking-wide text-card-txt">file</span>
                <input type="file">
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span class="w-32 flex-none text-xs font-semibold uppercase tracking-wide text-card-txt">check · radio</span>
                <label class="flex cursor-pointer items-center gap-2 text-sm text-card-txt"><input type="checkbox" checked> Checked</label>
                <label class="flex cursor-pointer items-center gap-2 text-sm text-card-txt"><input type="checkbox"> Off</label>
                <label class="flex cursor-pointer items-center gap-2 text-sm text-card-txt"><input type="checkbox" disabled> Disabled</label>
                <label class="flex cursor-pointer items-center gap-2 text-sm text-card-txt"><input type="radio" name="gallery-radio" checked> Radio</label>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span class="w-32 flex-none text-xs font-semibold uppercase tracking-wide text-card-txt">focus</span>
                <input value="Click me" data-gallery-focus>
                <span class="text-xs text-card-txt">click it — the ring is the one <code>reset.css</code> draws, not a copy of it</span>
            </div>

            {{-- Recorded rather than shown as a peer: `.field` is a second field look, 42px against
                 the reset's 30 and an 8px radius against its 2px, worn by two ported screens and by
                 no shared component. A reader who meets only one of the two reads it as the field,
                 and then everything built to the other looks broken — which is exactly what this
                 page did to the select until it was taken off. --}}
            <div class="flex flex-wrap items-center gap-3">
                <span class="w-32 flex-none text-xs font-semibold uppercase tracking-wide text-card-txt">.field</span>
                <input class="field w-[220px]" value="FBN Trading LLC">
                <span class="text-xs text-card-txt">
                    a second look, used by <code>login</code> and <code>security-role</code> only —
                    42px against the reset's 30, radius 8 against 2, and no component matches it
                </span>
            </div>
        </div>
    </div>


    <h2 class="mb-1 mt-8 text-base font-semibold text-card-title-txt">Toggle</h2>
    <p class="mb-3 max-w-3xl text-sm text-card-txt">
        <code>&lt;x-ui.toggle&gt;</code>, every variant in both states. Off is the same set in all of
        them — a switch shows its state by the difference between the two, so only on is a variant's
        to colour. The five wash rather than fill, and the wash is a rung of the accent's own family
        rather than the accent at an alpha: a role token is a <code>var()</code>, and an alpha
        applied to one is dropped at build time. The rung named second is the edge and the knob,
        which are one colour because they are one statement. Both sets are listed — flip the skin
        above and the second is what you are looking at.
    </p>

    <div class="rounded-xl border border-card-border bg-card-bg p-4 shadow-sm">
        <div class="flex flex-col gap-3">
            @foreach ([
                [null, 'neutral-200 → dark-300', 'dark-600 → dark-300'],
                ['primary', 'brand-50 · brand-600', 'brand-800 · brand-500'],
                ['success', 'success-50 · success-600', 'success-800 · success-500'],
                ['warning', 'warning-50 · warning-600', 'warning-800 · warning-500'],
                ['danger', 'danger-50 · danger-600', 'danger-800 · danger-500'],
                ['neutral', 'light-200 · dark-200', 'dark-700 · light-600'],
            ] as [$variant, $light, $dark])
                <div class="flex flex-wrap items-center gap-3">
                    <span class="w-32 flex-none text-xs font-semibold uppercase tracking-wide text-card-txt">{{ $variant ?? 'default' }}</span>
                    <x-ui.toggle :variant="$variant" :on="['label' => 'On']" :off="['label' => 'Off']" />
                    <x-ui.toggle :variant="$variant" :on="['label' => 'On']" :off="['label' => 'Off']" :checked="true" />
                    <span class="text-xs text-card-txt">
                        <code>{{ $light }}</code> &nbsp;·&nbsp; <code>{{ $dark }}</code>
                    </span>
                </div>
            @endforeach

            {{-- One set for every variant and both halves, so a disabled `danger` is not still a
                 red-edged pill. The pair on the right is off and on, and both arrive here. --}}
            <div class="flex flex-wrap items-center gap-3">
                <span class="w-32 flex-none text-xs font-semibold uppercase tracking-wide text-card-txt">disabled</span>
                <x-ui.toggle :on="['label' => 'On']" :off="['label' => 'Off']" disabled />
                <x-ui.toggle variant="primary" :on="['label' => 'On']" :off="['label' => 'Off']" :checked="true" disabled />
                <span class="text-xs text-card-txt">
                    <code>neutral-300 · neutral-400</code> &nbsp;·&nbsp; <code>dark-500 · dark-100</code>
                </span>
            </div>

            {{-- What the header wears. Icons rather than a second word, and the word still there
                 behind them for anything reading the control out. --}}
            <div class="flex flex-wrap items-center gap-3">
                <span class="w-32 flex-none text-xs font-semibold uppercase tracking-wide text-card-txt">with icon</span>
                <x-ui.toggle :on="['label' => 'Dark', 'icon' => 'moon']" :off="['label' => 'Light', 'icon' => 'sun']" />
                <x-ui.toggle :on="['label' => 'Dark', 'icon' => 'moon']" :off="['label' => 'Light', 'icon' => 'sun']" :checked="true" />
                <span class="text-xs text-card-txt">the skin switch in the header</span>
            </div>
        </div>
    </div>

    <h2 class="mb-1 mt-8 text-base font-semibold text-card-title-txt">Segment</h2>
    <p class="mb-3 max-w-3xl text-sm text-card-txt">
        A radio group drawn as one strip, which is a different control from the switch above: a row
        of choices where one is chosen, rather than one control with two states. Not a component —
        a recipe, worn by the role editor's status field.
    </p>

    <div class="rounded-xl border border-card-border bg-card-bg p-4 shadow-sm">
        <div class="flex flex-wrap items-center gap-3">
            <span class="w-32 flex-none text-xs font-semibold uppercase tracking-wide text-card-txt">two states</span>
            <div class="inline-flex overflow-hidden rounded-lg border border-field-border">
                @foreach (['Active', 'Inactive'] as $index => $label)
                    <label class="cursor-pointer">
                        <input class="peer sr-only" type="radio" name="gallery-segment" @checked($index === 0)>
                        <span class="block px-4 py-2 text-sm font-semibold text-card-txt transition peer-checked:bg-segment-selected-bg peer-checked:text-segment-selected-txt">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            <span class="text-xs text-card-txt"><code>segment-selected-bg</code> · <code>segment-selected-txt</code></span>
        </div>
    </div>

    <h2 class="mb-1 mt-8 text-base font-semibold text-card-title-txt">Select</h2>
    <p class="mb-3 max-w-3xl text-sm text-card-txt">
        <code>&lt;x-ui.select&gt;</code>, seeded from the server. Open one and type — the panel
        filters, the keyboard moves the highlight, and the underlying <code>&lt;select&gt;</code> is
        what holds the value. It is built to the bare control's geometry above, which is why it sits
        level with a plain <code>&lt;select&gt;</code> beside it on any screen.
    </p>

    <div class="rounded-xl border border-card-border bg-card-bg p-4 shadow-sm">
        <div class="flex flex-col gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <span class="w-32 flex-none text-xs font-semibold uppercase tracking-wide text-card-txt">single</span>
                <x-ui.select name="gallery-document" :options="$documents" selected="si" placeholder="Choose a document type…" />
                <span class="text-xs text-card-txt">searchable, grouped, with descriptions and one disabled row</span>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span class="w-32 flex-none text-xs font-semibold uppercase tracking-wide text-card-txt">multiple</span>
                <x-ui.select name="gallery-customers" :options="$customers" :selected="['1', '2']" multiple placeholder="Choose customers…" />
                <span class="text-xs text-card-txt">chips while shut; they move into the panel when open so the control cannot change height under it</span>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span class="w-32 flex-none text-xs font-semibold uppercase tracking-wide text-card-txt">not searchable</span>
                <x-ui.select name="gallery-plain" :options="$customers" :searchable="false" placeholder="Choose a customer…" />
                <span class="text-xs text-card-txt">no box to type in, so the field goes on showing what is held</span>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span class="w-32 flex-none text-xs font-semibold uppercase tracking-wide text-card-txt">disabled</span>
                <x-ui.select name="gallery-disabled" :options="$customers" selected="3" disabled />
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span class="w-32 flex-none text-xs font-semibold uppercase tracking-wide text-card-txt">beside a bare one</span>
                <x-ui.select name="gallery-level" :options="$customers" selected="1" />
                <select><option>Sales invoices</option></select>
                <input value="SO-1041">
                <span class="text-xs text-card-txt">all three are 30px — the check this page failed before the fields above were stripped</span>
            </div>
        </div>
    </div>

    <h2 class="mb-1 mt-8 text-base font-semibold text-card-title-txt">Date</h2>
    <p class="mb-3 max-w-3xl text-sm text-card-txt">
        <code>&lt;x-ui.date&gt;</code> and <code>&lt;x-ui.date-range&gt;</code>, drawn open so every
        colour a cell can carry is on the screen at once rather than one click at a time. These are
        the same panels a field opens — the fields above each one still work, and the month and year
        in the heading still page and zoom.
    </p>
    <p class="mb-3 max-w-3xl text-sm text-card-txt">
        Six states answer to the pointer and cannot be shown standing still: a cell under it, a day
        from the month either side under it, a day inside a period under it, the two paging controls
        under it, and a chosen cell it has not left yet. Hover any panel to check those.
    </p>

    <div class="rounded-xl border border-card-border bg-card-bg p-4 shadow-sm">
        <div class="flex flex-wrap items-start gap-6">
            <div class="flex flex-col gap-2">
                <span class="text-xs font-semibold uppercase tracking-wide text-card-txt">resting</span>
                <x-ui.date inline />
                <span class="max-w-64 text-xs text-card-txt">today's ink, the weekend pair, the days either side of the month, and the row of day names</span>
            </div>

            <div class="flex flex-col gap-2">
                <span class="text-xs font-semibold uppercase tracking-wide text-card-txt">chosen</span>
                <x-ui.date inline :value="$calendar['chosen']" />
                <span class="max-w-64 text-xs text-card-txt">the fill and the ink that says "this is the one", with today still marked beside it</span>
            </div>

            <div class="flex flex-col gap-2">
                <span class="text-xs font-semibold uppercase tracking-wide text-card-txt">refusing</span>
                <x-ui.date inline :min="$calendar['floor']" :max="$calendar['ceiling']" :disable="$calendar['refused']" />
                <span class="max-w-64 text-xs text-card-txt">a window and three days named individually — both refusals wear one ink</span>
            </div>

            <div class="flex flex-col gap-2">
                <span class="text-xs font-semibold uppercase tracking-wide text-card-txt">marked</span>
                <x-ui.date inline :highlight="$calendar['marked']" :value="$calendar['marked'][0]" />
                <span class="max-w-64 text-xs text-card-txt">underlined rather than filled, and the mark stands down under the day that was chosen</span>
            </div>

            <div class="flex flex-col gap-2">
                <span class="text-xs font-semibold uppercase tracking-wide text-card-txt">with a clock</span>
                <x-ui.date inline time today clearable :value="$calendar['moment']" />
                <span class="max-w-64 text-xs text-card-txt">the track, its thumb and the half of the day, over the panel's own two buttons</span>
            </div>

            <div class="flex flex-col gap-2">
                <span class="text-xs font-semibold uppercase tracking-wide text-card-txt">the pair, shut</span>
                <x-ui.date-range
                    legend="Period"
                    :from="$calendar['from']"
                    :to="$calendar['to']"
                />
                <span class="max-w-64 text-xs text-card-txt">the two ends joined into one box, the gap between them the only divider, and the word naming what they answer together</span>
            </div>

            <div class="flex flex-col gap-2">
                <span class="text-xs font-semibold uppercase tracking-wide text-card-txt">a period</span>
                {{-- The pair assembled here rather than drawn by <x-ui.date-range>, which joins its
                     two ends into one box — a box an open panel is inserted into, leaving two
                     calendars wedged between two fields. The directive is what makes two date
                     fields a period, and it asks only to be wrapped around them. --}}
                <div x-date-range class="flex flex-wrap items-start gap-4">
                    {{-- An end and its panel share a column because the panel is put on the page
                         beside the field it belongs to, and two ends in one row would leave each
                         calendar standing next to the other end's field. --}}
                    <div class="flex flex-col gap-2">
                        <x-ui.date inline :value="$calendar['from']" :disable="$calendar['refused']" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-ui.date inline :value="$calendar['to']" :disable="$calendar['refused']" />
                    </div>
                </div>
                <span class="max-w-[34rem] text-xs text-card-txt">both ends drawn open, each showing the whole span — covered ground, the chosen day at its own end, and a refused day read against that ground rather than against the panel</span>
            </div>
        </div>
    </div>

    <h2 class="mb-1 mt-8 text-base font-semibold text-card-title-txt">Dropdown</h2>
    <p class="mb-3 max-w-3xl text-sm text-card-txt">
        <code>x-dropdown</code>, the same directive the header's account menu is built from. The
        caret is injected by the directive and points the way the panel actually landed, which flip
        may change.
    </p>

    <div class="rounded-xl border border-card-border bg-card-bg p-4 shadow-sm">
        <div class="flex flex-col gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <span class="w-32 flex-none text-xs font-semibold uppercase tracking-wide text-card-txt">opens down</span>
                <div x-dropdown>
                    <button type="button" x-dropdown:trigger>
                        <span class="icon icon-circle-user text-2xl"></span>
                        <span>Administrator</span>
                    </button>

                    {{-- The panel is absolutely positioned and the content area scrolls, so one
                         left inside the card would be clipped. --}}
                    <template x-teleport="body">
                        <ul x-dropdown:panel x-transition x-cloak>
                            <li class="x-dropdown__header">
                                <span class="icon icon-circle-user"></span>
                                <span class="x-dropdown__header-name">Administrator</span>
                            </li>
                            <li><a class="x-dropdown__item" href="#">
                                <span class="icon icon-statistics"></span><span>Dashboard</span></a></li>
                            <li><a class="x-dropdown__item" href="#">
                                <span class="icon icon-prefs"></span><span>Preferences</span></a></li>
                            <li><a class="x-dropdown__item" href="#">
                                <span class="icon icon-security"></span><span>Change password</span></a></li>
                            <li><a class="x-dropdown__item text-button-danger-txt" href="#">
                                <span class="icon icon-logout"></span><span>Logout</span></a></li>
                        </ul>
                    </template>
                </div>
                <span class="text-xs text-card-txt">header row, items, and one item taking a colour of its own</span>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span class="w-32 flex-none text-xs font-semibold uppercase tracking-wide text-card-txt">prefers up</span>
                <div x-dropdown>
                    <button type="button" x-dropdown:trigger>
                        <span class="icon icon-settings"></span>
                        <span>Export</span>
                    </button>

                    <template x-teleport="body">
                        <ul x-dropdown:panel.top-start x-transition x-cloak>
                            <li><button type="button" class="x-dropdown__item w-full cursor-pointer border-0 bg-transparent text-start">Export as CSV</button></li>
                            <li><button type="button" class="x-dropdown__item w-full cursor-pointer border-0 bg-transparent text-start">Export as PDF</button></li>
                        </ul>
                    </template>
                </div>
                <span class="text-xs text-card-txt">asked for <code>.top-start</code>; it still flips if there is no room</span>
            </div>
        </div>
    </div>

    <h2 class="mb-1 mt-8 text-base font-semibold text-card-title-txt">Panel</h2>
    <p class="mb-3 max-w-3xl text-sm text-card-txt">
        The floating surface itself, held still so it can be looked at. It is the box a dropdown, a
        select list and the legacy date picker are all dropped into, which is why it owns only its
        own fill, edge and shadow — everything a row wears belongs to the row.
    </p>

    <div class="rounded-xl border border-card-border bg-card-bg p-4 shadow-sm">
        <div class="flex flex-wrap items-start gap-3">
            <span class="w-32 flex-none pt-2 text-xs font-semibold uppercase tracking-wide text-card-txt">surface</span>
            <div class="x-dropdown__panel !static w-64">
                <div class="x-dropdown__header"><span class="x-dropdown__header-name">Panel surface</span></div>
                <div class="x-dropdown__item">An item, and its hover fill</div>
                <div class="x-dropdown__item">Another item</div>
            </div>
        </div>
    </div>

    <h2 class="mb-1 mt-8 text-base font-semibold text-card-title-txt">Dialog</h2>
    <p class="mb-3 max-w-3xl text-sm text-card-txt">
        <code>$confirm()</code> builds one native <code>&lt;dialog&gt;</code> lazily and reuses it,
        resolving <code>true</code> on confirm and <code>false</code> on cancel, Escape or a click on
        the backdrop. There is one layout, so a caller passes options rather than markup.
    </p>

    <div class="rounded-xl border border-card-border bg-card-bg p-4 shadow-sm" x-data="{ outcome: null }">
        <div class="flex flex-col gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <span class="w-32 flex-none text-xs font-semibold uppercase tracking-wide text-card-txt">question</span>
                <x-button variant="outline"
                          @click="$confirm({
                              title: 'Post this sales order?',
                              text: 'The order will be written to the ledger and can no longer be edited.',
                              confirmText: 'Post it',
                          }).then((ok) => outcome = ok ? 'confirmed' : 'dismissed')">
                    Ask
                </x-button>
                <span class="text-xs text-card-txt">warning icon, brand confirm</span>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span class="w-32 flex-none text-xs font-semibold uppercase tracking-wide text-card-txt">dangerous</span>
                <x-button variant="danger" icon="trash"
                          @click="$confirm({
                              title: 'Delete this role?',
                              text: '12 users are assigned to it. They will lose these permissions immediately, and this cannot be undone.',
                              confirmText: 'Delete role',
                              danger: true,
                          }).then((ok) => outcome = ok ? 'confirmed' : 'dismissed')">
                    Ask
                </x-button>
                <span class="text-xs text-card-txt" x-show="outcome !== null" x-cloak>resolved <b x-text="outcome"></b></span>
            </div>
        </div>
    </div>

    <h2 class="mb-1 mt-8 text-base font-semibold text-card-title-txt">Collapse</h2>
    <p class="mb-3 max-w-3xl text-sm text-card-txt">
        <code>x-collapse</code>: any number of panels open at once, which is what the permission
        groups on the role editor need. The root carries <code>showAll()</code> and
        <code>hideAll()</code> for a control outside any one item. Which items open first is read out
        of the class the server already had to write.
    </p>

    <div class="overflow-hidden rounded-xl border border-card-border bg-card-bg shadow-sm" x-collapse>
        <div class="flex flex-wrap items-center gap-3 border-b border-card-divider px-5 py-3">
            <h3 class="me-auto m-0 text-sm font-semibold uppercase tracking-wide text-card-title-txt">Permissions</h3>
            <button type="button" @click="showAll()" class="ghost text-link-txt">Expand all</button>
            <button type="button" @click="hideAll()" class="ghost text-link-txt">Collapse all</button>
        </div>

        <div class="divide-y divide-card-divider">
            @foreach ([['sales', 'Sales', ['Sales orders', 'Sales invoices', 'Credit notes'], true], ['purchases', 'Purchases', ['Purchase orders', 'Supplier invoices'], false], ['setup', 'Setup', ['Company setup', 'Access setup', 'Display setup'], false]] as [$key, $group, $entries, $open])
                <section x-collapse:item="{{ $key }}" @class(['x-collapse__item--open' => $open])>
                    <div class="flex items-center gap-3 px-5 py-3 transition hover:bg-card-hover-bg">
                        <input type="checkbox" @checked($open)>
                        <button type="button" x-collapse:trigger
                                @class(['flex grow cursor-pointer items-center gap-2 border-0 bg-transparent p-0 text-start', 'x-collapse__trigger--open' => $open])>
                            <span class="text-sm font-semibold text-card-title-txt">{{ $group }}</span>
                            <span class="badge">{{ count($entries) }}</span>
                        </button>
                    </div>

                    {{-- Two elements rather than one: the panel shuts through display, which on the
                         grid itself would take the columns with it. --}}
                    <div @class(['x-collapse__panel--open' => $open]) x-collapse:panel>
                        <div class="grid gap-x-6 gap-y-1 bg-card-sunken-bg px-5 pb-4 pt-1 sm:grid-cols-2">
                            @foreach ($entries as $entry)
                                <label class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm text-card-txt transition hover:bg-card-row-hover-bg">
                                    <input type="checkbox" @checked($open)>
                                    <span>{{ $entry }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endforeach
        </div>
    </div>

    <h2 class="mb-1 mt-8 text-base font-semibold text-card-title-txt">Accordion</h2>
    <p class="mb-3 max-w-3xl text-sm text-card-txt">
        <code>x-accordion</code>: one panel open at a time within a root, and a second click on the
        open one shuts it. Roots nest, so a panel may hold another accordion without the inner one
        disturbing the outer.
    </p>

    <ul class="m-0 list-none overflow-hidden rounded-xl border border-card-border bg-card-bg p-0 shadow-sm" x-accordion>
        @foreach ([['company-setup', 'Company setup', ['Company Setup', 'Fiscal Years', 'Taxes'], true], ['maintenance', 'Maintenance', ['Void a Transaction', 'Backups', 'System Diagnostics'], false], ['access', 'Access', ['Users', 'Roles', 'Login Log'], false]] as [$key, $item, $entries, $open])
            <li x-accordion:item="{{ $key }}" @class(['border-t border-card-divider' => ! $loop->first, 'x-accordion__item--open' => $open])>
                <button type="button" x-accordion:trigger
                        @class(['flex w-full cursor-pointer items-center gap-2 border-0 bg-transparent px-5 py-3 text-start text-sm font-semibold text-card-title-txt transition hover:bg-card-hover-bg', 'x-accordion__trigger--open' => $open])>
                    <span class="grow">{{ $item }}</span>
                </button>

                <div @class(['x-accordion__panel--open' => $open]) x-accordion:panel>
                    <div class="bg-card-sunken-bg px-5 pb-4 pt-1">
                        @foreach ($entries as $entry)
                            <a class="block rounded-md px-2 py-1.5 text-sm text-card-entry-txt transition hover:bg-card-row-hover-bg" href="#">{{ $entry }}</a>
                        @endforeach
                    </div>
                </div>
            </li>
        @endforeach
    </ul>

    <h2 class="mb-1 mt-8 text-base font-semibold text-card-title-txt">Table</h2>
    <p class="mb-3 max-w-3xl text-sm text-card-txt">
        The row tokens a ported listing wears — the striping, and the four status fills a row can
        take. On a FrontAccounting screen the same tokens are worn by <code>tablestyle</code> and its
        row classes, which the legacy stylesheet supplies.
    </p>

    <div class="overflow-hidden rounded-xl border border-card-border bg-card-bg shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="bg-table-header-bg text-table-header-txt">
                        @foreach (['Row', 'Customer', 'Reference', 'Status', 'Amount'] as $heading)
                            <th class="border border-table-border px-3 py-2 text-start font-semibold {{ $loop->last ? 'text-end' : '' }}">{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="text-page-txt">
                    @foreach ([
                        ['odd', 'bg-table-row-odd-bg', 'Acme Ltd', 'SO-1041', 'info', 'Open', '4,210.00'],
                        ['even', 'bg-table-row-even-bg', 'Umbrella', 'SO-1042', 'info', 'Open', '2,110.00'],
                        ['settled', 'bg-row-settled-bg text-row-settled-txt', 'Globex', 'SO-1002', 'success', 'Settled', '1,980.00'],
                        ['inquiry', 'bg-row-inquiry-bg', 'Initech', 'SO-1033', 'warning', 'Inquiry', '760.00'],
                        ['overdue', 'bg-row-overdue-bg text-row-overdue-txt', 'Northwind', 'SO-0998', 'danger', 'Overdue', '12,400.00'],
                        ['inactive', 'bg-table-row-even-bg text-row-inactive-txt', 'Contoso', 'SO-1055', 'neutral', 'Closed', '3,050.00'],
                        ['label', 'bg-row-label-bg font-semibold', 'Total', '', null, '', '22,400.00'],
                    ] as [$name, $rowClass, $customer, $ref, $tone, $status, $amount])
                        <tr class="{{ $rowClass }}">
                            <td class="border border-table-border px-3 py-2 text-xs uppercase tracking-wide">{{ $name }}</td>
                            <td class="border border-table-border px-3 py-2">{{ $customer }}</td>
                            <td class="border border-table-border px-3 py-2">{{ $ref }}</td>
                            <td class="border border-table-border px-3 py-2">@if ($tone)<x-badge :color="$tone">{{ $status }}</x-badge>@endif</td>
                            <td class="border border-table-border px-3 py-2 text-end">{{ $amount }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <h2 class="mb-1 mt-8 text-base font-semibold text-card-title-txt">Toolbar and pagination</h2>
    <p class="mb-3 max-w-3xl text-sm text-card-txt">
        The strips a listing is framed by. Both are recipes rather than components — a card divider,
        the bare controls, and the button component.
    </p>

    <div class="overflow-hidden rounded-xl border border-card-border bg-card-bg shadow-sm">
        <div class="flex flex-wrap items-center gap-2 border-b border-card-divider px-5 py-3">
            <span class="grow font-semibold text-card-title-txt">Customer transactions</span>
            <input class="w-[200px]" placeholder="Search…">
            <x-button variant="outline" icon="settings">Filters</x-button>
            <x-button icon="plus-circle">New</x-button>
        </div>

        <div class="flex items-center justify-between px-5 py-3 text-sm text-card-txt">
            <span>Showing 1–6 of 128</span>
            <div class="flex items-center gap-1">
                <x-button variant="outline">‹</x-button>
                <x-button>1</x-button>
                <x-button variant="outline">2</x-button>
                <x-button variant="outline">›</x-button>
            </div>
        </div>
    </div>

    <h2 class="mb-1 mt-8 text-base font-semibold text-card-title-txt">Chart</h2>
    <p class="mb-3 max-w-3xl text-sm text-card-txt">
        Series colours are the chromatic anchors, which do not move between themes — only the
        surface and the rule under them do.
    </p>

    <div class="rounded-xl border border-card-border bg-card-bg p-4 shadow-sm">
        @php $series = [['brand', 'Sales', 92], ['info', 'Purchases', 70], ['success', 'Receipts', 56], ['warning', 'Pending', 44], ['accent-1', 'Accruals', 32], ['accent-2', 'Deposits', 22], ['danger', 'Overdue', 14]]; @endphp

        <div class="flex h-[170px] items-end gap-3 border-b border-card-divider pb-2">
            @foreach ($series as [$family, $label, $height])
                <span class="flex h-full flex-1 flex-col justify-end">
                    <span class="block rounded-t" style="background: var(--c-{{ $family }}-500); height: {{ $height }}%"></span>
                </span>
            @endforeach
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-4 text-xs text-card-txt">
            @foreach ($series as [$family, $label, $height])
                <span class="flex items-center gap-2">
                    <span class="inline-block h-3 w-3 rounded-sm" style="background: var(--c-{{ $family }}-500)"></span>{{ $label }}
                </span>
            @endforeach
        </div>
    </div>

    <h2 class="mb-1 mt-8 text-base font-semibold text-card-title-txt">Loader</h2>
    <p class="mb-3 max-w-3xl text-sm text-card-txt">
        The three states the shared busy indicator can be in, drawn by <code>data-loader</code>.
    </p>

    <div class="rounded-xl border border-card-border bg-card-bg p-4 shadow-sm">
        <div class="flex flex-col gap-3">
            @foreach ([['spinner', 'spinner'], ['bouncing ball', 'progress'], ['failure', 'warning']] as [$name, $kind])
                <div class="flex flex-wrap items-center gap-3">
                    <span class="w-32 flex-none text-xs font-semibold uppercase tracking-wide text-card-txt">{{ $name }}</span>
                    <div data-loader="{{ $kind }}"></div>
                </div>
            @endforeach
        </div>
    </div>

    <h2 class="mb-1 mt-8 text-base font-semibold text-card-title-txt">Empty state</h2>
    <p class="mb-3 max-w-3xl text-sm text-card-txt">
        A recipe: the sunken fill, a dashed edge, and the one action that would end the emptiness.
    </p>

    <div class="rounded-xl border border-card-border bg-card-bg p-4 shadow-sm">
        <div class="rounded-lg border border-dashed border-card-border bg-card-sunken-bg p-8 text-center text-card-txt">
            <span class="icon icon-invoice mb-2 block text-2xl text-card-entry-icon"></span>
            <b class="text-card-title-txt">No transactions yet</b>
            <p class="mt-1 text-sm">Create a sales order to see it listed here.</p>
            <x-button class="mt-3" icon="plus-circle">New sales order</x-button>
        </div>
    </div>

    <h2 class="mb-1 mt-8 text-base font-semibold text-card-title-txt">Card</h2>
    <p class="mb-3 max-w-3xl text-sm text-card-txt">
        Its surfaces, stacked in one card so the ladder can be read: the header band, the body, the
        divided rows and their hover, the sunken strip, and the footer.
    </p>

    <div class="grid gap-5 md:grid-cols-2">
        <div class="overflow-hidden rounded-xl border border-card-border bg-card-bg shadow-lg">
            <div class="bg-card-header-bg p-4 text-card-header-txt">
                <h3 class="m-0 text-lg font-semibold">Transactions</h3>
            </div>
            <div class="p-5">
                <div class="text-xl font-semibold text-card-title-txt">System settings</div>
                <p class="mt-1 text-sm leading-relaxed text-card-txt">Six permissions are granted by this role.</p>
            </div>
            <div class="divide-y divide-card-divider">
                <div class="flex items-center gap-3 px-5 py-3 text-card-txt transition hover:bg-card-hover-bg">Users &amp; access <span class="badge">6</span></div>
                <div class="flex items-center gap-3 px-5 py-3 text-card-txt transition hover:bg-card-hover-bg">Fiscal years <span class="badge">2</span></div>
            </div>
            <div class="grid gap-x-6 gap-y-1 bg-card-sunken-bg px-5 pb-4 pt-3 sm:grid-cols-2">
                <span class="rounded-md px-2 py-1.5 text-sm text-card-txt transition hover:bg-card-row-hover-bg">Void a transaction</span>
                <span class="rounded-md px-2 py-1.5 text-sm text-card-txt transition hover:bg-card-row-hover-bg">Rebuild stock</span>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-2 border-t border-card-divider bg-card-footer-bg px-8 py-4 text-sm text-card-txt">
                Last saved {{ now()->format('d/m/Y') }}
            </div>
        </div>
    </div>

    <h2 class="mb-1 mt-8 text-base font-semibold text-card-title-txt">Area index</h2>
    <p class="mb-3 max-w-3xl text-sm text-card-txt">
        The second way to read the tree: one card per section, laid across the page rather than down
        a column. The structure below is the one <code>pages/foundation/area-index</code> writes —
        the wrapper, the card, the two-column grid, the columns and the break between runs — because
        the indent an entry gets is decided by what it is wrapped in. Put these rows inside a
        <code>.nav</code> and <code>.nav .nav__entry</code> lands <code>ps-11</code> on them, which
        is right in the menu and wrong here, and the card comes out indented by 44px with nothing to
        say so.
    </p>

    <div class="area-index">
        <section class="area-index__card mb-4 rounded-lg bg-card-bg text-card-title-txt shadow-md">
            <div class="rounded-t-lg bg-card-header-bg p-2 text-card-header-txt md:p-4">
                <h2 class="text-lg font-semibold">Transactions</h2>
            </div>

            <div class="grid grid-cols-1 gap-4 p-2 md:grid-cols-2 md:p-4">
                @foreach ([
                    [['invoice', 'Sales Quotation Entry'], ['invoice', 'Sales Order Entry'], null, ['feature', 'Direct Delivery'], ['feature', 'Direct Invoice']],
                    [['reports', 'Customer Transaction Inquiry'], ['reports', 'Sales Order Inquiry'], null, ['setup-master', 'Recurrent Invoices']],
                ] as $column)
                    <div class="area-index__column col-span-1 grid grid-cols-1 p-2 md:p-4">
                        @foreach ($column as $entry)
                            @if ($entry === null)
                                {{-- A run break: drawn between two runs that both survived, never
                                     leading, trailing or doubled. --}}
                                <div class="area-index__break col-span-1" aria-hidden="true">&nbsp;</div>
                            @else
                                {{-- What `<x-nav::entry>` renders. Written out here because that
                                     component takes a navigation node and there is no tree on this
                                     page to take one from — the one hand-written thing in this
                                     section, and only its leaf. --}}
                                <a class="nav__entry nav__row col-span-1" href="#">
                                    <span class="icon nav__entry-icon icon-{{ $entry[0] }}"></span>
                                    <span class="nav__row-label">{{ $entry[1] }}</span>
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <h2 class="mb-1 mt-8 text-base font-semibold text-card-title-txt">Sticky bar</h2>
    <p class="mb-3 max-w-3xl text-sm text-card-txt">
        The strip an editor pins its save action to. Its fill is the one translucency the theme keeps
        — content has to show through as it scrolls under.
    </p>

    <div class="rounded-xl border border-card-border bg-card-sticky-bg p-4 shadow-md backdrop-blur">
        <x-button icon="button-ok">Save changes</x-button>
    </div>

    <h2 class="mb-1 mt-8 text-base font-semibold text-card-title-txt">Hero</h2>
    <p class="mb-3 max-w-3xl text-sm text-card-txt">
        The banded header a guest page sets its title in, with the caption strip a login or error
        page signs off with. The caption sits on the page rather than in the shell, so it is not the
        footer's to colour.
    </p>

    <div class="max-w-md overflow-hidden rounded-xl border border-card-border bg-card-bg shadow-lg">
        <div class="bg-hero-bg px-8 py-10 text-center">
            <div class="mb-2 text-2xl font-bold text-hero-title-txt">taqbook <span class="text-lg font-normal">ERP</span></div>
            <p class="text-sm text-hero-subtitle-txt">Sign in to continue</p>
        </div>
        <div class="p-6">
            <label class="mb-1 block text-sm font-semibold text-card-txt">Username</label>
            <input class="w-full" value="administrator">
            <x-button class="mt-4 w-full justify-center">Sign in</x-button>
            <div class="mt-6 rounded-lg border border-card-border bg-page-caption-bg px-6 py-4 text-center">
                <div class="text-sm text-page-caption-txt">{{ request()->getHost() }}</div>
                <div class="mt-1 text-xs text-page-caption-txt">{{ now()->format('d/m/Y · h:i A') }}</div>
            </div>
        </div>
    </div>

    <h2 class="mb-1 mt-8 text-base font-semibold text-card-title-txt">Backtrace</h2>
    <p class="mb-3 max-w-3xl text-sm text-card-txt">
        The <code>errors.partials.backtrace</code> partial itself, given sample frames. The app's own frames
        are filled and marked; a long run from vendor is dimmed and starts folded, which is
        <code>x-collapse</code> again in the place it was written for.
    </p>

    <div class="rounded-xl border border-card-border bg-card-bg p-4 shadow-sm">
        <div class="rounded border-s-4 border-message-error-border bg-message-error-bg px-4 py-3 text-sm text-message-error-txt">
            You must enter at least one non empty item line.
            @include('errors.partials.backtrace', ['groups' => $backtrace])
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    var KEY = 'taqbook-preview-theme';
    var root = document.documentElement;
    var button = document.querySelector('[data-skin-toggle]');
    var text = button.querySelector('[data-skin-toggle-label]');

    /* The label says where the click leads rather than where the page is, so nothing has to be
       read off the button to know what pressing it does. */
    function label() {
        text.textContent = root.getAttribute('data-skin') === 'dark' ? 'dark → light' : 'light → dark';
    }

    button.addEventListener('click', function () {
        var next = root.getAttribute('data-skin') === 'dark' ? null : 'dark';

        if (next) root.setAttribute('data-skin', next);
        else root.removeAttribute('data-skin');

        try { localStorage.setItem(KEY, next || 'light'); } catch (e) {}

        label();
    });

    label();

    /* The focus row shows the ring `reset.css` draws rather than a copy of it, which means
       something has to actually put focus there — a hand-written imitation is the one thing a
       reference may not do. Not on page load, which would scroll the page down to it. */
    var focusable = document.querySelector('[data-gallery-focus]');

    if (focusable) {
        focusable.addEventListener('click', function () { focusable.select(); });
    }

    /* Every hex on the palette card is printed by reading the custom property back, so the swatch
       and its label can never disagree with the stylesheet that painted it. */
    var computed = getComputedStyle(root);

    Array.prototype.forEach.call(document.querySelectorAll('[data-rung]'), function (el) {
        el.textContent = (computed.getPropertyValue(el.dataset.rung) || '').trim() || '—';
    });
})();
</script>
@endpush
