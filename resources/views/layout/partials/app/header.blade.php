@php
// Set defaults from passed variables or session/globals
$no_menu = $no_menu ?? false;
$is_index = $is_index ?? false;
$title = $title ?? null;

// Access session data
$applications = session('App')->applications ?? [];
$sel_app = session('sel_app') ?? null;
$user = session('wa_current_user') ?? null;

// Calculate derived values
$shouldShowFooter = !$no_menu && !$is_index && null !== session('wa_current_user');
$sidebarCollapsed = ($_COOKIE['sidebar_collapsed'] ?? '0') === '1';

// Define toolbox
$toolbox = [
    'dashboard' => [
        'link' => url("/admin/dashboard.php", ['sel_app' => $sel_app]),
        'icon' => 'icon-statistics',
        'label' => __('Dashboard')
    ],
    'preferences' => [
        'link' => url("/admin/display_prefs.php"),
        'icon' => 'icon-prefs',
        'label' => __('Preferences')
    ],
    'change_password' => [
        'link' => url("/admin/change_current_user_password.php", ['selected_id' => $user->username ?? '']),
        'icon' => 'icon-security',
        'label' => __('Change password')
    ],
    'logout' => [
        'link' => url("/access/logout.php"),
        'icon' => 'icon-logout',
        'label' => __('Logout')
    ]
];

// Define application icons
$appIcons = [
    "orders" => "icon-storefront",
    "mp_orders" => "icon-storefront",
    "AP" => "icon-procurement",
    "GL" => "icon-accountant",
    "stock" => "icon-inventory",
    "system" => "icon-settings",
    "manuf" => "icon-manufacturing",
    "assets" => "icon-fixed-assets",
    "proj" => "icon-dimension"
];

// Handle footer data and Ajax if needed
if ($shouldShowFooter && isset($GLOBALS['Pagehelp']) && isset($GLOBALS['Ajax'])) {
    $help = implode('; ', $GLOBALS['Pagehelp']);
    $GLOBALS['Ajax']->addUpdate(true, 'hotkeyshelp', $help);
}

// Ajax indicator
$indicator = url("themes/".user_theme()."/images/ajax-loader.gif");
@endphp
<section class="{{ conditional_join([
    'main-container',
    'has-header' => true,
    'has-sidebar' => !$no_menu,
    'has-footer' => $shouldShowFooter,
    'sidebar-collapsed' => !$no_menu && $sidebarCollapsed
]) }}">
    @if (!$no_menu)
    <!-- Sidebar -->
    <aside class="main-sidebar">
        <div class="sidebar-inner">
            <h2 class="app-name">
                <img src="{{ url("/themes/default/images/logo.svg") }}" alt="Logo">
                taqbook <small><sub>ERP</sub></small>
            </h2>
            <nav>
                <ul>
                    @foreach($applications as $app)
                        @if ($user && $user->check_application_access($app))
                            @php $acc = access_string($app->name); @endphp
                            <li class="main-nav-item {{ $sel_app == $app->id ? 'selected' : '' }}">
                                <span class="icon pe-2 {{ $appIcons[$app->id] ?? 'icon-spacer' }}"></span>
                                {!! "<a href='" . url("/index.php", ['application' => $app->id]) . "' {$acc[1]}>{$acc[0]}</a>" !!}
                            </li>
                        @endif
                    @endforeach
                </ul>
            </nav>
        </div>
    </aside>
    @endif

    <section class="main-section">
        @if(!$no_menu)
        <header class="main-header">
            <!-- Sidebar Minimize Button -->
            <button id="sidebar-toggle" class="sidebar-toggle-btn me-2 bg-transparent w-[25px] h-[25px] border-0 text-lg pb-0" aria-label="Toggle sidebar" type="button">
                <span class="icon icon-bars"></span>
            </button>
            @if ($title && !$is_index)
            <h1 class="title">{{ $title }}</h1>
            @endif
            <ul class="toolbar">
            @foreach($toolbox as $key => $item)
                <li class="toolbar-item">
                    <a href="{{ $item['link'] }}">
                        <span class="icon {{ $item['icon'] }}"></span>
                        <span>{{ $item['label'] }}</span>
                    </a>
                </li>
            @endforeach
            </ul>
        </header>
        @endif

        <main class="main-content-area">
            <img class="hidden" id='ajaxmark' src='{{ $indicator }}' style='visibility:hidden;' alt='ajaxmark'></img>
            <div data-loader-container><div data-loader></div></div>
            <section class="main-content">

