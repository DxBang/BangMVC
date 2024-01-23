<?php
namespace Bang;

class Sanitize {
	static function name($name):string {
		return (string) filter_var($name, FILTER_SANITIZE_STRING);
	}
	static function username($username):string {
		return (string) filter_var($username, FILTER_SANITIZE_STRING);
	}
	static function password($password):string {
		return (string) filter_var($password, FILTER_UNSAFE_RAW);
	}
	static function email($email):string {
		return (string) filter_var($email, FILTER_SANITIZE_EMAIL);
	}
	static function url($url):string {
		return (string) filter_var($url, FILTER_SANITIZE_URL);
	}
	static function domain($domain):string {
		return Validate::domain($domain) ? $domain : '';
	}
	static function host($host):string {
		return Validate::host($host) ? $host : '';
	}
	static function ip($ip):string {
		return Validate::ip($ip) ? $ip : '';
	}
	static function ipv4($ipv4):string {
		return Validate::ipv4($ipv4) ? $ipv4 : '';
	}
	static function ipv6($ipv6):string {
		return Validate::ipv6($ipv6) ? $ipv6 : '';
	}
	static function bool($bool):bool {
		return (bool) filter_var($bool, FILTER_VALIDATE_BOOLEAN);
	}
	static function float($float):float {
		return (float) filter_var($float, FILTER_SANITIZE_NUMBER_FLOAT);
	}
	static function int($int):int {
		return (int) filter_var($int, FILTER_SANITIZE_NUMBER_INT);
	}
	static function string($string):string {
		return (string) filter_var($string, FILTER_SANITIZE_STRING);
	}
}
