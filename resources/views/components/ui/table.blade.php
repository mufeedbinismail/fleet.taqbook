@use('App\Foundation\Component\Control\Support\Control')
@use('App\Foundation\Component\Table\Enum\DataType')
@use('App\Foundation\Component\Control\Enum\ControlName')
@use('App\Foundation\Component\Table\Enum\Stick')
@use('App\Foundation\Component\Table\Exception\TableException')
@use('App\Foundation\Component\Table\Support\Length')
@use('App\Foundation\Component\Table\Support\StickyColumns')
@use('App\Foundation\Component\Table\ValueObject\FilterDefinition')
@use('Illuminate\Support\Js')
@use('Illuminate\View\ComponentSlot')

@props([
    /*
        The settled table being drawn, where there is one. Optional, since a table may be assembled
        from parts instead, with no definition behind it to disagree with.
    */
    'definition' => null,
    'name' => null,
    // What the table is, in words. Required, and hidden by default, most tables sitting under a
    // heading that already names them; `caption-visible` is for the one that stands on its own.
    'caption' => null,
    'captionVisible' => false,
    'route' => null,
    'params' => [],
    'url' => null,
    'columns' => [],
    'perPage' => null,
    'perPageOptions' => [10, 25, 50, 100],
    'search' => true,
    'columnSearch' => false,
    'filters' => [],
    'export' => [],
    'urlSync' => true,
    /*
        The page this table opens on, resolved before it was drawn. Declared rather than assumed: it
        puts the rows in the page source, which a saved page, a document cache and a captured DOM
        all reach and a fetch response does not.
    */
    'initial' => null,
    /*
        Holds off the first fetch until the table is scrolled to. The first, not every one: anything
        causing a fetch ends the waiting, so the table fetches exactly once either way.
    */
    'defer' => false,
    'rowKey' => null,
    // Keys into the row, not expressions: what a row's class or link is gets decided where the
    // row is built, and travels with it as data.
    'rowClassKey' => null,
    'rowHrefKey' => null,
    // A cap rather than a height: a table short enough to fit under it never scrolls. `none`
    // declines it, and gives up the pinned chrome with it.
    'height' => '70vh',
])

@php
    if ($definition !== null) {
        if ($name !== null) {
            throw TableException::alreadyDeclaredByDefinition('name');
        }

        if ($columns !== []) {
            throw TableException::alreadyDeclaredByDefinition('columns');
        }

        $name = $definition->name;
        $columns = $definition->definitions;
    }

    if ($name === null || trim((string) $name) === '') {
        throw TableException::unnamed();
    }

    if ($caption === null || trim((string) $caption) === '') {
        throw TableException::uncaptioned();
    }

    if ($height !== 'none' && ! Length::measurable((string) $height)) {
        throw TableException::heightNotALength((string) $height);
    }

    if ($defer && $initial !== null) {
        throw TableException::deferredAndHandedAPage();
    }

    /*
        A handed page settles where the table opens, so anything declared here that would open it
        somewhere else can only contradict it — silently, the page having already been drawn.
    */
    if ($initial !== null) {
        if ($perPage !== null) {
            throw TableException::declaredBesideAHandedPage('per-page');
        }

        if ($filters !== []) {
            throw TableException::declaredBesideAHandedPage('filters');
        }
    }

    /*
        Slots, which no @props declares: `cell_<key>` and `foot_<key>` take a column's cell and its
        footer cell over, with `row` in scope; `actions` is a trailing column carrying `label`,
        `sticky` and `width`, and exists only here, nothing served from a definition carrying it.
    */
    $columns = array_map(fn (\App\Foundation\Component\Table\ValueObject\ColumnDefinition $column) => $column->toArray(), $columns);

    // An appearance is what a definition drawing a column has; a row flag has none.
    $drawn = array_values(array_filter($columns, fn (array $column) => $column['appearance'] !== null));

    // Kept only where they are slots rather than taken at their name, so a variable that happens
    // to share a prefix can never be drawn into a cell.
    $slots = array_filter(
        get_defined_vars(),
        fn ($value, $key) => $value instanceof ComponentSlot
            && (str_starts_with($key, 'cell_') || str_starts_with($key, 'foot_')),
        ARRAY_FILTER_USE_BOTH,
    );

    $config = [
        'name' => $name,
        'url' => $url ?? ($route === null ? null : route($route, $params)),
        'columns' => $columns,
        // What the table narrows by on its own, published so a chip on it has a label to read.
        'filters' => array_map(fn (FilterDefinition $filter) => $filter->toArray(), $definition?->filters ?? []),
        'perPageOptions' => array_values($perPageOptions),
        // The position the table opens in; a page size nobody gave stays null.
        'defaults' => ['perPage' => $perPage, 'filters' => (object) $filters],
        'search' => (bool) $search,
        'columnSearch' => (bool) $columnSearch,
        'export' => array_values($export),
        'urlSync' => (bool) $urlSync,
        // The page and the position it answers for, both, so the two can be told apart.
        'initial' => $initial?->toArray(),
        'defer' => (bool) $defer,
        // Placeholders rather than fragments, so each sentence stays one translatable string and
        // a language that orders it differently can say so.
        'messages' => [
            'summary' => __('foundation.table.page.summary', ['from' => ':from:', 'to' => ':to:', 'total' => ':total:']),
            'none' => __('foundation.table.page.empty'),
            'yes' => __('foundation.table.filter.yes'),
            'no' => __('foundation.table.filter.no'),
            'any' => __('foundation.table.filter.any'),
            'remove' => __('foundation.table.filter.remove', ['filter' => ':filter:']),
            'position' => __('foundation.table.sort.position', ['position' => ':position:']),
            'sorted' => __('foundation.table.announce.sorted', ['columns' => ':columns:']),
            'unsorted' => __('foundation.table.announce.unsorted'),
            'ascending' => __('foundation.table.announce.ascending', ['column' => ':column:']),
            'descending' => __('foundation.table.announce.descending', ['column' => ':column:']),
            'then' => __('foundation.table.announce.then'),
            'results' => __('foundation.table.announce.results', ['from' => ':from:', 'to' => ':to:', 'total' => ':total:']),
            'noResults' => __('foundation.table.announce.empty'),
        ],
    ];

    $rowKeyExpression = $rowKey === null ? 'index' : 'row['.Js::from($rowKey).']';

    /*
        The trailing column pins on the same terms the declared ones do. Its width is asked for
        whether or not anything is pinned behind it today, the alternative being a table that pins
        correctly until the day a column joins it.
    */
    $pinsActions = isset($actions) && filter_var($actions->attributes->get('sticky'), FILTER_VALIDATE_BOOL);

    $actionsWidth = ! $pinsActions ? null
        : ($actions->attributes->get('width') ?? throw TableException::pinnedWithoutWidth('actions'));

    // Held to what a declared column's width is, the end run being measured from it.
    if ($actionsWidth !== null && ! Length::measurable((string) $actionsWidth)) {
        throw TableException::widthNotALength('actions', (string) $actionsWidth);
    }

    $pinned = StickyColumns::placed($drawn, $actionsWidth);

    $actionsPin = ! $pinsActions ? null : [
        'side' => Stick::End->value,
        'away' => '0',
        'width' => $actionsWidth,
        // The rows pass behind whichever pinned column is nearest the middle.
        'edge' => ! array_filter($drawn, fn (array $column) => $column['appearance']['sticky'] === Stick::End->value),
    ];

    /*
        Spelled out rather than assembled: the build keeps a rule only where it sees the class named
        in a template as written, and a name put together at render time appears in none of them.
    */
    $pinnable = [
        'x-table__head' => [
            'start' => 'x-table__head--sticky-start',
            'end' => 'x-table__head--sticky-end',
            'edge' => 'x-table__head--sticky-edge',
        ],
        'x-table__cell' => [
            'start' => 'x-table__cell--sticky-start',
            'end' => 'x-table__cell--sticky-end',
            'edge' => 'x-table__cell--sticky-edge',
        ],
    ];

    // Read at every row a column has a cell in — heading, filter box, rows, footer.
    $pin = fn (string $part, ?array $placed) => $placed === null ? ''
        : ' '.$pinnable[$part][$placed['side']].($placed['edge'] ? ' '.$pinnable[$part]['edge'] : '');

    /*
        A grid sized from its content treats a width on `<col>` as a preference, which a pinned
        column cannot afford: the one behind it is placed from this exact figure. Both bounds, the
        content being free to come out narrower than the declaration as well as wider.
    */
    $pinStyle = fn (array $placed) => "--x-table-stick: {$placed['away']}"
        ."; width: {$placed['width']}; min-width: {$placed['width']}; max-width: {$placed['width']}";

    /*
        Whether the pane has anywhere to scroll to vertically, which pinning the heading and the
        footer depends on: a sticky cell holds its place against the nearest scrolling ancestor, and
        an uncapped pane never moves. Said as a class because a rule cannot read a style attribute.
    */
    $capped = $height !== 'none';

    /*
        Whether anything here sorts, said once in the caption rather than on each heading, where a
        hint would be read out again in every column.
    */
    $sorts = array_filter($drawn, fn (array $column) => $column['sortable']) !== [];
@endphp

<div
    {{ $attributes->merge(['class' => 'x-table']) }}
    x-data="dataTable(@js($config))"
>
    <div class="x-table__bar">
        @if ($search)
            <label class="x-table__search">
                <span class="icon icon-search x-table__search-icon" aria-hidden="true"></span>
                <input
                    type="search"
                    class="x-table__search-input"
                    autocomplete="off"
                    placeholder="{{ __('foundation.table.search_placeholder') }}"
                    aria-label="{{ __('foundation.table.search') }}"
                    :value="asked.q"
                    @input="setSearch($event.target.value)"
                >
            </label>
        @endif

        <div class="x-table__bar-actions">
            <x-ui.button variant="outline" x-show="isDirty()" x-cloak @click="reset()">
                {{ __('foundation.table.clear') }}
            </x-ui.button>

            {{ $toolbar ?? '' }}

            @foreach ($export as $format)
                <x-ui.button variant="outline" icon="download" @click="exportTo('{{ $format }}')">
                    {{ __('foundation.table.export.'.$format) }}
                </x-ui.button>
            @endforeach
        </div>
    </div>

    <div class="x-table__chips" x-show="chips().length > 0" x-cloak>
        <template x-for="chip in chips()" :key="chip.key">
            <button type="button" class="x-table__chip" :aria-label="chip.remove" @click="setFilter(chip.key, '')">
                <span class="x-table__chip-label" x-text="chip.label"></span>
                <span x-text="chip.text"></span>
                <span class="x-table__chip-remove" aria-hidden="true">&times;</span>
            </button>
        </template>
    </div>

    <p class="x-table__error" x-show="ui.error" x-text="ui.error" x-cloak></p>

    {{-- The same news, said rather than drawn. Its own region rather than the one below, so the
         polite message is not lost behind the assertive one and the timer that expires a sentence
         does not take this away as well. --}}
    <div class="x-table__alert" role="alert" x-text="ui.error"></div>

    {{-- Written into the page empty rather than created when there is something to say: a live
         region is announced only to software already watching it, so one arriving with its first
         message arrives too late to deliver it. --}}
    <div class="x-table__announcement" role="status" x-text="ui.announcement"></div>

    {{-- A sibling of the scrolling box rather than a child, the indicator having to be placed
         against something that does not scroll, and written after it so it wins the tie. --}}
    <div class="x-table__body">
        {{-- Focusable because a box that scrolls and cannot be focused is a box a keyboard cannot
             scroll, and named by its own caption so two tables on a page can be told apart. --}}
        <div
            class="x-table__scroll{{ $capped ? ' x-table__scroll--capped' : '' }}"
            role="region"
            tabindex="0"
            aria-labelledby="{{ $name }}-caption"
            @if ($capped) style="max-height: {{ $height }}" @endif
        >
        <table class="x-table__grid">
            <caption id="{{ $name }}-caption" class="x-table__caption{{ $captionVisible ? ' x-table__caption--visible' : '' }}">
                {{ $caption }}
                @if ($sorts)
                    {{ __('foundation.table.caption.sortable') }}
                @endif
            </caption>

            <colgroup>
                @foreach ($drawn as $column)
                    <col @if ($column['appearance']['width'] !== null) style="width: {{ $column['appearance']['width'] }}" @endif>
                @endforeach
                @isset($actions)
                    <col @if ($actionsWidth !== null) style="width: {{ $actionsWidth }}" @endif>
                @endisset
            </colgroup>

            <thead>
                <tr>
                    @foreach ($drawn as $column)
                        @php
                            $narrows = $columnSearch && $column['filter'] !== null;

                            /*
                                A heading's accessible name is computed from its subtree, which the
                                filter control living there would join. `aria-labelledby` overrides
                                that computation entirely; scoped by the table's own name, so two
                                tables on a page never point at each other's headings.
                            */
                            $headingId = $narrows ? "{$name}-heading-{$column['key']}" : null;
                        @endphp

                        {{-- A column that can be narrowed keeps its filter in its own heading.
                             Shown from the chips as well as the toggle, so a filter arriving with
                             the address opens the heading holding it. --}}
                        <th
                            scope="col"
                            class="x-table__head x-table__head--{{ $column['appearance']['align'] }}{{ $column['sortable'] ? ' x-table__head--sortable' : '' }}{{ $pin('x-table__head', $pinned[$column['key']] ?? null) }} {{ $column['appearance']['class'] }}"
                            @isset($pinned[$column['key']])
                                style="{{ $pinStyle($pinned[$column['key']]) }}"
                            @endisset
                            @if ($narrows)
                                x-data="{ open: false }"
                                :class="(open || chips().some((chip) => chip.key === @js($column['filter']['key']))) && 'x-table__head--filtering'"
                                aria-labelledby="{{ $headingId }}"
                            @endif
                            {{-- Omitted rather than set to "none", which no screen reader
                                 distinguishes from it, and which would claim a precedence ARIA has
                                 no attribute for. --}}
                            @if ($column['sortable'])
                                :aria-sort="sortOf(@js($column['key'])).ariaSort"
                            @endif
                        >
                        <div class="x-table__head-bar">
                            <span class="x-table__head-label" @if ($headingId !== null) id="{{ $headingId }}" @endif>
                            @if ($column['sortable'])
                                <button
                                    type="button"
                                    class="x-table__sort"
                                    @click="toggleSort(@js($column['key']), $event.shiftKey)"
                                >
                                    <span>{{ $column['label'] }}</span>
                                    <span
                                        class="x-table__sort-arrow icon"
                                        :class="{ asc: 'icon-sort-asc', desc: 'icon-sort-desc' }[sortOf(@js($column['key'])).direction] ?? 'icon-sort-none'"
                                        aria-hidden="true"
                                    ></span>
                                    {{-- The aria-label folds into the button's accessible name, so
                                         the ordinal is announced as well as read. --}}
                                    <span
                                        class="x-table__sort-position"
                                        x-show="sortOf(@js($column['key'])).position"
                                        x-text="sortOf(@js($column['key'])).position"
                                        :aria-label="sortOf(@js($column['key'])).announced"
                                        x-cloak
                                    ></span>
                                </button>
                            @else
                                {{ $column['label'] }}
                            @endif
                            </span>

                            @if ($narrows)
                                <button
                                    type="button"
                                    class="x-table__head-funnel"
                                    :class="chips().some((chip) => chip.key === @js($column['filter']['key'])) && 'x-table__head-funnel--set'"
                                    :aria-expanded="open || chips().some((chip) => chip.key === @js($column['filter']['key']))"
                                    aria-label="{{ __('foundation.table.column_search', ['column' => $column['label']]) }}"
                                    @click="open = ! open"
                                >
                                    <span class="icon icon-filter" aria-hidden="true"></span>
                                </button>
                            @endif
                        </div>

                        @if ($narrows)
                            @php
                                $searchable = $column['filter']['source'] !== null
                                    || $column['filter']['control'] === ControlName::MultiSelect->value;

                                /*
                                    Scoped by the table's name, so two tables on a page never narrow
                                    themselves by each other's controls.
                                */
                                $controlId = "{$name}-filter-{$column['key']}";
                            @endphp

                            <div class="x-table__head-filter">
                                @if ($column['filter']['control'] === ControlName::DateRange->value)
                                    {{-- Two controls writing into one filter, bound with @change
                                         rather than @input: a date is picked, not typed. The bound
                                         in force is pushed into each field rather than bound onto
                                         its value, a bound coming back normalised and writing that
                                         on replacing the spelling being shown. --}}
                                    {{-- The two ends are linked by the directive rather than by
                                         anything either of them knows: neither may cross the
                                         other. --}}
                                    <div class="x-table__filter-range" x-date-range data-date-range="{{ Control::config(['maxDays' => $column['filter']['maxDays'] ?? null]) }}">
                                        @foreach (['from', 'to'] as $bound)
                                            @php
                                                // A bag rather than written on the tag: @js is never compiled
                                                // inside a component's attribute, so an expression written there
                                                // would be delivered spelled out rather than evaluated.

                                                // An HtmlString so it is escaped exactly once, Js::from having
                                                // already made each value safe to stand in an attribute.
                                                $key = \Illuminate\Support\Js::from($column['filter']['key']);
                                                $end = \Illuminate\Support\Js::from($bound);

                                                $boundId = "{$controlId}-{$bound}";

                                                $bind = new \Illuminate\View\ComponentAttributeBag([
                                                    'class' => 'x-table__filter-input',
                                                    // The heading and then this end's own word,
                                                    // composed here because a name written on an
                                                    // element replaces what labels it rather than
                                                    // joining it.
                                                    'aria-labelledby' => "{$headingId} {$boundId}",
                                                    // Read once before the call as well as inside
                                                    // it: `?.` skips the argument along with the
                                                    // call, so a field not yet built would leave
                                                    // nothing registered as worth watching.
                                                    'x-effect' => new \Illuminate\Support\HtmlString(
                                                        "((asked.filters[{$key}] ?? {})[{$end}], \$el.__xDate?.set((asked.filters[{$key}] ?? {})[{$end}] ?? null))"
                                                    ),
                                                    '@change' => new \Illuminate\Support\HtmlString(
                                                        "setFilter({$key}, { ...(asked.filters[{$key}] ?? {}), {$end}: \$event.target.value })"
                                                    ),
                                                ]);
                                            @endphp

                                            {{-- On the page so that a name can point at it: which
                                                 end a box is otherwise shows only in where it
                                                 sits. --}}
                                            <span id="{{ $boundId }}" class="x-table__filter-bound">{{ __('foundation.table.filter.range.'.$bound) }}</span>

                                            <x-ui.date :attributes="$bind" />
                                        @endforeach
                                    </div>
                                @elseif ($searchable)
                                    @php
                                        // A bag rather than written on the tag: @js is never
                                        // compiled inside a component's attribute.
                                        $key = Js::from($column['filter']['key']);
                                        $several = $column['filter']['multiple'] ?? false;

                                        /*
                                            Read off the control rather than out of the event: a
                                            select holding several choices reports only the first as
                                            its value. The empty row is how "any" is said, not a
                                            choice, so it is dropped.
                                        */
                                        $chosen = $several
                                            ? "Array.from(\$event.target.selectedOptions).map((option) => option.value).filter((value) => value !== '')"
                                            : '$event.target.value';

                                        /*
                                            What the chosen rows are called, handed up with them: a
                                            fetched list is nowhere on this page, so a choice being
                                            announced is the only moment its name is known without
                                            a second request.
                                        */
                                        $named = "Object.fromEntries(Array.from(\$event.target.selectedOptions)"
                                            .".filter((option) => option.value !== '')"
                                            .'.map((option) => [option.value, option.text]))';

                                        /*
                                            A list narrowed by another filter is told which control
                                            holds it, worked out here from the filter key. Written
                                            as an attribute match rather than an id selector, so a
                                            key holding anything needing escaping still names it.
                                        */
                                        $narrowedBy = [];

                                        foreach ($column['filter']['source']['dependsOn'] ?? [] as $parameter => $sibling) {
                                            $narrowedBy[$parameter] = '[id="'.$name.'-filter-'.$sibling.'"]';
                                        }

                                        $bind = new \Illuminate\View\ComponentAttributeBag([
                                            'class' => 'x-table__filter-input',
                                            // Labelled by the heading its column shares with every
                                            // other control there.
                                            'aria-labelledby' => $headingId,
                                            /*
                                                Pushed into the control rather than bound onto it,
                                                and silently: announced, it would come straight back
                                                as a choice and the table would reload itself for as
                                                long as anyone watched.

                                                Read once before the call as well as inside it: `?.`
                                                skips the argument along with the call, so a control
                                                not yet built would leave nothing registered as
                                                worth watching.
                                            */
                                            'x-effect' => new \Illuminate\Support\HtmlString(
                                                "(filterValues({$key}), \$el.__xSelect?.setValue(filterValues({$key}), { silent: true }))"
                                            ),
                                            '@change' => new \Illuminate\Support\HtmlString(
                                                "setFilter({$key}, {$chosen}, {$named})"
                                            ),
                                        ]);
                                    @endphp

                                    <x-ui.select
                                        :id="$controlId"
                                        :name="$controlId"
                                        :multiple="$several"
                                        :options="$column['filter']['options']"
                                        :selected="$filters[$column['filter']['key']] ?? []"
                                        :placeholder="__('foundation.table.filter.any')"
                                        :url="$column['filter']['source']['url'] ?? null"
                                        :params="$column['filter']['source']['params'] ?? []"
                                        :param-sources="$narrowedBy"
                                        :min-search="$column['filter']['source']['minSearch'] ?? 0"
                                        :attributes="$bind"
                                    />
                                @elseif ($column['filter']['control'] === ControlName::Select->value)
                                    <select
                                        id="{{ $controlId }}"
                                        class="x-table__filter-input"
                                        aria-labelledby="{{ $headingId }}"
                                        @change="setFilter(@js($column['filter']['key']), $event.target.value)"
                                    >
                                        {{-- Selected per option rather than as a value on the
                                             control, the choice being free to arrive with the
                                             address before this control exists to hold one. --}}
                                        <option value="" :selected="filterValue(@js($column['filter']['key'])) === ''">{{ __('foundation.table.filter.any') }}</option>
                                        @foreach ($column['filter']['options'] as $value => $label)
                                            <option value="{{ $value }}" :selected="filterValue(@js($column['filter']['key'])) === @js((string) $value)">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                @elseif ($column['filter']['control'] === ControlName::Toggle->value)
                                    <select
                                        id="{{ $controlId }}"
                                        class="x-table__filter-input"
                                        aria-labelledby="{{ $headingId }}"
                                        @change="setFilter(@js($column['filter']['key']), $event.target.value)"
                                    >
                                        <option value="" :selected="filterValue(@js($column['filter']['key'])) === ''">{{ __('foundation.table.filter.any') }}</option>
                                        <option value="1" :selected="filterValue(@js($column['filter']['key'])) === '1'">{{ __('foundation.table.filter.yes') }}</option>
                                        <option value="0" :selected="filterValue(@js($column['filter']['key'])) === '0'">{{ __('foundation.table.filter.no') }}</option>
                                    </select>
                                @elseif ($column['filter']['control'] !== null)
                                    <input
                                        id="{{ $controlId }}"
                                        type="search"
                                        class="x-table__filter-input"
                                        autocomplete="off"
                                        aria-labelledby="{{ $headingId }}"
                                        :value="filterValue(@js($column['filter']['key']))"
                                        @input="searchColumn(@js($column['filter']['key']), $event.target.value)"
                                    >
                                @endif
                            </div>
                        @endif
                        </th>
                    @endforeach

                    @isset($actions)
                        <th
                            scope="col"
                            class="x-table__head x-table__head--right x-table__actions{{ $pin('x-table__head', $actionsPin) }}"
                            @if ($actionsPin !== null) style="{{ $pinStyle($actionsPin) }}" @endif
                        >{{ $actions->attributes->get('label', '') }}</th>
                    @endisset
                </tr>
            </thead>

            <tbody :aria-busy="ui.loading">
                <template x-for="(row, index) in answer.rows" :key="{{ $rowKeyExpression }}">
                    <tr
                        class="x-table__row{{ $rowHrefKey !== null ? ' x-table__row--link' : '' }}"
                        @if ($rowClassKey !== null) :class="row[{{ Js::from($rowClassKey) }}]" @endif
                        @if ($rowHrefKey !== null) @click="row[{{ Js::from($rowHrefKey) }}] && (window.location.href = row[{{ Js::from($rowHrefKey) }}])" @endif
                    >
                        @foreach ($drawn as $column)
                            <td
                                class="x-table__cell x-table__cell--{{ $column['appearance']['align'] }} x-table__cell--{{ $column['dataType'] }}{{ $pin('x-table__cell', $pinned[$column['key']] ?? null) }} {{ $column['appearance']['class'] }}"
                                @isset($pinned[$column['key']])
                                    style="{{ $pinStyle($pinned[$column['key']]) }}"
                                @endisset
                                {{-- The row's own word, never a reading of the formatted value,
                                     and never over a slot. --}}
                                @if ($column['dataType'] === DataType::Money->value && ! isset($slots['cell_'.$column['key']]))
                                    :class="row[@js($column['key'].'_negative')] && 'x-table__cell--money-negative'"
                                @endif
                            >
                                @if (isset($slots['cell_'.$column['key']]))
                                    {{ $slots['cell_'.$column['key']] }}
                                @else
                                    <span x-text="cellOf(row, @js($column['key']), @js($column['appearance']['default']))"></span>
                                @endif
                            </td>
                        @endforeach

                        @isset($actions)
                            <td
                                class="x-table__cell x-table__cell--right x-table__actions{{ $pin('x-table__cell', $actionsPin) }}"
                                @if ($actionsPin !== null) style="{{ $pinStyle($actionsPin) }}" @endif
                            >{{ $actions }}</td>
                        @endisset
                    </tr>
                </template>
            </tbody>

            @isset($foot)
                <tfoot class="x-table__tfoot">{{ $foot }}</tfoot>
            @else
                <tfoot class="x-table__tfoot" x-show="answer.footer.length > 0" x-cloak>
                    <template x-for="(row, index) in answer.footer" :key="index">
                        <tr>
                            @foreach ($drawn as $column)
                                <td
                                    class="x-table__cell x-table__cell--{{ $column['appearance']['align'] }} x-table__cell--{{ $column['dataType'] }}{{ $pin('x-table__cell', $pinned[$column['key']] ?? null) }} {{ $column['appearance']['class'] }}"
                                    @isset($pinned[$column['key']])
                                        style="{{ $pinStyle($pinned[$column['key']]) }}"
                                    @endisset
                                    @if ($column['dataType'] === DataType::Money->value && ! isset($slots['foot_'.$column['key']]))
                                        :class="row[@js($column['key'].'_negative')] && 'x-table__cell--money-negative'"
                                    @endif
                                >
                                    @if (isset($slots['foot_'.$column['key']]))
                                        {{ $slots['foot_'.$column['key']] }}
                                    @else
                                        <span x-text="cellOf(row, @js($column['key']))"></span>
                                    @endif
                                </td>
                            @endforeach

                            @isset($actions)
                                <td
                                    class="x-table__cell x-table__actions{{ $pin('x-table__cell', $actionsPin) }}"
                                    @if ($actionsPin !== null) style="{{ $pinStyle($actionsPin) }}" @endif
                                ></td>
                            @endisset
                        </tr>
                    </template>
                </tfoot>
            @endisset
        </table>

        <p class="x-table__empty" x-show="isEmpty()" x-cloak>
            <span x-show="isNarrowed()">{{ $empty ?? __('foundation.table.empty') }}</span>
            <span x-show="! isNarrowed()">{{ $blank ?? __('foundation.table.blank') }}</span>
        </p>
        </div>

        <div data-loader-container :data-loader-visible="ui.loading" aria-hidden="true">
            <div data-loader="dots"></div>
        </div>
    </div>

    <div class="x-table__foot">
        <p class="x-table__summary" x-text="summary()"></p>

        <div class="x-table__foot-controls">
            <label class="x-table__size">
                <span>{{ __('foundation.table.page.size') }}</span>
                <select class="x-table__size-select" @change="setPerPage($event.target.value)">
                    <template x-for="size in ui.perPageOptions" :key="size">
                        <option :value="size" :selected="size === answer.perPage" x-text="size"></option>
                    </template>
                </select>
            </label>

            <div class="x-table__pager">
                <button type="button" class="x-table__pager-step" :disabled="! hasPrevious()" @click="first()"
                    aria-label="{{ __('foundation.table.page.first') }}"><span class="icon icon-page-first" aria-hidden="true"></span></button>
                <button type="button" class="x-table__pager-step" :disabled="! hasPrevious()" @click="previous()"
                    aria-label="{{ __('foundation.table.page.previous') }}"><span class="icon icon-page-previous" aria-hidden="true"></span></button>
                <span class="x-table__pager-count"><span x-text="answer.page"></span> / <span x-text="answer.pages"></span></span>
                <button type="button" class="x-table__pager-step" :disabled="! hasNext()" @click="next()"
                    aria-label="{{ __('foundation.table.page.next') }}"><span class="icon icon-page-next" aria-hidden="true"></span></button>
                <button type="button" class="x-table__pager-step" :disabled="! hasNext()" @click="last()"
                    aria-label="{{ __('foundation.table.page.last') }}"><span class="icon icon-page-last" aria-hidden="true"></span></button>
            </div>
        </div>
    </div>
</div>
