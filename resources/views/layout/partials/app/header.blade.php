@php
// Set defaults from passed variables or session/globals
$no_menu = $no_menu ?? false;
$is_index = $is_index ?? false;
$title = $title ?? null;

// Straight from the guard, not from the legacy session: a page that never boots FrontAccounting
// has nothing hydrated there, and this chrome is drawn on every page either way.
$user = auth()->user();

// The dashboard of the area this page is in, named outright: this leads to the figures for an area
// rather than to the area itself, so it does not go through whatever else opening an area means.
// With no area to be in, the home address is left to work that out.
$dashboard = ($area = $location->area()?->key()) === null
    ? legacy_url('index.php')
    : legacy_url('admin/dashboard.php', ['area' => $area]);

// Still the legacy session rather than the guard, and deliberately: what this gates is hotkey help,
// which only a FrontAccounting-booted request produces. A logged-in user is not the question.
$shouldShowFooter = !$no_menu && !$is_index && null !== session('wa_current_user');

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

// Handle footer data and Ajax if needed
if ($shouldShowFooter && isset($GLOBALS['Pagehelp']) && isset($GLOBALS['Ajax'])) {
    $help = implode('; ', $GLOBALS['Pagehelp']);
    $GLOBALS['Ajax']->addUpdate(true, 'hotkeyshelp', $help);
}
@endphp
<section
    class="main-container"
    @if (!$no_menu)
        x-data
        x-drawer
    @endif
>
    @if (!$no_menu)
    <!-- Mobile drawer backdrop -->
    <div x-drawer:backdrop x-transition.opacity x-cloak></div>

    <!-- Sidebar -->
    <aside class="main-sidebar" x-drawer:panel.left x-cloak>
        <div class="sidebar-inner">
            <h2 class="app-name">
                <img src="{{ url("/themes/default/images/logo.svg") }}" alt="Logo">
                taqbook <small><sub>ERP</sub></small>
            </h2>
            <x-nav.sidebar :navigation="$navigation" :location="$location" />
        </div>
    </aside>
    @endif

    <section class="main-section">
        @if(!$no_menu)
        <header class="main-header">
            <!-- Sidebar Minimize Button -->
            <button id="sidebar-toggle" class="me-2 bg-transparent border-0 cursor-pointer" aria-label="Toggle sidebar" x-drawer:trigger>
                <span class="icon icon-bars text-[2rem]"></span>
            </button>
            @if ($title && !$is_index)
            <h1 class="title">{{ $title }}</h1>
            @endif
            <div class="toolbar" x-dropdown>
                <button type="button" x-dropdown:trigger>
                    <span class="icon icon-circle-user text-[2rem]"></span>
                    <span class="hidden md:inline">{{ $user->real_name ?? '' }}</span>
                </button>

                <template x-teleport="body">
                    <ul x-dropdown:panel x-transition x-cloak>
                        <li class="x-dropdown-header">
                            <span class="icon icon-circle-user"></span>
                            <span class="x-dropdown-header-name">{{ $user->real_name ?? '' }}</span>
                        </li>
                        @foreach($toolbox as $key => $item)
                            <li>
                                @if (($item['method'] ?? 'get') === 'post')
                                    <form method="POST" action="{{ $item['link'] }}">
                                        @csrf
                                        <button type="submit" class="x-dropdown-item w-full text-left bg-transparent border-0 cursor-pointer">
                                            <span class="icon {{ $item['icon'] }}"></span>
                                            <span>{{ $item['label'] }}</span>
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ $item['link'] }}" class="x-dropdown-item">
                                        <span class="icon {{ $item['icon'] }}"></span>
                                        <span>{{ $item['label'] }}</span>
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </template>
            </div>
        </header>
        <x-nav.breadcrumbs :location="$location" />
        @endif

        <main class="main-content-area">
            <div data-loader-container><div data-loader="spinner"></div></div>
            <section class="main-content">

