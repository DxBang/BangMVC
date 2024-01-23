<?php
namespace Bang\CLI;

class Color {
	static
		$echo = false,
		$eol = false,
		$bg = [
			'black' => '40',
			'red' => '41',
			'green' => '42',
			'yellow' => '43',
			'blue' => '44',
			'purple' => '45',
			'cyan' => '46',
			'gray' => '47',
		],
		$fg = [
			'black' => '0;30',
			'dark_gray' => '1;30',
			'dark_blue' => '0;34',
			'blue' => '1;34',
			'dark_green' => '0;32',
			'green' => '1;32',
			'dark_cyan' => '0;36',
			'cyan' => '1;36',
			'dark_red' => '0;31',
			'red' => '1;31',
			'dark_purple' => '0;35',
			'purple' => '1;35',
			'dark_yellow' => '0;33',
			'yellow' => '1;33',
			'gray' => '0;37',
			'white' => '1;37',
		];
	static function bg(string $color = 'black') {
		return "\033[".
			(
				isset(self::$bg[$color])
				? self::$bg[$color]
				: self::$bg['white']
			).
			"m"; #]
	}
	static function fg(string $color = 'gray') {
		return "\033[".
			(
				isset(self::$fg[$color])
				? self::$fg[$color]
				: self::$fg['gray']
			).
			"m"; #]
	}
	static function text(string $text, string $fg = 'grey', string $bg = null) {
		return self::color($text, $fg, $bg);
	}
	static function color(string $text, string $fg = 'gray', string $bg = null) {
		if (!self::$echo)
			return self::_color($text, $fg, $bg).(self::$eol ? PHP_EOL : '');
		echo self::_color($text, $fg, $bg).(self::$eol ? PHP_EOL : '');
	}
	private static function _color(string $text, string $fg = 'gray', string $bg = null) {
		return
			($bg ? self::bg($bg) : null).
			self::fg($fg).
			$text.
			"\033[0m"; #]
	}
	static function warning(string $s) {
		return self::color(" {$s} ", "red", "red");
	}
	static function alert(string $s) {
		return self::color(" {$s} ", "yellow", "yellow");
	}
	static function success(string $s) {
		return self::color(" {$s} ", "blue", "blue");
	}
	static function good(string $s) {
		return self::color(" {$s} ", "green", "green");
	}
	static function info(string $s) {
		return self::color(" {$s} ", "gray", "black");
	}
}
