<?php
namespace Bang;

class Config {
	protected static
		$data;
	function __construct(object $config) {
		self::init();
		return self::install($config);
	}
	static function init() {
		if (!is_object(self::$data)) self::$data = (object) [];
	}
	static function install(object $config) {
		foreach ($config as $k => $v) {
			self::set($k, $v);
		}
	}

	static function get(string $k, string $a = null) {
		if (!empty($a)) return self::isset($k, $a) ? self::$data->{$a}->{$k} : null;
		return self::isset($k) ? self::$data->{$k} : null;
	}
	static function set(string $k, $v, string $a = null) {
		if (!is_null($a)) return self::$data->{$a}->{$k} = $v;
		return self::$data->{$k} = $v;
	}
	static function has(string $k, string $a = null) {
		if (!is_null($a)) return isset(self::$data->{$a}->{$k});
		return isset(self::$data->{$k});
	}
	static function isset(string $k, string $a = null) {
		return self::has($k, $a);
	}
	static function unset(string $k) {
		unset(self::$data->{$k});
	}
	static function pair(string $k, &$v, string $a = null) {
		if (!is_null($a)) return self::$data->{$a}->{$k} = &$v;
		return self::$data->{$k} = &$v;
	}
	static function debug() {
		return self::$data;
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

	function __debugInfo() {
		return self::$data;
	}
	function __toString() {
		echo json_encode($this->__debugInfo());
	}
}


