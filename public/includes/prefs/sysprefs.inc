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

use App\Foundation\Setting\SettingRepository;

require_once __DIR__ . "/../../admin/db/company_db.inc";

/**
 * System and per-company preferences.
 *
 * The object is created only with site-wide preferences. After user login, the `refresh` method
 * must be called to re-initialize company-specific settings.
 *
 * Properties are initialized from config('legacy.*') when Laravel's config() is available.
 */
class sys_prefs extends stdClass
{
	public SettingRepository $prefs;

	public string $error_logfile;
	public int $debug;
	public int $show_sql;
	public int $go_debug;
	public int $pdf_debug;
	public int $sql_trail;
	public int $select_trail;
	public string $app_title;
	public string $build_version;
	public string $power_by;
	public string $power_url;
	public int $no_check_edit_conflicts;
	public int $use_icon_for_editkey;
	public int $auto_create_branch;
	public int $use_popup_windows;
	public int $use_audit_trail;
	public int $use_oldstyle_convert;
	public int $show_users_online;
	public int $old_style_help;
	public string $help_base_url;
	public string $comp_path;
	public int $date_system;
	public int $allow_gl_reopen;
	public array $dateformats;
	public array $dateseps;
	public array $thoseps;
	public array $decseps;
	public int $dflt_date_fmt;
	public int $dflt_date_sep;
	public array $pagesizes;
	public bool $check_qty_charged_vs_del_qty;
	public bool $check_price_charged_vs_order_price;
	public float $config_allocation_settled_allowance;
	public int $use_costed_values;
	public int $show_menu_category_icons;
	public bool $allow_demo_mode;
	public bool $allow_password_reset;
	public int $pic_width;
	public int $pic_height;
	public int $max_image_size;
	public int $graph_skin;
	public string $UTF8_fontfile;
	public bool $text_company_selection;
	public int $hide_inaccessible_menu_items;
	public int $login_delay;
	public int $login_max_attempts;
	public array $xr_providers;
	public int $dflt_xr_provider;
	public bool $xr_provider_authoritative;
	public bool $sort_sales_items;
	public bool $clear_trial_balance_opening;
	public bool $use_popup_search;
	public int $max_rows_in_search;
	public string $time_zone;

	function __construct()
	{
        $this->error_logfile                 = config('legacy.error_logfile');
        $this->debug                         = config('legacy.debug');
        $this->show_sql                      = config('legacy.show_sql');
        $this->go_debug                      = config('legacy.go_debug');
        $this->pdf_debug                     = config('legacy.pdf_debug');
        $this->sql_trail                     = config('legacy.sql_trail');
        $this->select_trail                  = config('legacy.select_trail');
        $this->app_title                     = config('app.name');
        $this->build_version                 = config('legacy.build_version');
        $this->power_by                      = config('app.name');
        $this->power_url                     = config('legacy.power_url');
        $this->no_check_edit_conflicts       = config('legacy.no_check_edit_conflicts');
        $this->use_icon_for_editkey          = config('legacy.use_icon_for_editkey');
        $this->auto_create_branch            = config('legacy.auto_create_branch');
        $this->use_popup_windows             = config('legacy.use_popup_windows');
        $this->use_audit_trail               = config('legacy.use_audit_trail');
        $this->use_oldstyle_convert          = config('legacy.use_oldstyle_convert');
        $this->show_users_online             = config('legacy.show_users_online');
        $this->old_style_help                = config('legacy.old_style_help');
        $this->help_base_url                 = config('legacy.help_base_url');
        $this->comp_path                     = config('legacy.comp_path');
        $this->date_system                   = config('legacy.date_system');
        $this->allow_gl_reopen               = config('legacy.allow_gl_reopen');
        $this->dateformats                   = config('legacy.dateformats');
        $this->dateseps                      = config('legacy.dateseps');
        $this->thoseps                       = config('legacy.thoseps');
        $this->decseps                       = config('legacy.decseps');
        $this->dflt_date_fmt                 = config('legacy.dflt_date_fmt');
        $this->dflt_date_sep                 = config('legacy.dflt_date_sep');
        $this->pagesizes                     = config('legacy.pagesizes');
        $this->check_qty_charged_vs_del_qty        = config('legacy.check_qty_charged_vs_del_qty');
        $this->check_price_charged_vs_order_price  = config('legacy.check_price_charged_vs_order_price');
        $this->config_allocation_settled_allowance = config('legacy.config_allocation_settled_allowance');
        $this->use_costed_values             = config('legacy.use_costed_values');
        $this->show_menu_category_icons      = config('legacy.show_menu_category_icons');
        $this->allow_demo_mode               = config('legacy.allow_demo_mode');
        $this->allow_password_reset          = config('legacy.allow_password_reset');
        $this->pic_width                     = config('legacy.pic_width');
        $this->pic_height                    = config('legacy.pic_height');
        $this->max_image_size                = config('legacy.max_image_size');
        $this->graph_skin                    = config('legacy.graph_skin');
        $this->UTF8_fontfile                 = language()->isRtl()
                                                ? config('legacy.utf8_fontfile_rtl') 
                                                : config('legacy.UTF8_fontfile');
        $this->text_company_selection        = config('legacy.text_company_selection');
        $this->hide_inaccessible_menu_items  = config('legacy.hide_inaccessible_menu_items');
        $this->login_delay                   = config('legacy.login_delay');
        $this->login_max_attempts            = config('legacy.login_max_attempts');
        $this->xr_providers                  = config('legacy.xr_providers');
        $this->dflt_xr_provider              = config('legacy.dflt_xr_provider');
        $this->xr_provider_authoritative     = config('legacy.xr_provider_authoritative');
        $this->sort_sales_items              = config('legacy.sort_sales_items');
        $this->clear_trial_balance_opening   = config('legacy.clear_trial_balance_opening');
        $this->use_popup_search              = config('legacy.use_popup_search');
        $this->max_rows_in_search            = config('legacy.max_rows_in_search');
        $this->time_zone                     = config('app.timezone');

		$this->prefs = settings();
	}

	/**
	 * Re-initialize company preferences.
	 */
	function refresh()
	{
		settings()->refresh();
	}

	function allow_negative_stock() 
	{
		return $this->prefs['allow_negative_stock'];
	}

    /**
     * Proportion by which items can be received over the quantity specified in a purchase invoice.
     *
     * The figure entered is interpreted as a percentage, i.e. 10 means 0.1 or 10%, not 10 times.
     *
     * @author Sherifoz
     * @since 26.06.03
     */
	function over_receive_allowance() 
	{
		return $this->prefs['po_over_receive'];
	}
	
    /**
     * Proportion by which a purchase invoice line is an overcharge for a purchase order item received.
     *
     * If the overcharge is more than this percentage, an error is reported and
     * the purchase invoice line cannot be entered.
     * 
     * The figure entered is interpreted as a percentage, i.e. 20 means 0.2 or 20%, not 20 times.
     */
	function over_charge_allowance() 
	{
		return $this->prefs['po_over_charge'];
	}
	
	function default_credit_limit() 
	{
		return $this->prefs['default_credit_limit'];
	}				
	
	function default_wo_required_by() 
	{
		return $this->prefs['default_workorder_required'];
	}

	function default_quote_valid_days() 
	{
		return $this->prefs['default_quote_valid_days'];
	}

	function default_delivery_required_by() 
	{
		return $this->prefs['default_delivery_required'];
	}

	function default_receival_required_by() 
	{
		return $this->prefs['default_receival_required'];
	}

	function default_dimension_required_by() 
	{
		return $this->prefs['default_dim_required'];
	}	
	
	function auto_currency_revaluation() 
	{
		return $this->prefs['auto_curr_reval'];
	}	
	
	function allocation_settled_allowance()
	{
		return $this->config_allocation_settled_allowance;
	}

	function no_zero_lines_amount() 
	{
		return $this->prefs['no_zero_lines_amount'];
	}

	function show_po_item_codes() 
	{
		return $this->prefs['show_po_item_codes'];
	}

	function accounts_alpha() 
	{
		return $this->prefs['accounts_alpha'];
	}

	function loc_notification() 
	{
		return $this->prefs['loc_notification'];
	}

	function print_invoice_no() 
	{
		return $this->prefs['print_invoice_no'];
	}

	function allow_negative_prices() 
	{
		return $this->prefs['allow_negative_prices'];
	}

	function print_item_images_on_quote() 
	{
		return $this->prefs['print_item_images_on_quote'];
	}

	function alternative_tax_include_on_docs() 
	{
		return $this->prefs['alternative_tax_include_on_docs'];
	}

	function suppress_tax_rates() 
	{
		return $this->prefs['suppress_tax_rates'];
	}

	function backup_dir($comp=null)
	{
		if (!isset($comp))
			$comp = user_company();

		if (isset($this->backup_path))
			return sprintf($this->backup_path, $comp);
		else
			return $this->comp_path.'/'.$comp."/backup/";
	}
}

