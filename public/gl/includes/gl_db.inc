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
require_once __DIR__ . "/../../includes/banking.inc";

require_once __DIR__ . "/../../gl/includes/db/gl_db_trans.inc";
require_once __DIR__ . "/../../gl/includes/db/gl_journal.inc";
require_once __DIR__ . "/../../gl/includes/db/gl_db_bank_trans.inc";
require_once __DIR__ . "/../../gl/includes/db/gl_db_banking.inc";
require_once __DIR__ . "/../../gl/includes/db/gl_db_bank_accounts.inc";
require_once __DIR__ . "/../../gl/includes/db/gl_db_currencies.inc";
require_once __DIR__ . "/../../gl/includes/db/gl_db_rates.inc";
require_once __DIR__ . "/../../gl/includes/db/gl_db_accounts.inc";
require_once __DIR__ . "/../../gl/includes/db/gl_db_account_types.inc";
//--------------------------------------------------------------------------------
