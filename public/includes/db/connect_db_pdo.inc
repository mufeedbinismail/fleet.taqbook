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
define('DB_DUPLICATE_ERROR', 1062);
define('SQL_MODE', 'STRICT_ALL_TABLES'); // prevents SQL injection with silent field content truncation

$GLOBALS['db_last_inserted_id'] = 0;
$GLOBALS['db_last_stmt'] = null;

if (!db_extension_exists()) {
	throw new RuntimeException('The PDO MySQL extension is required.');
}

function set_global_connection($company=-1)
{
	global $db, $SysPrefs;

	if ($company == -1) 
		$company = user_company();

	cancel_transaction(); // cancel all aborted transactions (if any)

	$_SESSION["wa_current_user"]->cur_con = $company;

	// Use Laravel's existing PDO connection
	$db = \Illuminate\Support\Facades\DB::connection()->getPdo();

	// Set SQL mode
	db_query("SET sql_mode = '".SQL_MODE."'");
	
	$SysPrefs->refresh();
	return $db;
}

//DB wrapper functions to change only once for whole application

function db_query($sql, $err_msg=null)
{
	global $db, $db_last_stmt, $SysPrefs, $sql_queries, $db_connections, $Ajax, $db_last_inserted_id;

    // set current db prefix
    if ($sql instanceof \Illuminate\Database\Query\Builder) {
        $sql = $sql->toRawSql();
    } else {
        $comp = isset($_SESSION["wa_current_user"]->cur_con) ? $_SESSION["wa_current_user"]->cur_con : 0;
        $cur_prefix = (string)@$db_connections[$comp]['tbpref'];
        $sql = str_replace(TB_PREF, $cur_prefix, $sql);
    }

	if ($SysPrefs->show_sql)
	{
		$Ajax->activate('footer_debug');
		$sql_queries .= "<pre>$sql</pre>\n<hr>";
	}

	db_profile(); // mysql profiling

	// Deadlock retry logic
	$retry = MAX_DEADLOCK_RETRY;
	$stmt = null;
	do {
		try {
			$stmt = $db->query($sql);
            $stmt->_rows_fetched = 0;
			$db_last_stmt = $stmt;
			$db_last_inserted_id = $db->lastInsertId();
			$retry = 0;
		} catch (PDOException $e) {
			$info = $db->errorInfo();
			if ($info[1] == 1213) {  // Deadlock
				sleep(1);
				$retry--;
			} else {
				$retry = 0;
                $err_msg = $err_msg ?: '';
                $err_detail = $err_msg . ": " . $e->getMessage();
                if (function_exists('xdebug_call_file')) {
                    $err_detail = '<br>At file '.call_user_func('xdebug_call_file').':'.call_user_func('xdebug_call_line').':<br>'.$err_detail;
                }
                check_db_error($err_detail, $sql, true);
				return false;
			}
		}
	} while ($retry > 0);

	db_profile($sql);

	// SQL trail logging
	if($SysPrefs->sql_trail) {
		if ($SysPrefs->select_trail || (strstr($sql, 'SELECT') === false)) {
			// No table prefix - multi-company support removed
			$trail_sql = "INSERT INTO sql_trail (`sql`, `result`, `msg`) VALUES (?, ?, ?)";
			try {
				$trail_stmt = $db->prepare($trail_sql);
				$trail_stmt->execute([
					$sql,
					$stmt ? 1 : 0,
					$err_msg
				]);
			} catch (PDOException $e) {
				// Ignore trail insert errors to avoid recursion
			}
		}
	}

    // If the query failed, return false
    if (!$stmt) {
        return false;
    }

	// MIMIC MYSQLI: Return true for queries that don't produce result sets
	// Return PDOStatement for queries that produce result sets (SELECT, SHOW, DESCRIBE, EXPLAIN)
	// Use columnCount() to detect result-producing queries
	if ($stmt->columnCount() === 0) {
		return true;  // No columns = no result set (INSERT, UPDATE, DELETE, etc.)
	}

	return $stmt;  // Has columns = result set (SELECT, SHOW, DESCRIBE, etc.)
}

function db_ensure_cached(&$result)
{
	if (!$result) return false;
	
	// Cache all results if not already cached (for db_num_rows compatibility)
	if (!isset($result->_cached_results)) {
		$result->_cached_results = $result->fetchAll(PDO::FETCH_ASSOC);
		$result->_cache_pointer = 0;
        $result->_num_rows = count($result->_cached_results) + $result->_rows_fetched;
	}
	
	return true;
}

function db_free_cached(&$result)
{
    if (!$result) return;
    
    $result->_cached_results = null;
    $result->_cache_pointer = 0;
    $result->_num_rows = 0;
    $result->_rows_fetched = 0;
}

function db_fetch($result, $fetch_mode = PDO::FETCH_BOTH)
{
	if (!$result) return false;
	
	// Use cached results if available (for db_num_rows compatibility)
	if (isset($result->_cached_results)) {
		if ($result->_cache_pointer < count($result->_cached_results)) {
			$row = $result->_cached_results[$result->_cache_pointer++];
			// Convert to requested format
			if ($fetch_mode === PDO::FETCH_NUM) {
				return array_values($row);
			} elseif ($fetch_mode === PDO::FETCH_BOTH) {
				// Add numeric indices (0, 1, 2, ...) + associative keys
				return array_merge(array_values($row), $row);
			}
			return $row;  // PDO::FETCH_ASSOC (cached as assoc by default)
		}
		return false;
	}
	
    $row = $result->fetch($fetch_mode);

    if ($row) $result->_rows_fetched++;

	return $row;
}

function db_fetch_row($result)
{
	return db_fetch($result, PDO::FETCH_NUM);
}

function db_fetch_assoc($result)
{
	return db_fetch($result, PDO::FETCH_ASSOC);
}

function db_free_result($result)
{
	if ($result && $result instanceof PDOStatement) {
		$result->closeCursor();
        db_free_cached($result);
	}
}

function db_num_rows($result)
{
	if (!$result) return 0;
	
	// Ensure results are cached
	db_ensure_cached($result);
	
	return $result->_num_rows;
}

function db_num_fields($result)
{
	return $result ? $result->columnCount() : 0;
}

function db_escape($value = "", $nullify = false)
{
	global $db;

	$value = @html_entity_decode($value, ENT_QUOTES, language()->getEncoding());
	$value = html_specials_encode($value);

  	//reset default if second parameter is skipped
	$nullify = ($nullify === null) ? (false) : ($nullify);

  	//check for null/unset/empty strings
	if ((!isset($value)) || (is_null($value)) || ($value === "")) {
		$value = ($nullify) ? ("NULL") : ("''");
	} else {
		if (is_string($value)) {
			$value = $db->quote($value);  // Already adds quotes
      		//value is a string and should be quoted; 
		} else if (!is_numeric($value)) {
			//value is not a string nor numeric
			display_error("ERROR: incorrect data type send to sql query");
			echo '<br><br>';
			throw new \App\Legacy\Exception\FlowTerminatedException;
		}
	}
	return $value;
}

function db_error_no()
{
	global $db;
	$info = $db->errorInfo();
	return $info[1];  // MySQL error number, not SQLSTATE
}

function db_error_msg($conn)
{
	$info = $conn->errorInfo();
	return $info[2];  // Error message
}

function db_insert_id()
{
	global $db_last_inserted_id, $SysPrefs, $db;

	return $SysPrefs->sql_trail ? $db_last_inserted_id : (int)$db->lastInsertId();
}

function db_num_affected_rows()
{
	global $db_last_stmt;
	return $db_last_stmt ? $db_last_stmt->rowCount() : 0;
}

function db_field_name($result, $n)
{
	if (!$result) return false;
	$meta = $result->getColumnMeta($n);
	return $meta ? $meta['name'] : false;
}

function db_close($dbase = null)
{
	global $db;
	if (!$dbase) {
		$db = null;
	} else {
		$dbase = null;
	}
	return true;
}

function db_extension_exists()
{
	return class_exists('PDO') && in_array('mysql', PDO::getAvailableDrivers());
}

function db_escape_function($string)
{
	global $db;
	$quoted = $db->quote($string);
	return substr($quoted, 1, -1);  // Remove surrounding quotes
}

/*
	Set mysql client encoding.
	Default is is encoding used by default language.
*/
function db_set_encoding($ui_encoding=null)
{
	global $db;

	if (empty($ui_encoding)) {
		$ui_encoding = language()->getEncoding();
	}

	if ($mysql_enc = get_mysql_encoding_name($ui_encoding))
		$db->exec("SET NAMES '$mysql_enc'");
}

function db_get_charset($db)
{
	$result = $db->query("SELECT @@character_set_client as charset");
	$row = $result->fetch(PDO::FETCH_ASSOC);
	return $row ? $row['charset'] : '';
}

function db_set_charset($db, $charset)
{
	return $db->exec("SET NAMES '$charset'");
}

