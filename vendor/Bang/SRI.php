<?php
namespace Bang;

class SRI {
	static protected
		$cacheFile = SITE_DATA.'/sri.cache.json',
		$cache,
		$http;
	public static function hash(string $path) {
		self::load();
		return self::$cache;
	}
	public function __destruct() {
		echo 'SRI!';
	}
	private static function load() {
		if (is_null(self::$cache))
			self::$cache = Model::json(self::$cacheFile);
	}
	private static function save() {

	}
	private static function cache() {

	}
	private static function http() {
		if (self::$http instanceof Chain\HTTP) return;
		self::$http = new Chain\HTTP;
	}
}