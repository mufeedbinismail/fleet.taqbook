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
require_once __DIR__ . "/../../includes/date_functions.inc";

class renderer
{
    function get_icon($category)
    {
        global $SysPrefs;

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
        return "<span class='icon $icon'></span>&nbsp;&nbsp;";
    }

    function wa_header()
    {
        page(__($GLOBALS['help_context'] = "Main Menu"), false, true);
    }

    function wa_footer()
    {
        end_page(false, true);
    }

    function menu_header($title, $no_menu, $is_index)
    {
        echo view('layout.partials.app.header', [
            'title' => $title,
            'no_menu' => $no_menu,
            'is_index' => $is_index
        ])->render();
    }

    function menu_footer($no_menu, $is_index)
    {
        echo view('layout.partials.app.footer', [
            'no_menu' => $no_menu,
            'is_index' => $is_index
        ])->render();
    }

    function display_applications(&$waapp)
    {
        $selected_app = $waapp->get_selected_application();
        $user = $_SESSION["wa_current_user"];
        if (!$user->check_application_access($selected_app)) {
            return;
        }

        if (method_exists($selected_app, 'render_index')) {
            $selected_app->render_index();
            return;
        }

        echo "<div class='mx-auto p-2 md:p-4'>";
        foreach ($selected_app->modules as $module) {
            if (!$user->check_module_access($module)) {
                continue;
            }

            echo "<div class='bg-white text-primary-txt shadow-md rounded-lg mb-4'>";
            echo "<div class='bg-card-header-bg text-card-header-txt p-2 md:p-4 rounded-t-lg'>";
            echo "<h2 class='text-lg font-semibold'>" . $module->name . "</h2>";
            echo "</div>";
            echo "<div class='p-2 md:p-4 grid grid-cols-1 md:grid-cols-2 gap-4'>";

            echo "<div class='col-span-1'>";
            echo "<div class='p-2 md:p-4 grid grid-cols-1'>";
            foreach ($module->lappfunctions as $appfunction) {
                $img = $this->get_icon($appfunction->category);
                if ($appfunction->label == "") {
                    echo "<div class='col-span-1'>&nbsp;<br></div>";
                } elseif ($user->can_access_page($appfunction->access)) {
                    $access = access_string($appfunction->label);
                    echo "<div class='col-span-1 flex items-center'>";
                    echo $img . "<a href='" . url($appfunction->link) . "' class='text-primary-txt hover:underline ml-2' {$access[1]}>{$access[0]}</a>";
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
                echo "<div class='p-2 md:p-4 grid grid-cols-1'>";
                foreach ($module->rappfunctions as $appfunction) {
                    $img = $this->get_icon($appfunction->category);
                    if ($appfunction->label == "") {
                        echo "<div class='col-span-1'>&nbsp;<br></div>";
                    } elseif ($user->can_access_page($appfunction->access)) {
                        $access = access_string($appfunction->label);
                        echo "<div class='col-span-1 flex items-center'>";
                        echo $img . "<a href='" . url($appfunction->link) . "' class='text-primary-txt hover:underline ml-2' {$access[1]}>{$access[0]}</a>";
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
}
