<?php

use App\Foundation\Shared\Setting\UserSetting;

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

class user_prefs
{
    protected UserSetting $repo;

	function __construct($user = null)
	{
		$this->repo = user_settings()->setUser($user);
	}

    public function repo(): UserSetting
    {
        return $this->repo;
    }
	
	function get_all()
	{
		return $this->repo->all();
	}
	
	function language() 
	{
		return $this->repo->locale();
	}

	function qty_dec() 
	{
		return $this->repo->quantityDecimals();
	}

	function price_dec() 
	{
		return $this->repo->priceDecimals();
	}

	function exrate_dec() 
	{
		return $this->repo->exchangeRateDecimals();
	}

	function percent_dec() 
	{
		return $this->repo->percentDecimals();
	}

	function show_gl_info() 
	{
		return $this->repo->enableViewGlLinks();
	}

	function show_codes() 
	{
		return $this->repo->showItemCodesAlso();
	}

	function date_format() 
	{
		return $this->repo->dateFormatIdx()->value;
	}

	function date_sep() 
	{
		return $this->repo->dateSepIdx()->value;
	}

	function date_display() 
	{
		return $this->repo->dateFormat();
	}

	function tho_sep() 
	{
		return $this->repo->thousandSepIdx()->value;
	}

	function dec_sep() 
	{
		return $this->repo->decimalSepIdx()->value;
	}

	function get_theme() 
	{
		return $this->repo->theme();
	}

	function get_pagesize() 
	{
		return $this->repo->pageSizeIdx()->value;
	}

	function show_hints() 
	{
		return $this->repo->showHints();
	}

	function print_profile() 
	{
		return $this->repo->printProfileName();
	}

	function rep_popup() 
	{
		return $this->repo->showReportAsPopup();
	}

	function query_size() 
	{
		return $this->repo->rowsPerPage();
	}

	function graphic_links() 
	{
		return $this->repo->useIconsInLinks();
	}
	
	function sticky_date()
	{
		return $this->repo->useStickyDocDate();
	}
	
	function start_up_tab()
	{
		return $this->repo->startupTab();
	}

    function transaction_days() 
    {
        return $this->repo->transactionDays();
    }

    function save_report_selections() 
    {
        return $this->repo->reportSelectionRetentionDays();
    }

    function use_date_picker() 
    {
        return $this->repo->useDatePicker();
    }

    function def_print_destination() 
    {
        return $this->repo->printDestinationIdx()->value;
    }

    function def_print_orientation() 
    {
        return $this->repo->printOrientationIdx()->value;
    }

	function set_dec($price_dec, $qty_dec, $exrate_dec, $percent_dec, $showgl, $showcodes) 
	{
        $this->repo->set([
            'prices_dec' => $price_dec,
            'qty_dec' => $qty_dec,
            'rates_dec' => $exrate_dec,
            'percent_dec' => $percent_dec,
            'show_gl' => $showgl,
            'show_codes' => $showcodes,
        ]);
	}

	function set_format($date_format, $date_sep, $tho_sep, $dec_sep, $theme, $pagesize) 
	{
		$this->repo->set([
            'date_format' => $date_format,
            'date_sep' => $date_sep,
            'tho_sep' => $tho_sep,
            'dec_sep' => $dec_sep,
            'theme' => $theme,
            'page_size' => $pagesize,
        ]);
	}

    public function __get($name)
    {
        return $this->repo->get($this->normalizeProperty($name), null);
    }

    public function __set($name, $value)
    {
        $this->repo->set($this->normalizeProperty($name), $value);
    }

    public function __isset($name)
    {
        return $this->repo->has($this->normalizeProperty($name));
    }

    public function normalizeProperty($name)
    {
        return [
            'price_dec' => 'prices_dec',
            'exrate_dec' => 'rates_dec',
            'show_gl_info' => 'show_gl',
            'pagesize' => 'page_size',
            'sticky_date' => 'sticky_doc_date',
        ][$name] ?? $name;
    }
}

