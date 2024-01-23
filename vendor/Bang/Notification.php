<?php
namespace Bang;

class Notification extends Model {
	const
		NOTE_ONE_TIME = 0,
		NOTE_USER = 1,
		NOTE_ALERT = 2,
		NOTE_IMPORTANT = 3,
		LIMIT = 10;
	protected static
		$data;

	static function make(string $message, int $level = 0, string $class = null, int $user_id = null) {
		$user_id = $user_id ?? Visitor::$user->id();
		echo 'Notification:'.$user_id;
	}

	static function array() {
		return self::$data;
	}
	static function html() {
		foreach (self::$data as $k => &$v) {

		}
	}
}