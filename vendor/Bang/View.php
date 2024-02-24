<?php
namespace Bang;

class View {
	public
		$model;
	protected static
		$instance,
		$data = [];
	function __construct() {
		if (self::$instance) {
			die('Only ONE View instance is allowed');
		}
		self::$instance = true;
		if (!is_object(self::$data)) self::$data = (object) [];
	}
	function __set(string $k, $v) {
		return self::set($k, $v);
	}
	function __get(string $k) {
		return self::get($k);
	}
	function __isset(string $k) {
		return self::isset($k);
	}
	function __unset(string $k) {
		return self::unset($k);
	}
	static function set(string $k, $v) {
		return self::$data->{$k} = $v;
	}
	static function get(string $k) {
		if (isset(self::$data->{$k})) {
			return self::$data->{$k};
		}
		return Config::get($k, 'view');
	}
	static function isset(string $k) {
		return isset(self::$data->{$k}) || Core::has($k, 'view');
	}
	static function unset(string $k) {
		unset(self::$data->{$k});
	}
	static function pair($k, &$v) {
		return self::$data->{$k} = &$v;
	}
	function render(string $view) {
		try {
			HTTP2::push();
			$viewName = strlen($view) ? strtolower($view) : 'index';
			$file = SITE_PRIVATE.'/views/'.$viewName.'.php';
			if (defined('SITE_LANGUAGE_SURFIX') && constant('SITE_LANGUAGE_SURFIX') && file_exists(SITE_PRIVATE.'/views/'.$viewName.'.'.constant('SITE_LANGUAGE_SURFIX').'.php')) {
				$file = SITE_PRIVATE.'/views/'.$viewName.'.'.constant('SITE_LANGUAGE_SURFIX').'.php';
			}
			if (!file_exists($file)) {
				throw new Exception('Render "'.$view.'" doesn\'t exists', 12321);
			}
			Core::mark('Bang\View::render('.$file.')');
			require $file;
			self::flush();
			return true;
		} catch (Error $e) {
			Core::error($e->getMessage(), $e->getCode(), $e->getPrevious());
		} catch (Exception $e) {
			Core::exception($e);
		}
	}
	static function flush() {
		ob_flush();
		flush();
	}
	static function data() {
		return self::$data;
	}
	static function exception(\Exception $e) {
		return Core::exception($e);
	}
	static function json($data) {
		return Core::json($data);
	}
	static function text(string $text, bool $forceHtml = true, bool $allowLines = false) {
		return Format::text($text, $forceHtml, $allowLines);
	}
	static function humanDataSize(int $int) {
		return Format::humanDataSize($int);
	}
	static function copyright(string $owner, int $year):string {
		$y = (int) gmdate('Y');
		if ($y > $year)
			return 'Copyright &copy; '.$year.'-'.$y.' '.$owner;
		return 'Copyright &copy; '.$year.' '.$owner;
	}
}
