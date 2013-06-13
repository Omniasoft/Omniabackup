<?php
// Fall back time
if (ini_get('date.timezone') == null)
	date_default_timezone_set('UTC');

// Auto load
require_once('vendor/autoload.php');

// Defines
define('DS', DIRECTORY_SEPARATOR);
define('PATH_ROOT', realpath(dirname($_SERVER['PHP_SELF'])));
define('PATH_TMP', PATH_ROOT.DS.'tmp');
define('PATH_CONFIG', PATH_ROOT.DS.'conf');
define('PATH_CRONTAB', PATH_CONFIG.DS.'crontab');

// Check if temp directory exists if not create it
if ( ! is_dir(PATH_TMP))
	mkdir(PATH_TMP);
if (fileperms(PATH_TMP) != 0777)
	chmod(PATH_TMP, 0777);