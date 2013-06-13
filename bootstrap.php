<?php
// Fallback time
if (ini_get('date.timezone') == null)
	date_default_timezone_set('UTC');

// Autoload
require_once('vendor/autoload.php');

// Defines
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', realpath(dirname($_SERVER['PHP_SELF'])));
define('TMP', ROOT.DS.'tmp');

// Check if TMP directory exists if not create it
if ( ! is_dir(TMP))
	mkdir(TMP);
if (fileperms(TMP) != 0777)
	chmod(TMP, 0777);