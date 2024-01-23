<?php

if (!function_exists('mb_internal_encoding')) {
	die('this source needs mbstring');
}
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
date_default_timezone_set('UTC');
if (defined('SITE_ROOT')) {
	if (file_exists(SITE_ROOT.'/.dev')) {
		define('BANG_DEV', true);
		define('BANG_DEV_DEBUG', E_ALL);
		define('BANG_DEV_MARKS', true);
		error_reporting(BANG_DEV_DEBUG);
	}
	else {
		define('BANG_DEV', false);
		define('BANG_DEV_DEBUG', E_ALL & ~E_NOTICE);
		define('BANG_DEV_MARKS', false);
		error_reporting(BANG_DEV_DEBUG);
	}
}
else {
	exit('SITE_ROOT definition is required');
}

define('BANG_ROOT',				__DIR__);
define('BANG_UI',				BANG_ROOT.'/ui');
define('BANG_DATA',				BANG_ROOT.'/data');
define('BANG_VENDOR',			BANG_ROOT.'/vendor');
define('BANG_VERSION',			'3.1.10');
define('BANG_CODENAME',			'Peregrine Falcon');

define('BANG_CONTROL',			SITE_PRIVATE.'/controllers');
define('BANG_MODEL',			SITE_PRIVATE.'/models');
define('BANG_VIEW',				SITE_PRIVATE.'/views');

define('JSON_ENCODE_SETTINGS',	JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
define('DATE_SQL', 				'Y-m-d H:i:s');

set_error_handler(function (int $code, string $error, string $file, int $line, array $context = []) {
    $_ENV['errors'][] = [
		$code,
		$error,
		$file,
		$line,
		$context,
    ];
}, BANG_DEV_DEBUG);

register_shutdown_function(function() {
	if (!BANG_DEV) return;
	if (empty($_ENV['errors'])) return;
	foreach ($_ENV['errors'] as $e) {
		switch ($e[0]) {
			case E_ERROR:
				$type = 'error';
			break;
			case E_WARNING:
				$type = 'warning';
			break;
			case E_PARSE:
				$type = 'parse';
			break;
			case E_NOTICE:
				$type = 'notice';
			break;
			case E_CORE_ERROR:
				$type = 'core error';
			break;
			case E_CORE_WARNING:
				$type = 'core warning';
			break;
			case E_COMPILE_ERROR:
				$type = 'compile error';
			break;
			case E_COMPILE_WARNING:
				$type = 'compile warning';
			break;
			case E_USER_ERROR:
				$type = 'user error';
			break;
			case E_USER_WARNING:
				$type = 'user warning';
			break;
			case E_USER_NOTICE:
				$type = 'user notice';
			break;
			case E_STRICT:
				$type = 'strict';
			break;
			case E_RECOVERABLE_ERROR:
				$type = 'recoverable error';
			break;
			case E_DEPRECATED:
				$type = 'deprecated';
			break;
			case E_USER_DEPRECATED:
				$type = 'user deprecated';
			break;
				default: 'error code-'.$e[0];
		}
		if (Bang\Core::isAPI()) {
			echo PHP_EOL.json_encode(
				(object) [
					'type' => $type,
					'error' => $e[1],
					'file' => $e[2],
					'line' => $e[3],
					#'context' => $e[4],
				],
				JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
			);
			continue;
		}
		
		if (Bang\Core::isWebsite()) {
			echo '<dl class="bang debug '.$type.'">'
				.'<dt>type</dt><dd>'.$type.'</dd>'
				.'<dt>error</dt><dd>'.$e[1].'</dd>'
				.'<dt>file</dt><dd>'.$e[2].' ('.$e[3].')</dd>'
				.'<dt>context</dt><dd><pre>';
			print_r($e[4]);
			echo '</pre></dd>'
				.'</dl>';
			continue;
		}
		echo str_pad($type, 18).' '.$e[1].PHP_EOL
			.str_pad('-', 18, ' ', STR_PAD_LEFT).' '.$e[2].' ('.$e[3].')'.PHP_EOL;
		#print_r($e[4]);
	}
});

spl_autoload_register(function ($class) {
	$class = ltrim($class, '\\');
	foreach ([
		defined('SITE_VENDOR') ? constant('SITE_VENDOR') : null,
		constant('BANG_VENDOR'),
		defined('SITE_SHARED_VENDOR') ? constant('SITE_SHARED_VENDOR') : null,
	] as $vendor) {
		if (is_null($vendor)) continue;
		$vendor = rtrim($vendor, DIRECTORY_SEPARATOR);
		$file = $vendor.DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php';
		if (file_exists($file)) {
			require_once $file;
			Bang\Core::mark($file);
			return;
		}
	}
});

if (Bang\Core::isCLI()) {
	echo 'Bang! v'.BANG_VERSION.PHP_EOL;
	$_SERVER['HTTP_HOST']
		= $_SERVER['HTTP_SERVER']
		= 'bang.commandline';
	$_SERVER['REQUEST_URI'] = !empty($argv[1]) ? $argv[1] : '/';
}

try {
	if (!file_exists(SITE_PRIVATE.'/config.php')) throw new \Exception('missing config', 1);
	require_once SITE_PRIVATE.'/config.php';
	return new Bang\Core($config);
}
catch (Exception $e) {
	echo $e->getCode().': '.$e->getMessage().PHP_EOL;
	echo $e->getFile().'('.$e->getLine().')'.PHP_EOL;
	exit;
}

function print_d($any, int $indent = 0, int $index = 0, $key = null, string $indent_as = "\t") {
	$type = strtolower(gettype($any));
	echo str_repeat($indent_as, $indent);
	if (!is_null($key)) {
		echo $key.' = ';
	}
	$indent++;
	echo '('.$type[0].') <span class="'.$type.'">';
	switch ($type) {
		case 'array':
			echo '['.PHP_EOL;
			foreach($any as $key => $value) {
				print_d($value, ($indent), $index, $key);
				$index++;
			}
			echo str_repeat($indent_as, ($indent - 1)).']';
			break;
		case 'object':
			echo '{'.PHP_EOL;
			if (method_exists($any, '__debugInfo')) {
				$any = $any->__debugInfo();
			}
			foreach($any as $key => $value) {
				print_d($value, ($indent), $index, $key);
				$index++;
			}
			echo str_repeat($indent_as, ($indent - 1)).'}';
			break;
		case 'string':
			echo '<q>'.$any.'</q>';
			break;
		case 'boolean':
			echo '<i>'.($any ? 'true' : 'false').'</i>';
			break;
		case 'integer':
		case 'double':
			echo '<i>'.$any.'</i>';
			break;
		case 'null':
		case 'resource':
			echo '<i>'.$type.'</i>';
			break;
		default:
			echo '<i>unknown</i>';
			break;
	}
	echo '</span>'.PHP_EOL;
}
