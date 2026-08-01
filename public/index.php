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

use App\Navigation\Facade\Navigation;

$GLOBALS['page_security'] = 'SA_OPEN';
ini_set('xdebug.auto_trace',1);
include_once("includes/session.inc");

// Nothing is left for this address to show itself. What it does instead is answer, for the
// whole application, what opening an area means — so no view drawing an area has to know, and
// there is one place to change when the answer changes.
$navigation = Navigation::tree();

// Which area was asked for, then which one the user asked to land on. Each is a key that may
// name nothing at all, or something that is not an area, so both are tried the same way and a
// link naming somewhere the user has since lost access to falls through like an absent one.
$area = null;

foreach ([request()->query('area'), user_startup_tab()] as $key)
{
    $candidate = $key === null ? null : $navigation->find($key);

    if ($candidate !== null && $candidate->isArea())
    {
        $area = $candidate;
        break;
    }
}

if ($area === null)
    $area = $navigation->areas()->first();

if ($area === null)
    // A user with no area at all is left without a mode, which is the only honest thing to
    // send when there is nowhere to name.
    $destination = legacy_url('admin/dashboard.php');
elseif (config('navigation.area_index'))
    $destination = route('navigation.area-index', $area->key());
else
    $destination = legacy_url('admin/dashboard.php', ['area' => $area->key()]);

// Handed over before anything is rendered rather than by a refresh tag part-way down the page,
// so nothing is painted in between.
header('Location: ' . $destination);
exit;
