@php
use App\Foundation\Shared\Enum\Skin;

$title = $title ?? null;

// Straight from the guard, not from the legacy session: a page that never boots FrontAccounting
// has nothing hydrated there, and this chrome is drawn on every page either way.
$user = auth()->user();

$dark = user_settings()->skin() === Skin::Dark;

// The dashboard of the area this page is in, named outright: this leads to the figures for an area
// rather than to the area itself, so it does not go through whatever else opening an area means.
// With no area to be in, the home address is left to work that out.
$dashboard = ($area = $location->area()?->key()) === null
    ? legacy_url('index.php')
    : legacy_url('admin/dashboard.php', ['area' => $area]);

// Define toolbox
$toolbox = [
    'dashboard' => [
        'link' => $dashboard,
        'icon' => 'icon-statistics',
        'label' => __('Dashboard')
    ],
    'preferences' => [
        'link' => legacy_url("/admin/display_prefs.php"),
        'icon' => 'icon-prefs',
        'label' => __('Preferences')
    ],
    'change_password' => [
        'link' => legacy_url("/admin/change_current_user_password.php", ['selected_id' => $user->user_id ?? '']),
        'icon' => 'icon-security',
        'label' => __('Change password')
    ],
    'logout' => [
        'link' => route('logout'),
        'icon' => 'icon-logout',
        'label' => __('Logout'),
        'method' => 'post'
    ]
];

// The hints are drawn where the chrome closes, which on an ajax request is not sent. Queuing the
// same text as an update is what puts it on a page that only replaced its middle.
if ($shouldShowFooter && isset($GLOBALS['Pagehelp']) && isset($GLOBALS['Ajax'])) {
    $GLOBALS['Ajax']->addUpdate(true, 'hotkeyshelp', $help);
}
@endphp
<section
    class="shell"
    @if (!$no_menu)
        x-data
        x-drawer
    @endif
>
    @if (!$no_menu)
    <!-- Mobile drawer backdrop -->
    <div x-drawer:backdrop x-transition.opacity x-cloak></div>

    <!-- Sidebar -->
    <aside class="shell__sidebar" x-drawer:panel.left x-cloak>
        <div class="shell__sidebar-inner">
            <h2 class="shell__sidebar-brand">
                <img src="{{ url("/themes/default/images/logo.svg") }}" alt="Logo">
                taqbook <small><sub>ERP</sub></small>
            </h2>
            <x-nav::sidebar :navigation="$navigation" :location="$location" />
        </div>
    </aside>
    @endif

    <section class="shell__section">
        @if(!$no_menu)
        <header class="shell__header">
            <!-- Sidebar Minimize Button -->
            <button id="sidebar-toggle" class="me-2 bg-transparent border-0 cursor-pointer" aria-label="Toggle sidebar" x-drawer:trigger>
                <span class="icon icon-bars text-[2rem]"></span>
            </button>
            @if ($title && !$is_index)
            <h1 class="shell__header-title">{{ $title }}</h1>
            @endif
            <div class="shell__header-toolbar">
                <x-ui.toggle
                    :checked="$dark"
                    :on="['label' => __('foundation.skin.dark'), 'icon' => 'moon']"
                    :off="['label' => __('foundation.skin.light'), 'icon' => 'sun']"
                    :aria-label="__('foundation.skin.label')"
                    @toggled="App.setSkin($event.detail.on)"
                />

                <div x-dropdown>
                    <button type="button" x-dropdown:trigger>
                        <span class="icon icon-circle-user text-[2rem]"></span>
                        <span class="hidden md:inline">{{ $user->real_name ?? '' }}</span>
                    </button>

                    <template x-teleport="body">
                        <ul x-dropdown:panel x-transition x-cloak>
                            <li class="x-dropdown__header">
                                <span class="icon icon-circle-user"></span>
                                <span class="x-dropdown__header-name">{{ $user->real_name ?? '' }}</span>
                            </li>
                            @foreach($toolbox as $key => $item)
                                <li>
                                    @if (($item['method'] ?? 'get') === 'post')
                                        <form method="POST" action="{{ $item['link'] }}">
                                            @csrf
                                            <button type="submit" class="x-dropdown__item w-full text-left bg-transparent border-0 cursor-pointer">
                                                <span class="icon {{ $item['icon'] }}"></span>
                                                <span>{{ $item['label'] }}</span>
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ $item['link'] }}" class="x-dropdown__item">
                                            <span class="icon {{ $item['icon'] }}"></span>
                                            <span>{{ $item['label'] }}</span>
                                        </a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </template>
                </div>
            </div>
        </header>
        <x-nav::breadcrumbs :location="$location" />
        @endif

        <main class="shell__content">
            <div data-loader-container><div data-loader="spinner"></div></div>
            <section class="shell__content-scroller">

