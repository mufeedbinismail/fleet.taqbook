@props([
    // The address parameter the picked record is kept in, and the route that answers for one.
    'param',
    'route',
    'selected' => null,
    'title',
    'railLabel',
    'detailLabel',
    'filtersLabel',
    'lookupLabel',
    'lookupId' => 'record-lookup',
    /*
        What the pane says while nothing is picked, while it loads and when it cannot show the record:
        `none_title`, `none`, `missing_title`, `missing`, `problem_title`, `loading`; `missing` is a
        translation key the page registers, filled with `:recordId`.
    */
    'messages',
    'stateIcon' => 'search',
])

<div {{ $attributes }} x-record-browser x-cloak
     data-record-browser="{{ json_encode(['param' => $param, 'route' => $route, 'selected' => $selected]) }}">
    <aside aria-label="{{ $railLabel }}" class="x-record-browser__rail">
        <div class="x-record-browser__rail-head">
            <h2 class="x-record-browser__title">{{ $title }}</h2>

            <div class="flex items-center gap-1">
                {{-- What the rail offers beside its filters, such as the button that adds a record. --}}
                @isset($actions)
                    {{ $actions }}
                @endisset

                <button type="button" class="ghost" x-record-browser:filters
                        title="{{ $filtersLabel }}" aria-label="{{ $filtersLabel }}">
                    <span class="icon icon-filter" aria-hidden="true"></span>
                </button>
            </div>
        </div>

        <div class="x-record-browser__lookup">
            <label for="{{ $lookupId }}" class="x-record-browser__lookup-label">{{ $lookupLabel }}</label>
            {{ $lookup }}
        </div>

        {{ $list }}
    </aside>

    <section aria-label="{{ $detailLabel }}" class="x-record-browser__detail" x-record-browser:detail>
        <template x-record-browser:state>
            <div class="x-record-browser__state" role="status">
                <div class="x-record-browser__state-icon">
                    <span class="icon" :class="status.loading ? 'icon-spinner' : 'icon-{{ $stateIcon }}'" aria-hidden="true"></span>
                </div>
                <h2 class="x-record-browser__state-title"
                    x-text="selected === null ? @js($messages['none_title']) : (status.missing ? @js($messages['missing_title']) : (status.problem ? @js($messages['problem_title']) : @js($messages['loading'])))"></h2>
                <p class="x-record-browser__state-text" x-show="selected === null || status.missing || status.problem"
                   x-text="selected === null ? @js($messages['none']) : (status.missing ? App.i18n(@js($messages['missing']), { recordId: selected }) : status.problem)"></p>
            </div>
        </template>

        <template x-record-browser:pane>
            <div class="x-record-browser__pane">{{ $detail }}</div>
        </template>
    </section>
</div>
