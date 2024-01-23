<?php
namespace Bang;

class JSON {
	public static
		$count,
		$depth = [],
		$countItems,
		$jsonArray,
		$jsonObject;
	public function __construct(bool $sendHeader = true) {
		if ($sendHeader) {
			self::headers();
		}
	}
	static function headers() {
		if (!headers_sent()) {
			header('Content-Type: application/json; charset=UTF-8');
		}
	}
	static function objectBegin(string $name = null) {
		self::$countItems = 0;
		self::$depth[] = $name;
		if (!empty($name)) {
			echo '{"'.$name.'":'.PHP_EOL;
			return;
		}
		echo '{'.PHP_EOL;
	}
	static function objectItem(string $name = null) {
		self::$countItems = 0;
		self::$depth[] = $name;
		if (!empty($name)) {
			echo '"'.$name.'":'.PHP_EOL;
			return;
		}
		echo ','.PHP_EOL;
	}
	static function objectEnd() {
		echo PHP_EOL.'}';
	}
	static function object(string $name, $object) {
		echo '"'.$name.'":'.PHP_EOL;
		self::json($object);
	}
	static function arrayBegin(string $name = null) {
		self::$countItems = 0;
		if (!empty($name)) {
			echo '"'.$name.'": ['.PHP_EOL;
			return;
		}
		echo '['.PHP_EOL;
	}
	static function arrayEnd() {
		echo PHP_EOL.']';
	}
	static function item($any, bool $forceSeparator = false) {
		if (self::$countItems || $forceSeparator) self::sep();
		self::json($any);
		self::$countItems++;
	}
	static function json($any) {
		if (is_object($any) && get_class($any) == 'PDORow') {
			echo '{"Warning": "Convert PDORow to Object first"}';
			return;
		}
		echo self::string($any);
	}
	static function sep() {
		echo ','.PHP_EOL;
	}
	static function string($any) {
		return json_encode($any, JSON_UNESCAPED_SLASHES);
	}
}

