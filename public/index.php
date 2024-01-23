<?php
$_SERVER['REQUEST_TIME_FLOAT'] = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(1);
$_SERVER['MEMORY_GET_USAGE'] = memory_get_usage(); # for BANG_DEV_MARKS
$_SERVER['MEMORY_GET_PEAK_USAGE'] = memory_get_peak_usage(); # for BANG_DEV_MARKS

define('SITE_ROOT',				dirname(__DIR__));
define('SITE_PUBLIC',			__DIR__);
define('SITE_PRIVATE',			SITE_ROOT.'/private');
define('SITE_DATA',				SITE_PRIVATE.'/data');
define('SITE_CACHE',			SITE_DATA.'/cache');
define('SITE_VENDOR',			SITE_PRIVATE.'/vendor');
define('SITE_SHARED_VENDOR',	'/srv/src/shared/vendor');

define('SITE_UI',				SITE_PUBLIC.'/ui');
define('WEB_UI',				'/ui');

if (file_exists(SITE_ROOT.'/.dev')) {
	require_once '/srv/src/bang/v3-dev/bang.php';
}
else {
	require_once '/srv/src/bang/v3/bang.php';
}

if (\Bang\Core::URI(0) == 'cli')
	return new \Bang\CLI();
if (\Bang\Core::URI(0) == 'api')
	return new \Bang\API();
return new \Bang\Website();
