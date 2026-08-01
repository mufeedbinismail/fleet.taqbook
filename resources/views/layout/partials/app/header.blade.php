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

// Define toolbox
$toolbox = [
    'dashboard' => [
        'link' => legacy_url("/admin/dashboard.php", ['sel_app' => $sel_app]),
        'icon' => 'icon-statistics',
        'label' => __('Dashboard')
    ],
    'preferences' => [
        'link' => legacy_url("/admin/display_prefs.php"),
        'icon' => 'icon-prefs',
        'label' => __('Preferences')
    ],
    'change_password' => [
        'link' => legacy_url("/admin/change_current_user_password.php", ['selected_id' => $user->username ?? '']),
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
@endphp
<section
    class="{{ conditional_join([
        'main-container',
        'has-header' => true,
        'has-sidebar' => !$no_menu,
        'has-footer' => $shouldShowFooter,
    ]) }}"
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
            <button id="sidebar-toggle" class="me-2 bg-transparent border-0 cursor-pointer" aria-label="Toggle sidebar" x-drawer:trigger>
                <span class="icon icon-bars text-[2rem]"></span>
            </button>
            @if ($title && !$is_index)
            <h1 class="title">{{ $title }}</h1>
            @endif
            <div class="toolbar" x-dropdown>
                <button type="button" x-dropdown:trigger>
                    <span class="icon icon-circle-user text-[2rem]"></span>
                    <span class="hidden md:inline">{{ $user->name ?? '' }}</span>
                </button>

                <template x-teleport="body">
                    <ul x-dropdown:panel x-transition x-cloak>
                        <li class="x-dropdown-header">
                            <span class="icon icon-circle-user"></span>
                            <span class="x-dropdown-header-name">{{ $user->name ?? '' }}</span>
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
        @endif

        <main class="main-content-area">
            <div data-loader-container><div data-loader="spinner"></div></div>
            <section class="main-content">

