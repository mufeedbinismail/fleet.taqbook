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
$GLOBALS['messages']   = array(); // container for system messages
$GLOBALS['before_box'] = '';      // temporary container for output html data before error box

#
#   Wrapper for trigger_error
#
function fa_trigger_error($msg, $error_level=E_USER_NOTICE)
{
    error_handler($error_level, $msg, __FILE__, __LINE__);
}

function get_backtrace($html = false, $skip=0, $trace=null)
{
	$trace ??= debug_backtrace();

	if (!$html) {
		$lines = [];
		foreach ($trace as $i => $frame) {
			if ($i < $skip) continue;
			$location = isset($frame['file']) ? "{$frame['file']}:{$frame['line']}" : '[internal]';
			$call = isset($frame['class'])
				? $frame['class'] . $frame['type'] . $frame['function']
				: $frame['function'];
			$lines[] = "#{$i} {$location} {$call}()";
		}
		return implode("\n", $lines);
	}

	$root = function_exists('base_path') ? base_path() . '/' : '';

	$frames = [];

	foreach ($trace as $i => $frame) {
		if ($i < $skip) continue;

		$file      = $frame['file'] ?? null;
		$shortFile = $file === null ? null : ltrim(str_replace($root, '', $file), '/');

		$frames[] = [
			'index'  => $i,
			// Kept rather than filtered: with the hops between them removed, the app's own frames
			// read as if they called each other directly.
			'vendor' => $file !== null && str_contains($file, '/vendor/'),
			'dir'    => $shortFile === null ? null : dirname($shortFile),
			'file'   => $shortFile === null ? null : basename($shortFile),
			'line'   => $frame['line'] ?? null,
			'call'   => isset($frame['class'])
				? $frame['class'] . $frame['type'] . $frame['function']
				: $frame['function'],
		];
	}

	// Grouped by run so a stretch of vendor frames can be folded as one.
	$groups = [];

	foreach ($frames as $frame) {
		$last = count($groups) - 1;

		if ($last >= 0 && $groups[$last]['vendor'] === $frame['vendor']) {
			$groups[$last]['frames'][] = $frame;
			continue;
		}

		$groups[] = ['vendor' => $frame['vendor'], 'frames' => [$frame]];
	}

	// The markup lives under resources/views rather than here because Tailwind only scans that
	// tree: a class written in this file would be purged from the build and render unstyled.
	return view('errors.partials.backtrace', ['groups' => $groups])->render();
}

//-----------------------------------------------------------------------------
//    Error handler - collects all php/user messages for
//    display in message box.

function error_handler($errno, $errstr, $file, $line, $trace=null) {
    global $messages, $SysPrefs, $cur_error_level;

	// skip well known warnings we don't care about.
	// Please use restrainedly to not risk loss of important messages
	$excluded_warnings = array(
		'html_entity_decode', 				// nevermind encodings, special chars are processed anyway
		'should be compatible with that',	// ignore cpdf/frontreport wrapper warnings
		'mysql extension is deprecated'		// ignore strict warning in 5.4
	);
	foreach($excluded_warnings as $ref) {
		if (strpos($errstr, $ref) !== false) {
			return true;
		}
	}

	$bt = isset($SysPrefs) && $SysPrefs->go_debug>1 ? get_backtrace(true, 0, $trace) : array();

	// error_reporting!=cur_error_level when messages are set off with @ 
	if ($cur_error_level == error_reporting()) {
		if ($errno & $cur_error_level) {
			// suppress duplicated errors
			if (!in_array(array($errno, $errstr, $file, $line, @$bt), $messages))
				$messages[] = array($errno, $errstr, $file, $line, @$bt);
		}
		else if ($errno&~E_NOTICE && $errstr != '') { // log all not displayed messages 
			$user = @$_SESSION["wa_current_user"]->loginname;
			error_log(user_company() . ":$user:". basename($file) .":$line: $errstr");
		}
	}
	
    return true;
}

function exception_handler($exception)
{
	error_handler(E_ERROR, sprintf(__("Unhandled exception [%s]: %s."), $exception->getCode(), $exception->getMessage()),
		 $exception->getFile(), $exception->getLine(), $exception->getTrace());
	end_page();
}
//------------------------------------------------------------------------------
//	Formats system messages before insert them into message <div>
// FIX center is unused now
function fmt_errors($center=false) {
    global $messages, $SysPrefs;

  	$msg_class = array(
  		E_USER_ERROR => 'err_msg',
  		E_USER_WARNING =>'warn_msg', 
		E_USER_NOTICE => 'note_msg');

  	$type = E_USER_NOTICE;
  	$content = '';

  	if (count($messages)) {
		foreach($messages as $cnt=>$msg) {
			if ($SysPrefs->go_debug && $msg[0]>E_USER_NOTICE)
		 		$msg[0] = E_ERROR;

			if ($msg[0]>$type) continue;

			if ($msg[0]<$type) { 
				if ($msg[0] == E_USER_WARNING) {
					$type = E_USER_WARNING; // user warnings 
					$content = ''; 			// clean notices when we have errors
				} else  {
					$type = E_USER_ERROR; 	// php or user errors 
					if($type == E_USER_WARNING)
						$content = ''; 			// clean other messages
				}
			}

	    	$str = $msg[1];
			if (!in_array($msg[0], array(E_USER_NOTICE, E_USER_ERROR, E_USER_WARNING)) && $msg[2] != null)
		  		$str .= ' '.__('in file').': '.$msg[2].' '.__('at line ').$msg[3];

			if ($SysPrefs->go_debug>1 && $type!=E_USER_NOTICE && $type!=E_USER_WARNING)
		  		$str .= '<br>'.$msg[4];
			$content .= ($cnt ? '<hr>' : '').$str;
		}
		$class = $msg_class[$type];
    	$content = "<div class='$class'>$content</div>";
  	}
    
  	return $content;
}
//-----------------------------------------------------------------------------
// Error box <div> element.
//
function error_box() {
    global $before_box;
    
    echo "<div id='msgbox'>";

	// Necessary restart instead of get_contents/clean calls due to a bug in php 4.3.2
	$before_box = ob_get_clean(); // save html content before error box 
    ob_start('output_html');
    echo "</div>";
}
/*
	Helper to avoid sparse log notices.
*/
function end_flush() {
	global $Ajax;

	if (isset($Ajax))
		$Ajax->run();

 	 // on some (but not all) php versions zlib extension adds 1 additional level of buffering, 
 	 // so flush the last buffer outside the loop to be on safe side 
 	while(ob_get_level() > 1)
 		ob_end_flush();
	@ob_end_flush();

	// if any transaction was aborted unexpectedly rollback changes
	cancel_transaction();
}

function display_db_error($msg, $sql_statement=null, $exit=true)
{
	global $db, $SysPrefs;

	$warning = $msg==null;
	$db_error = db_error_no();
	
	if($warning)
		$str = "<b>" . __("Debug mode database warning:") . "</b><br>";
	else
		$str = "<b>" . __("DATABASE ERROR :") . "</b> $msg<br>";
	
	if ($db_error != 0) 
	{
		$str .= "error code : " . $db_error . "<br>";
		$str .= "error message : " . db_error_msg($db) . "<br>";
	}
	
	if ($SysPrefs->debug == 1)
	{
		$str .= "sql that failed was : ".$sql_statement."<br>";
	}
	
	$str .= "<br><br>";
	if (!$SysPrefs->go_debug)
		error_log($str);
	else {
		if($msg)
			fa_trigger_error($str, E_USER_ERROR);
		else	// $msg can be null here only in debug mode, otherwise the error is ignored
			fa_trigger_error($str, E_USER_WARNING);
	}
	if ($exit)
		throw new \App\Legacy\Exception\FlowTerminatedException;
}

function frindly_db_error($db_error)
{
	if ($db_error == DB_DUPLICATE_ERROR) 
	{
		display_error(__("The entered information is a duplicate. Please go back and enter different values."));
		return true;
	}
	
	return false;
}

function check_db_error($msg, $sql_statement, $exit_if_error=true, $rollback_if_error=true)
{
	global $db, $SysPrefs;
	$db_error = db_error_no();
	
	if ($db_error != 0) 
	{
		
		if ($SysPrefs->go_debug || !frindly_db_error($db_error)) {
			display_db_error($msg, $sql_statement, false);
		}
		
		if ($rollback_if_error) 
		{
		  	$rollback_result = db_query("rollback");
		}
		
		if ($exit_if_error) 
		{
			end_page(); throw new \App\Legacy\Exception\FlowTerminatedException;
		}
	}
	return $db_error;
}

