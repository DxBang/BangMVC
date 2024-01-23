<?php
date_default_timezone_set('UTC');
error_reporting(E_ALL); #  & ~E_NOTICE

define('BANG_ROOT',				__DIR__);
define('BANG_UI',				BANG_ROOT.'/ui');
define('BANG_DATA',				BANG_ROOT.'/data');
define('BANG_VENDOR',			BANG_ROOT.'/vendor');
define('BANG_VERSION',			'3.1');
define('JSON_ENCODE_SETTINGS',	JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
define('DATE_SQL', 				'Y-m-d H:i:s');

spl_autoload_register(function ($class) {
	if (defined('SITE_VENDOR')) {
		$file = constant('SITE_VENDOR').DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php';
		if (file_exists($file)) {
			require_once($file);
			return;
		}
	}
	$file = constant('BANG_VENDOR').DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php';
	if (file_exists($file)) {
		require_once($file);
		return;
	}
});

