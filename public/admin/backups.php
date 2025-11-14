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
$GLOBALS['page_security'] = 'SA_BACKUP';

require_once __DIR__ . "/../includes/session.inc";
require_once __DIR__ . "/../includes/ui.inc";
require_once __DIR__ . "/../admin/db/maintenance_db.inc";

if (get_post('view')) {
	if (!get_post('backups')) {
		display_error(__('Select backup file first.'));
	} else {
		$filename = $SysPrefs->backup_dir() . clean_file_name(get_post('backups'));
		if (in_ajax()) 
			$Ajax->popup( $filename );
		else {
            throw new \App\Exceptions\Legacy\FileStreamException($filename);
		}
	}
};

if (get_post('download')) {
	if (get_post('backups')) {
		download_file($SysPrefs->backup_dir().clean_file_name(get_post('backups')));
	} else
		display_error(__("Select backup file first."));
}

page(__($GLOBALS['help_context'] = "Backup Database"), false, false, '', '');

check_paths();

function check_paths()
{
  global $SysPrefs;

	if (!file_exists($SysPrefs->backup_dir())) {
		display_error (__("Backup paths have not been set correctly.") 
			.__("Please contact System Administrator.")."<br>" 
			. __("cannot find backup directory") . " - " . $SysPrefs->backup_dir() . "<br>");
		end_page();
		throw new \App\Exceptions\Legacy\FlowTerminatedException;
	}
}

function generate_backup($conn, $ext='no', $comm='')
{
	global $SysPrefs;

	$filename = db_backup($conn, $ext, $comm, $SysPrefs->backup_dir());
	if ($filename)
		display_notification(__("Backup successfully generated."). ' '
			. __("Filename") . ": " . $filename);
	else
		display_error(__("Database backup failed."));

	return $filename;
}


function get_backup_file_combo()
{
	global $Ajax, $SysPrefs;
	
	$ar_files = array();
    default_focus('backups');
    $dh = opendir($SysPrefs->backup_dir());
	while (($file = readdir($dh)) !== false)
		$ar_files[] = $file;
	closedir($dh);

    rsort($ar_files);
	$opt_files = "";
    foreach ($ar_files as $file)
		if (preg_match("/.sql(.zip|.gz)?$/", $file))
    		$opt_files .= "<option value='$file'>$file</option>";

	$selector = "<select name='backups' size=2 style='height:160px;min-width:230px'>$opt_files</select>";

	$Ajax->addUpdate('backups', "_backups_sel", $selector);
	$selector = "<span id='_backups_sel'>".$selector."</span>\n";

	return $selector;
}

function compress_list_row($label, $name, $value=null)
{
	$ar_comps = array('no'=>__("No"));

    if (function_exists("gzcompress"))
    	$ar_comps['zip'] = "zip";
    if (function_exists("gzopen"))
    	$ar_comps['gzip'] = "gzip";

	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector('comp', $value, $ar_comps);
	echo "</td></tr>";
}

function download_file($filename)
{
    if (empty($filename) || !file_exists($filename))
    {
		display_error(__('Select backup file first.'));
        throw new \App\Exceptions\Legacy\FlowTerminatedException;
    }
    
    $saveasname = basename($filename);
    throw new \App\Exceptions\Legacy\FileDownloadException(
        $filename,
        $saveasname
    );
}

$conn = $db_connections[user_company()];
$backup_name = clean_file_name(get_post('backups'));
$backup_path = $SysPrefs->backup_dir() . $backup_name;

if (get_post('creat')) {
	generate_backup($conn, get_post('comp'), get_post('comments'));
	$Ajax->activate('backups');
	$SysPrefs->refresh(); // re-read system setup
};

if (get_post('deldump')) {
	if ($backup_name) {
		if (unlink($backup_path)) {
			display_notification(__("File successfully deleted.")." "
					. __("Filename") . ": " . $backup_name);
			$Ajax->activate('backups');
		}
		else
			display_error(__("Can't delete backup file."));
	} else
		display_error(__("Select backup file first."));
}
//-------------------------------------------------------------------------------
start_form(true, true);
start_outer_table(TABLESTYLE2);
table_section(1);
table_section_title(__("Create backup"));
	textarea_row(__("Comments:"), 'comments', null, 30, 8);
	compress_list_row(__("Compression:"),'comp');
	vertical_space("height='20px'");
	submit_row('creat',__("Create Backup"), false, "colspan=2 align='center'", '', 'process');
table_section(2);
table_section_title(__("Backup scripts maintenance"));

	start_row();
	echo "<td style='padding-left:20px' align='left'>".get_backup_file_combo()."</td>";
	echo "<td style='padding-left:20px' valign='top'>";
	start_table();
	submit_row('view',__("View Backup"), false, '', '', false);
	submit_row('download',__("Download Backup"), false, '', '', 'download');
	submit_row('deldump', __("Delete Backup"), false, '','', true);
	// don't use 'delete' name or IE js errors appear
	submit_js_confirm('deldump', sprintf(__("You are about to remove selected backup file.\nDo you want to continue ?")));
	end_table();
	echo "</td>";
	end_row();
end_outer_table();

end_form();

end_page();
