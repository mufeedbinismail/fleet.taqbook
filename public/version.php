<?php
//==========================================================================================
//
// Settings in this file can be automatically updated at any time during software update.
//

// Versions used by source/database version compatibility checks. Do not change.
$GLOBALS['db_version']  = "2.4.1";
$GLOBALS['src_version'] = "2.4.18";

// application version - can be overriden in config/legacy.php
$GLOBALS['version']     = isset($SysPrefs->version) ? $SysPrefs->version : $GLOBALS['src_version'];

//======================================================================
// Legacy extension repository settings (retained for compatibility only)
// Can be overridden in config/legacy.php if third-party code still expects it.

$GLOBALS['repo_auth']   = isset($SysPrefs->repo_auth)
    ? $SysPrefs->repo_auth
    : array(
        'login' => 'anonymous',
        'pass' => 'password',
        'host' => 'repo.frontaccounting.eu', // repo server address
        'branch' => '2.4'	// Repository branch for current sources version
    );
