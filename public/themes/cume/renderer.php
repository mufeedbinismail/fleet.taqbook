<?php
/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL, 
	as published by the Free Software Foundation, either version 3 
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/
require_once $GLOBALS['path_to_root'] . "/includes/date_functions.inc";

class renderer
{
    public function __construct() {
        $footerScripts = $this->footer_scripts();
        if (array_search($footerScripts, $GLOBALS['js_lib']) === false) {
            $GLOBALS['js_lib'][] = $footerScripts;
        }
    }

    function get_icon($category)
    {
        global  $path_to_root, $SysPrefs;

        if (!$SysPrefs->show_menu_category_icons) {
            return '';
        }

        $menuIconsMap = [
            'menu_entry' => 'icon-data',
            'menu_inquiry' => 'icon-view',
            'menu_maintenance' => 'icon-setup-master',
            'menu_report' => 'icon-reports',
            'menu_settings' => 'icon-setup-master',
            'menu_system' => 'icon-tools',
            'menu_transaction' => 'icon-feature',
            'menu_update' => 'icon-globe',
            'money' => 'icon-banknote',
        ];

        $icon = $menuIconsMap[$category] ?? 'icon-feature';
        return "<span class='cu-icon $icon'></span>&nbsp;&nbsp;";
    }

    function wa_header()
    {
        page(_($help_context = "Main Menu"), false, true);
    }

    function wa_footer()
    {
        end_page(false, true);
    }

    function menu_header($title, $no_menu, $is_index)
    {
        $this->layout($no_menu, $is_index, $title);
    }

    function menu_footer($no_menu, $is_index)
    {
        $this->layout($no_menu, $is_index, null, false);
    }

    function display_applications(&$waapp)
    {
        global $path_to_root;

        $selected_app = $waapp->get_selected_application();
        $user = $_SESSION["wa_current_user"];
        if (!$user->check_application_access($selected_app)) {
            return;
        }

        if (method_exists($selected_app, 'render_index')) {
            $selected_app->render_index();
            return;
        }

        echo "<div class='mx-auto p-4'>";
        foreach ($selected_app->modules as $module) {
            if (!$user->check_module_access($module)) {
                continue;
            }

            echo "<div class='bg-white text-primary-txt shadow-md rounded-lg mb-4'>";
            echo "<div class='bg-card-header-bg text-card-header-txt p-4 rounded-t-lg'>";
            echo "<h2 class='text-lg font-semibold'>" . $module->name . "</h2>";
            echo "</div>";
            echo "<div class='p-4 grid grid-cols-1 md:grid-cols-2 gap-4'>";

            echo "<div class='col-span-1'>";
            echo "<div class='p-4 grid grid-cols-1'>";
            foreach ($module->lappfunctions as $appfunction) {
                $img = $this->get_icon($appfunction->category);
                if ($appfunction->label == "") {
                    echo "<div class='col-span-1'>&nbsp;<br></div>";
                } elseif ($user->can_access_page($appfunction->access)) {
                    $access = access_string($appfunction->label);
                    echo "<div class='col-span-1 flex items-center'>";
                    echo $img . "<a href='" . $this->url($appfunction->link) . "' class='text-primary-txt hover:underline ml-2' {$access[1]}>{$access[0]}</a>";
                    echo "</div>";
                } elseif (!$user->hide_inaccessible_menu_items()) {
                    echo "<div class='col-span-1 flex items-center'>";
                    echo $img . "<span class='text-gray-500 ml-2'>" . access_string($appfunction->label, true) . "</span>";
                    echo "</div>";
                }
            }
            echo "</div>";
            echo "</div>";

            if (sizeof($module->rappfunctions) > 0) {
                echo "<div class='col-span-1'>";
                echo "<div class='p-4 grid grid-cols-1'>";
                foreach ($module->rappfunctions as $appfunction) {
                    $img = $this->get_icon($appfunction->category);
                    if ($appfunction->label == "") {
                        echo "<div class='col-span-1'>&nbsp;<br></div>";
                    } elseif ($user->can_access_page($appfunction->access)) {
                        $access = access_string($appfunction->label);
                        echo "<div class='col-span-1 flex items-center'>";
                        echo $img . "<a href='" . $this->url($appfunction->link) . "' class='text-primary-txt hover:underline ml-2' {$access[1]}>{$access[0]}</a>";
                        echo "</div>";
                    } elseif (!$user->hide_inaccessible_menu_items()) {
                        echo "<div class='col-span-1 flex items-center'>";
                        echo $img . "<span class='text-gray-500 ml-2'>" . access_string($appfunction->label, true) . "</span>";
                        echo "</div>";
                    }
                }
                echo "</div>";
                echo "</div>";
            }

            echo "</div>";
            echo "</div>";
        }
        echo "</div>";
    }

    protected function url($link) {
        global $path_to_root;

        if ($link[0] !=  '/') $link = '/' . $link;
        
        return $path_to_root . $link;
    }

    protected function shouldShowFooter($no_menu, $is_index) {
        return !$no_menu && !$is_index && isset($_SESSION['wa_current_user']);
    }

    protected function layout($no_menu, $is_index, $title = null, $is_opening = true) {
        global $path_to_root, $SysPrefs, $db_connections, $Pagehelp, $Ajax, $version;

        $applications = $_SESSION['App']->applications;
        $local_path_to_root = $path_to_root;
        $sel_app = $_SESSION['sel_app'];
        $user = $_SESSION["wa_current_user"];
        $shouldShowFooter = $this->shouldShowFooter($no_menu, $is_index);
        $sidebarCollapsed = ($_COOKIE['sidebar_collapsed'] ?? '0') === '1';
        $toolbox = [
            'dashboard' => [
                'link' => "$path_to_root/admin/dashboard.php?sel_app=$sel_app",
                'icon' => 'icon-statistics',
                'label' => _('Dashboard')
            ],
            'preferences' => [
                'link' => "$path_to_root/admin/display_prefs.php?",
                'icon' => 'icon-prefs',
                'label' => _('Preferences')
            ],
            'change_password' => [
                'link' => "$path_to_root/admin/change_current_user_password.php?selected_id=" . $user->username,
                'icon' => 'icon-security',
                'label' => _('Change password')
            ],
            'logout' => [
                'link' => "$local_path_to_root/access/logout.php?",
                'icon' => 'icon-logout',
                'label' => _('Logout')
            ]
        ];

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

        if ($shouldShowFooter) {
            $help = implode('; ', $Pagehelp);
            $Ajax->addUpdate(true, 'hotkeyshelp', $help);
        }

        $indicator = "$path_to_root/themes/".user_theme(). "/images/ajax-loader.gif";

        if ($is_opening): ?>
        <section class="<?= $this->class_names([
            'main-container',
            'has-header' => true,
            'has-sidebar' => !$no_menu,
            'has-footer' => $shouldShowFooter,
            'sidebar-collapsed' => !$no_menu && $sidebarCollapsed
        ]) ?>">
            <?php if (!$no_menu) : ?>
            <!-- Sidebar -->
            <aside class="main-sidebar">
                <div class="sidebar-inner">
                    <h2 class="app-name">
                        <img src="<?= "$path_to_root/themes/cume/images/logo.svg" ?>" alt="Logo">
                        taqbook <small><sub>ERP</sub></small>
                    </h2>
                    <nav>
                        <ul>
                            <?php foreach($applications as $app):
                                if ($user->check_application_access($app)):
                                    $acc = access_string($app->name); ?>
                                    <li class="main-nav-item <?= $sel_app == $app->id ? 'selected' : '' ?>">
                                        <span class="cu-icon pe-2 <?= $appIcons[$app->id] ?? 'icon-spacer' ?>"></span>
                                        <?= "<a href='{$local_path_to_root}/index.php?application={$app->id}' {$acc[1]}>{$acc[0]}</a>" ?>
                                    </li>
                                <?php endif;
                            endforeach; ?>
                        </ul>
                    </nav>
                </div>
            </aside>
            <?php endif; ?>

            <section class="main-section">
                <?php if(!$no_menu): ?>
                <header class="main-header">
                    <!-- Sidebar Minimize Button -->
                    <button id="sidebar-toggle" class="sidebar-toggle-btn me-2 bg-transparent w-[25px] h-[25px] border-0 text-lg pb-0" aria-label="Toggle sidebar" type="button">
                        <span class="cu-icon icon-bars"></span>
                    </button>
                    <?php if ($title && !$is_index) : ?>
                    <h1 class="title"><?= $title ?></h1>
                    <?php endif; ?>
                    <ul class="toolbar">
                    <?php foreach($toolbox as $key => $item) : ?>
                        <li class="toolbar-item">
                            <a href="<?= $item['link'] ?>">
                                <span class="cu-icon <?= $item['icon'] ?>"></span>
                                <span><?= $item['label'] ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                </header>
                <?php endif; ?>

                <main class="main-content-area">
                    <img class="hidden" id='ajaxmark' src='<?= $indicator ?>' style='visibility:hidden;' alt='ajaxmark'></img>
                    <div data-loader-container><div data-loader></div></div>
                    <section class="main-content">
        <?php else: ?>
                    <section>
                </main>
                <?php if ($shouldShowFooter) : ?>
                <footer class="main-footer">
                    <div><?= ($_SERVER['HTTP_HOST'] . " | " . Today() . " | " . Now()) ?></div>
                    <div>
                        <span id="hotkeyshelp"><?= $help ?></span>
                    </div>
                </footer>
                <?php endif; ?>
            </section>
        </section>
        <?php endif;
    }

    protected function footer_scripts() {
        global $path_to_root;
        
        $ret = "\n--></script>"
             . "\n<script type='module' src='{$path_to_root}/themes/cume/build/cume.js'></script>"
             . "\n<script type='text/javascript'><!--\n";
        
        return $ret;
    }

    protected function class_names(array $classes) {
        $_classes = array_filter($classes);

        foreach ($classes as $class => $condition) {
            if (is_int($class)) {
                $_classes[] = $condition;
            } else if ($condition) {
                $_classes[] = $class;
            }
        }
        
        return implode(' ', array_unique($_classes));
    }
}
