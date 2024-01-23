<?php
namespace Bang;

class Validate {
	static function name($name):bool {
		return preg_match('/^[a-zA-Z\- ]{2,50}/', $name);
	}
	static function username($username):bool {
		return preg_match('/'.Core::get('username','regex').'/', $username);
	}
	static function password($password):bool {
		return preg_match('/'.Core::get('password','regex').'/', $password);
	}
	static function email($email):bool {
		return filter_var($email, FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE);
	}
	static function url($url):bool {
		return filter_var($url, FILTER_VALIDATE_URL);
	}
	static function domain($domain):bool {
		return filter_var($domain, FILTER_VALIDATE_DOMAIN);
	}
	static function host($host):bool {
		return filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
	}
	static function ip($ip):bool {
		return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6);
	}
	static function ipv4($ipv4):bool {
		return filter_var($ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
	}
	static function ipv6($ipv6):bool {
		return filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
	}
	static function mac($mac) {
		return filter_var($mac, FILTER_VALIDATE_MAC);
	}
	static function regex($regex):bool {
		return filter_var($regex, FILTER_VALIDATE_REGEXP);
	}
	static function bool($bool):bool {
		return filter_var($bool, FILTER_VALIDATE_BOOLEAN);
	}
	static function float($float):bool {
		return filter_var($float, FILTER_VALIDATE_FLOAT);
	}
	static function int($int):bool {
		return filter_var($int, FILTER_VALIDATE_INT);
	}
	static function string($string):bool {
		return is_string($string);
	}
	static function value($value):bool {
		return $value !== null && $value !== false  && $value !== 0 && $value !== '';
	}
}
